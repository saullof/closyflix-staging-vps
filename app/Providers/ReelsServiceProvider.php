<?php

namespace App\Providers;

use App\Model\Attachment;
use App\Model\Reaction;
use App\Model\Reel;
use App\Model\ReelComment;
use App\Model\ReelView;
use App\Model\User;
use App\Model\UserBookmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReelsServiceProvider
{
    private const MAX_EXCLUDED_FEED_REEL_IDS = 100;

    public static function forFeed(?User $viewer, int $limit = 10, int $offset = 0, ?int $randomSeed = null, bool $prioritizeUnseen = false, array $excludeIds = []): Collection
    {
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isAdmin = $viewer && (int) ($viewer->role_id ?? 0) === 1;
        $authorIds = $viewer
            ? StoriesServiceProvider::allowedStoryAuthorIds($viewer)
                ->map(fn ($id) => (int) $id)
                ->values()
            : collect();

        $query = Reel::query()
            ->whereHas('video')
            ->when(!$isAdmin, function ($query) use ($authorIds, $viewerId) {
                $query->where(function ($query) use ($authorIds, $viewerId) {
                    $query->where('is_public', 1);

                    if ($authorIds->isNotEmpty()) {
                        $query->orWhereIn('user_id', $authorIds->all());
                    } elseif ($viewerId) {
                        $query->orWhere('user_id', $viewerId);
                    }
                });
            })
            ->with(self::defaultRelations())
            ->withCount(['views', 'comments', 'reactions', 'bookmarks']);

        $excludeIds = collect($excludeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(self::MAX_EXCLUDED_FEED_REEL_IDS)
            ->values();

        if ($excludeIds->isNotEmpty()) {
            $query->whereNotIn('reels.id', $excludeIds->all());
        }

        if ($prioritizeUnseen && $viewer) {
            // Widget-specific ranking: unseen first, then optionally shuffle only the unseen bucket.
            // Seen reels remain newest-first so an all-seen feed has predictable ordering.
            $seenOrderSql = 'CASE WHEN EXISTS (SELECT 1 FROM reel_views WHERE reel_views.reel_id = reels.id AND reel_views.user_id = ?) THEN 1 ELSE 0 END ASC';
            $query->orderByRaw($seenOrderSql, [(int) $viewer->id]);

            if ($randomSeed) {
                $unseenRandomSql = 'CASE WHEN NOT EXISTS (SELECT 1 FROM reel_views WHERE reel_views.reel_id = reels.id AND reel_views.user_id = ?) THEN RAND(?) ELSE 0 END ASC';
                $query->orderByRaw($unseenRandomSql, [(int) $viewer->id, $randomSeed]);
            }

            $query->orderByDesc('created_at');
        } elseif ($randomSeed) {
            $query->inRandomOrder($randomSeed);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->values();
    }

    public static function forProfile(?User $viewer, User $profileUser, int $limit = 10, int $offset = 0): Collection
    {
        $canViewPrivate = self::canViewPrivateReelsFromUser($viewer, (int) $profileUser->id);

        return Reel::query()
            ->where('user_id', (int) $profileUser->id)
            ->whereHas('video')
            ->when(!$canViewPrivate, fn ($query) => $query->where('is_public', 1))
            ->with(self::defaultRelations())
            ->withCount(['views', 'comments', 'reactions', 'bookmarks'])
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->filter(fn (Reel $reel) => self::canViewReel($viewer, $reel))
            ->values();
    }

    public static function forBookmarks(User $viewer, int $limit = 10, int $offset = 0): Collection
    {
        return Reel::query()
            ->select('reels.*')
            ->join('user_bookmarks', function ($join) use ($viewer) {
                $join->on('user_bookmarks.reel_id', '=', 'reels.id')
                    ->where('user_bookmarks.user_id', '=', (int) $viewer->id);
            })
            ->whereHas('video')
            ->with(self::defaultRelations())
            ->withCount(['views', 'comments', 'reactions', 'bookmarks'])
            ->orderByDesc('user_bookmarks.created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->filter(fn (Reel $reel) => self::canViewReel($viewer, $reel))
            ->values();
    }

    public static function profileCount(?User $viewer, User $profileUser): int
    {
        $canViewPrivate = self::canViewPrivateReelsFromUser($viewer, (int) $profileUser->id);

        return Reel::query()
            ->where('user_id', (int) $profileUser->id)
            ->whereHas('video')
            ->when(!$canViewPrivate, fn ($query) => $query->where('is_public', 1))
            ->count();
    }

    public static function canViewReel(?User $viewer, Reel $reel): bool
    {
        if ((bool) $reel->is_public) {
            return true;
        }

        return self::canViewPrivateReelsFromUser($viewer, (int) $reel->user_id);
    }

    public static function canViewPrivateReelsFromUser(?User $viewer, int $profileUserId): bool
    {
        if (!$viewer) {
            return false;
        }

        if ((int) $viewer->id === $profileUserId) {
            return true;
        }

        if ((int) ($viewer->role_id ?? 0) === 1) {
            return true;
        }

        return StoriesServiceProvider::allowedStoryAuthorIds($viewer)
            ->contains($profileUserId);
    }

    public static function toFrontendPayload($reels, ?User $viewer = null): array
    {
        $viewer = $viewer ?: Auth::user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $reelIds = collect($reels)->pluck('id')->map(fn ($id) => (int) $id)->values();

        $reactedIds = [];
        $bookmarkedIds = [];
        $seenIds = [];

        if ($viewerId && $reelIds->isNotEmpty()) {
            $reactedIds = Reaction::query()
                ->where('user_id', $viewerId)
                ->whereIn('reel_id', $reelIds)
                ->pluck('reel_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();

            $bookmarkedIds = UserBookmark::query()
                ->where('user_id', $viewerId)
                ->whereIn('reel_id', $reelIds)
                ->pluck('reel_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();

            $seenIds = ReelView::query()
                ->where('user_id', $viewerId)
                ->whereIn('reel_id', $reelIds)
                ->pluck('reel_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();
        }

        return [
            'reels' => collect($reels)->map(function (Reel $reel) use ($viewerId, $reactedIds, $bookmarkedIds, $seenIds) {
                $video = self::firstAttachmentOfType($reel->attachments, 'video');
                $cover = self::firstAttachmentOfType($reel->attachments, 'image');
                $coverSrc = $cover
                    ? $cover->path
                    : (($video && $video->has_thumbnail && !empty($video->thumbnail)) ? $video->thumbnail : null);
                $user = $reel->user;

                return [
                    'id' => (int) $reel->id,
                    'caption' => $reel->caption,
                    'is_public' => (bool) $reel->is_public,
                    'overlay' => $reel->overlay,
                    'duration' => $video && $video->length ? (int) $video->length : null,
                    'src' => $video ? $video->path : null,
                    'cover' => $coverSrc,
                    'time' => optional($reel->created_at)->timestamp ?? now()->timestamp,
                    'views' => (int) ($reel->views_count ?? 0),
                    'comments' => (int) ($reel->comments_count ?? 0),
                    'reactions' => (int) ($reel->reactions_count ?? 0),
                    'bookmarks' => (int) ($reel->bookmarks_count ?? 0),
                    'reacted' => isset($reactedIds[(int) $reel->id]),
                    'bookmarked' => isset($bookmarkedIds[(int) $reel->id]),
                    'seen' => isset($seenIds[(int) $reel->id]),
                    'owner' => $viewerId && ((int) $reel->user_id === $viewerId),
                    'url' => route('reels.get', ['reel_id' => (int) $reel->id]),
                    'user' => [
                        'id' => (int) $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'photo' => $user->avatar,
                        'verified' => GenericHelperServiceProvider::isUserVerified($user),
                        'url' => route('profile', ['username' => $user->username]),
                    ],
                    'sound' => self::mapSound($reel->sound),
                ];
            })->values(),
        ];
    }

    public static function commentsPayload(Reel $reel, ?User $viewer = null): array
    {
        $viewer = $viewer ?: Auth::user();
        $viewerId = $viewer ? (int) $viewer->id : null;

        $comments = ReelComment::query()
            ->where('reel_id', (int) $reel->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,avatar,birthdate,email_verified_at',
                'user.verification:id,user_id,status',
                'reactions',
            ])
            ->withCount('reactions')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $commentIds = $comments->pluck('id')->map(fn ($id) => (int) $id)->values();
        $reactedIds = [];

        if ($viewerId && $commentIds->isNotEmpty()) {
            $reactedIds = Reaction::query()
                ->where('user_id', $viewerId)
                ->whereIn('reel_comment_id', $commentIds)
                ->pluck('reel_comment_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();
        }

        return [
            'comments' => $comments->map(fn (ReelComment $comment) => self::commentPayload($comment, $viewer, $reel, $reactedIds))->values(),
        ];
    }

    public static function commentPayload(ReelComment $comment, ?User $viewer, Reel $reel, array $reactedIds = []): array
    {
        $viewerId = $viewer ? (int) $viewer->id : null;
        $comment->loadMissing([
            'user:id,name,username,avatar,birthdate,email_verified_at',
            'user.verification:id,user_id,status',
        ]);

        return [
            'id' => (int) $comment->id,
            'message' => $comment->message,
            'time' => optional($comment->created_at)->timestamp ?? now()->timestamp,
            'reactions' => (int) ($comment->reactions_count ?? 0),
            'reacted' => isset($reactedIds[(int) $comment->id]),
            'owner' => $viewerId && ((int) $comment->user_id === $viewerId),
            'can_delete' => $viewerId && (
                (int) $comment->user_id === $viewerId
                || (int) $reel->user_id === $viewerId
            ),
            'user' => [
                'id' => (int) $comment->user->id,
                'name' => $comment->user->name,
                'username' => $comment->user->username,
                'photo' => $comment->user->avatar,
                'verified' => GenericHelperServiceProvider::isUserVerified($comment->user),
                'url' => route('profile', ['username' => $comment->user->username]),
            ],
        ];
    }

    public static function firstAttachmentOfType($attachments, string $type): ?Attachment
    {
        return collect($attachments)->first(function (Attachment $attachment) use ($type) {
            return AttachmentServiceProvider::getAttachmentType($attachment->type) === $type;
        });
    }

    public static function defaultRelations(): array
    {
        return [
            'user' => function ($q) {
                $q->select('id', 'name', 'username', 'avatar', 'birthdate', 'email_verified_at')
                    ->with(['verification' => function ($v) {
                        $v->select('id', 'user_id', 'status');
                    }]);
            },
            'attachments' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'sound.attachments',
        ];
    }

    protected static function mapSound($sound): ?array
    {
        if (!$sound) {
            return null;
        }

        return [
            'id' => (int) $sound->id,
            'title' => $sound->title,
            'artist' => $sound->artist,
            'description' => $sound->description,
            'cover' => optional($sound->coverAttachment)->path,
            'audio_src' => optional($sound->audioAttachment)->path,
        ];
    }
}
