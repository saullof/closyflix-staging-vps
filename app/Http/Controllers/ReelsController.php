<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveReelCommentRequest;
use App\Http\Requests\StoreReelRequest;
use App\Model\Attachment;
use App\Model\Reaction;
use App\Model\Reel;
use App\Model\ReelComment;
use App\Model\ReelView;
use App\Model\Sound;
use App\Model\UserBookmark;
use App\Providers\AttachmentServiceProvider;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\ReelsServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JavaScript;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Throwable;

class ReelsController extends Controller
{
    private const DEFAULT_REELS_LIMIT = 10;
    private const MAX_REELS_LIMIT = 30;
    private const MAX_EXCLUDED_REEL_IDS = 100;

    public function index()
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        return view('pages.reels.index');
    }

    public function create()
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $canPost = $this->canCreateReel();

        JavaScript::put([
            'isAllowedToPost' => $canPost,
            'mediaSettings' => [
                'allowed_file_extensions' => '.'.str_replace(',', ',.', AttachmentServiceProvider::filterExtensions('videosFallback')),
                'max_file_upload_size' => (int) getSetting('media.max_file_upload_size'),
                'transcoding_driver' => getSetting('media.transcoding_driver'),
                'enforce_mp4_conversion' => (bool)getSetting('media.enforce_mp4_conversion'),
                'coconut_enforce_mp4_conversion' => (bool)getSetting('media.coconut_enforce_mp4_conversion'),
                'manual_payments_file_extensions' => '.'.str_replace(',', ',.', AttachmentServiceProvider::filterExtensions('manualPayments')),
                'manual_payments_excel_icon' => asset('/img/excel-preview.svg'),
                'manual_payments_pdf_icon' => asset('/img/pdf-preview.svg'),
                'initUploader' => $canPost,
            ],
            'reels' => [
                'maxVideoLengthSeconds' => (int) getSetting('reels.max_video_length_seconds'),
                'allowSounds' => (bool) getSetting('reels.allow_sounds'),
                'allowPublic' => (bool) getSetting('reels.allow_public_reels'),
            ],
        ]);

        return view('pages.reels.create');
    }

    public function feed(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $limit = (int) ($request->get('limit') ?: self::DEFAULT_REELS_LIMIT);
        $limit = max(1, min($limit ?: self::DEFAULT_REELS_LIMIT, self::MAX_REELS_LIMIT));
        $offset = max(0, (int) $request->get('offset', 0));
        $randomSeed = $request->boolean('randomize')
            ? max(1, (int) $request->get('seed', 1))
            : null;
        $prioritizeUnseen = $request->boolean('prioritize_unseen');
        // Feed widgets use this to keep repeated strips fresh without changing explore/profile ordering.
        $excludeIds = collect(explode(',', (string) $request->get('exclude_ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(self::MAX_EXCLUDED_REEL_IDS)
            ->values()
            ->all();

        $reels = ReelsServiceProvider::forFeed($request->user(), $limit + 1, $offset, $randomSeed, $prioritizeUnseen, $excludeIds);
        $hasMore = $reels->count() > $limit;
        $reels = $reels->take($limit)->values();
        $initialReelId = (int) $request->get('reel_id');

        if ($initialReelId && $offset === 0) {
            $initialReel = Reel::query()
                ->whereHas('video')
                ->with(ReelsServiceProvider::defaultRelations())
                ->withCount(['views', 'comments', 'reactions', 'bookmarks'])
                ->find($initialReelId);

            if ($initialReel && ReelsServiceProvider::canViewReel($request->user(), $initialReel)) {
                $reels = collect([$initialReel])
                    ->merge($reels->reject(fn (Reel $reel) => (int) $reel->id === (int) $initialReel->id))
                    ->values();
            }
        }

        return response()->json(array_merge(
            ReelsServiceProvider::toFrontendPayload($reels, $request->user()),
            [
                'has_more' => $hasMore,
                'next_offset' => $offset + $reels->count(),
            ]
        ));
    }

    public function show(Request $request, int $reelId)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $reel = Reel::query()
            ->whereHas('video')
            ->with(ReelsServiceProvider::defaultRelations())
            ->withCount(['views', 'comments', 'reactions', 'bookmarks'])
            ->find($reelId);

        if (!$reel || !ReelsServiceProvider::canViewReel($request->user(), $reel)) {
            return response()->view('pages.reels.index', ['initialReelUnavailable' => true], 404);
        }

        return view('pages.reels.index', ['initialReelId' => (int) $reel->id]);
    }

    public function store(StoreReelRequest $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        if (!$this->canCreateReel()) {
            return $this->identityVerificationRequiredResponse();
        }

        $user = $request->user();
        $data = $request->validated();

        if (!empty($data['is_public']) && !getSetting('reels.allow_public_reels')) {
            return response()->json(['message' => __('Public reels are disabled.')], 422);
        }

        $video = $this->unusedUserAttachment($data['video_attachment_id'], $user->id);
        if (!$video || AttachmentServiceProvider::getAttachmentType($video->type) !== 'video') {
            return response()->json(['message' => __('Please upload one video first.')], 422);
        }

        if ($video->coconut_id && str_contains($video->filename, '/tmp/')) {
            return response()->json(['message' => __('Video is still processing.')], 422);
        }

        $maxLength = (int) getSetting('reels.max_video_length_seconds');
        if ($maxLength > 0) {
            $videoLength = $this->resolveVideoLength($video);

            if ($videoLength === null && $this->requiresServerSideVideoLength()) {
                return response()->json(['message' => __('Video duration could not be verified.')], 422);
            }

            if ($videoLength !== null && $videoLength > $maxLength) {
                return response()->json(['message' => __('Video is too long.')], 422);
            }
        }

        $cover = null;
        if (!empty($data['cover_attachment_id'])) {
            $cover = $this->unusedUserAttachment($data['cover_attachment_id'], $user->id);
            if (!$cover || AttachmentServiceProvider::getAttachmentType($cover->type) !== 'image') {
                return response()->json(['message' => __('Cover must be an image.')], 422);
            }
        }

        $soundId = $data['sound_id'] ?? null;
        if ($soundId && !getSetting('reels.allow_sounds')) {
            return response()->json(['message' => __('Sounds are disabled.')], 422);
        }

        if ($soundId && !Sound::where('id', $soundId)->where('is_active', 1)->exists()) {
            return response()->json(['message' => __('Invalid sound.')], 422);
        }

        $reel = DB::transaction(function () use ($user, $data, $video, $cover, $soundId) {
            $reel = Reel::create([
                'user_id' => (int) $user->id,
                'caption' => $data['caption'] ?? null,
                'is_public' => !empty($data['is_public']),
                'overlay' => $data['overlay'] ?? null,
                'sound_id' => $soundId,
            ]);

            $attachmentIds = [$video->id];
            if ($cover) {
                $attachmentIds[] = $cover->id;
            }

            $updatedAttachments = Attachment::query()
                ->whereIn('id', $attachmentIds)
                ->where('user_id', (int) $user->id)
                ->whereNull('post_id')
                ->whereNull('message_id')
                ->whereNull('message_template_id')
                ->whereNull('payment_request_id')
                ->whereNull('story_id')
                ->whereNull('reel_id')
                ->whereNull('sound_id')
                ->update(['reel_id' => (int) $reel->id]);

            if ($updatedAttachments !== count($attachmentIds)) {
                throw ValidationException::withMessages([
                    'video_attachment_id' => __('Selected media is no longer available. Please upload it again.'),
                ]);
            }

            return $reel;
        });

        return response()->json([
            'success' => true,
            'reel_id' => (int) $reel->id,
            'redirect_url' => route('reels.get', ['reel_id' => (int) $reel->id]),
        ]);
    }

    public function markView(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validate([
            'reel_id' => ['required', 'integer', 'exists:reels,id'],
        ]);

        $reel = $this->findViewableReel($request, (int) $data['reel_id']);
        if (!$reel) {
            return $this->forbiddenResponse();
        }

        if ((int) $reel->user_id !== (int) $request->user()->id) {
            ReelView::updateOrCreate(
                [
                    'reel_id' => (int) $reel->id,
                    'user_id' => (int) $request->user()->id,
                ],
                ['seen_at' => now()]
            );
        }

        return response()->json([
            'success' => true,
            'views' => ReelView::where('reel_id', (int) $reel->id)->count(),
        ]);
    }

    public function comments(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validate([
            'reel_id' => ['required', 'integer', 'exists:reels,id'],
        ]);

        $reel = $this->findViewableReel($request, (int) $data['reel_id']);
        if (!$reel) {
            return $this->forbiddenResponse();
        }

        return response()->json(ReelsServiceProvider::commentsPayload($reel, $request->user()));
    }

    public function addComment(SaveReelCommentRequest $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validated();
        $reel = $this->findViewableReel($request, (int) $data['reel_id']);
        if (!$reel) {
            return $this->forbiddenResponse();
        }

        if (!empty($data['parent_id'])) {
            $parent = ReelComment::where('id', (int) $data['parent_id'])
                ->where('reel_id', (int) $reel->id)
                ->first();

            if (!$parent) {
                return response()->json(['message' => __('Invalid comment.')], 422);
            }
        }

        $comment = ReelComment::create([
            'reel_id' => (int) $reel->id,
            'user_id' => (int) $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'message' => $data['message'],
        ]);
        $comment->load([
            'user:id,name,username,avatar,birthdate,email_verified_at',
            'user.verification:id,user_id,status',
        ]);
        $comment->loadCount('reactions');

        return response()->json([
            'success' => true,
            'comment_id' => (int) $comment->id,
            'comment' => ReelsServiceProvider::commentPayload($comment, $request->user(), $reel),
            'comments' => ReelComment::where('reel_id', (int) $reel->id)->count(),
        ]);
    }

    public function deleteComment(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validate([
            'comment_id' => ['required', 'integer', 'exists:reel_comments,id'],
        ]);

        $comment = ReelComment::with('reel')->findOrFail((int) $data['comment_id']);
        $userId = (int) $request->user()->id;

        if ((int) $comment->user_id !== $userId && (int) $comment->reel->user_id !== $userId) {
            return $this->forbiddenResponse();
        }

        $reelId = (int) $comment->reel_id;
        $comment->delete();

        return response()->json([
            'success' => true,
            'comments' => ReelComment::where('reel_id', $reelId)->count(),
        ]);
    }

    public function reaction(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validate([
            'type' => ['required', 'in:reel,comment'],
            'action' => ['required', 'in:add,remove'],
            'id' => ['required', 'integer'],
        ]);

        $target = null;
        $reel = null;
        $where = [
            'reaction_type' => Reaction::LIKE_TYPE,
            'user_id' => (int) $request->user()->id,
        ];

        if ($data['type'] === 'reel') {
            $target = Reel::find((int) $data['id']);
            $reel = $target;
            $where['reel_id'] = (int) $data['id'];
        } else {
            $target = ReelComment::with('reel')->find((int) $data['id']);
            $reel = $target ? $target->reel : null;
            $where['reel_comment_id'] = (int) $data['id'];
        }

        if (!$target || !$reel) {
            return response()->json(['message' => __('Not found')], 404);
        }

        if (!ReelsServiceProvider::canViewReel($request->user(), $reel)) {
            return $this->forbiddenResponse();
        }

        if ($data['action'] === 'add') {
            Reaction::firstOrCreate($where);
        } else {
            Reaction::where($where)->delete();
        }

        $countColumn = $data['type'] === 'reel' ? 'reel_id' : 'reel_comment_id';

        return response()->json([
            'success' => true,
            'reactions' => Reaction::where($countColumn, (int) $data['id'])->count(),
        ]);
    }

    public function bookmark(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:reels,id'],
            'action' => ['required', 'in:add,remove'],
        ]);

        $reel = $this->findViewableReel($request, (int) $data['id']);
        if (!$reel) {
            return $this->forbiddenResponse();
        }

        $where = [
            'user_id' => (int) $request->user()->id,
            'reel_id' => (int) $reel->id,
        ];

        if ($data['action'] === 'add') {
            UserBookmark::firstOrCreate($where, ['post_id' => null]);
            $message = __('Reel saved.');
        } else {
            UserBookmark::where($where)->delete();
            $message = __('Reel removed from bookmarks.');
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'bookmarks' => UserBookmark::where('reel_id', (int) $reel->id)->count(),
        ]);
    }

    public function delete(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $data = $request->validate([
            'reel_id' => ['required', 'integer', 'exists:reels,id'],
        ]);

        $reel = Reel::where('id', (int) $data['reel_id'])
            ->where('user_id', (int) $request->user()->id)
            ->with('attachments')
            ->first();

        if (!$reel) {
            return $this->forbiddenResponse();
        }

        DB::transaction(function () use ($reel) {
            foreach ($reel->attachments as $attachment) {
                AttachmentServiceProvider::removeAttachment($attachment);
            }

            $reel->delete();
        });

        return response()->json(['success' => true]);
    }

    protected function canCreateReel(): bool
    {
        return !getSetting('site.enforce_user_identity_checks')
            || GenericHelperServiceProvider::isUserVerified();
    }

    protected function identityVerificationRequiredResponse()
    {
        return response()->json([
            'success' => false,
            'message' => __('User not verified. Can not post content.'),
            'errors' => ['permissions' => __('User not verified. Can not post content.')],
        ], 403);
    }

    protected function forbiddenResponse()
    {
        return response()->json(['message' => __('Forbidden')], 403);
    }

    protected function findViewableReel(Request $request, int $reelId): ?Reel
    {
        $reel = Reel::find($reelId);

        if (!$reel || !ReelsServiceProvider::canViewReel($request->user(), $reel)) {
            return null;
        }

        return $reel;
    }

    protected function unusedUserAttachment(string $id, int $userId): ?Attachment
    {
        return Attachment::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('post_id')
            ->whereNull('message_id')
            ->whereNull('message_template_id')
            ->whereNull('payment_request_id')
            ->whereNull('story_id')
            ->whereNull('reel_id')
            ->whereNull('sound_id')
            ->first();
    }

    protected function resolveVideoLength(Attachment $video): ?int
    {
        $length = (int) ($video->length ?? 0);
        if ($length > 0) {
            return $length;
        }

        if (!$video->filename) {
            return null;
        }

        try {
            $duration = FFMpeg::fromDisk(AttachmentServiceProvider::getStorageProviderName($video->driver))
                ->open($video->filename)
                ->getDriver()
                ->getDurationInSeconds();

            if ($duration && (float) $duration > 0) {
                $length = (int) ceil((float) $duration);
                $video->forceFill(['length' => $length])->save();

                return $length;
            }
        } catch (Throwable $exception) {
            return null;
        }

        return null;
    }

    protected function requiresServerSideVideoLength(): bool
    {
        return in_array((string) getSetting('media.transcoding_driver'), ['ffmpeg', 'coconut'], true);
    }
}
