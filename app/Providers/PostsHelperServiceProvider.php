<?php

namespace App\Providers;

use App\Model\Attachment;
use App\Model\Hashtag;
use App\Model\HashtagLink;
use App\Model\Poll;
use App\Model\PollAnswer;
use App\Model\PollUserAnswer;
use App\Model\Post;
use App\Model\PostComment;
use App\Model\Stream;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\UserList;
use App\Model\User;
use Carbon\Carbon;
use Cookie;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use View;

class PostsHelperServiceProvider extends ServiceProvider
{
    /**
     * Get latest user attachments.
     *
     * @param int|false $userID
     * @param string|false $type
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public static function getLatestUserAttachments($userID = false, $type = false)
    {
        if (!$userID) {
            if (Auth::check()) {
                $userID = Auth::user()->id;
            } else {
                throw new \Exception(__('Can not fetch latest post attachments for this profile.'));
            }
        }
        $attachments = Attachment::with(['post'])->where('attachments.post_id', '<>', null)->where('attachments.user_id', $userID);

        if ($type) {
            $extensions = AttachmentServiceProvider::getTypeByExtension('image');
            $attachments->whereIn('attachments.type', $extensions);
        }
        // validate access for paid posts attachments
        if(Auth::check() && Auth::user()->role_id !== 1 && Auth::user()->id !== $userID) {
            $attachments->leftJoin('posts', 'posts.id', '=', 'attachments.post_id')
                ->leftJoin('transactions', 'transactions.post_id', '=', 'posts.id')
                ->where(function ($query) {
                    $query->where('posts.price', '=', floatval(0))
                        ->orWhere(function ($query) {
                            $query->where('transactions.id', '<>', null)
                                ->where('transactions.type', '=', Transaction::POST_UNLOCK)
                                ->where('transactions.status', '=', Transaction::APPROVED_STATUS)
                                ->where('transactions.sender_user_id', '=', Auth::user()->id);
                        });
                })
                ->where(function ($query) {
                    $query->where('posts.expire_date', '>', Carbon::now());
                    $query->orWhere('posts.expire_date', null);
                })
                ->where(function ($query) {
                    $query->where('posts.release_date', '<', Carbon::now());
                    $query->orWhere('posts.release_date', null);
                })
                ->where('posts.status', 1);
        }
        $attachments = $attachments->limit(3)->orderByDesc('attachments.created_at')->get();

        return $attachments;
    }

    /**
     * Get user by it's username.
     *
     * @param $username
     * @return mixed
     */
    public static function getUserByUsername($username)
    {
        return User::where('username', $username)->first();
    }

    /**
     * Get user's all active subs.
     *
     * @param $userID
     * @return mixed
     */
    public static function getUserActiveSubs($userID)
    {
        $activeSubs = Subscription::where('sender_user_id', $userID)
            ->whereIn('status', [Subscription::ACTIVE_STATUS, Subscription::CANCELED_STATUS])
            ->where('expires_at', '>', Carbon::now()->toDateTimeString())
            ->pluck('recipient_user_id')->toArray();

        return $activeSubs;
    }

    /**
     * Get following users with free profiles.
     * @param $userId
     * @return mixed
     */
    public static function getFreeFollowingProfiles($userId) {
        $followingList = UserList::where('user_id', $userId)->where('type', 'following')->with(['members', 'members.user'])->first();
        $followingUserIds = [];
        foreach($followingList->members as $member){
            if(ProfileMonetizationServiceProvider::userHasFreeProfile($member->user)){
                $followingUserIds[] = $member->user->id;
            }
        }
        return $followingUserIds;
    }

    /**
     * Check if user has active sub to another.
     *
     * @param $sender_id
     * @param $recipient_id
     * @return bool
     */
    public static function hasActiveSub($sender_id, $recipient_id)
    {
        $hasSub = Subscription::where('sender_user_id', $sender_id)
            ->where('recipient_user_id', $recipient_id)
            ->whereIn('status', [Subscription::ACTIVE_STATUS, Subscription::CANCELED_STATUS])
            ->where('expires_at', '>', Carbon::now()->toDateTimeString())
            ->count();
        if ($hasSub > 0) {
            return true;
        }

        return false;
    }

    /**
     * Gets list of posts for feed.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param int|false $pageNumber
     * @param bool $mediaType
     * @return array|LengthAwarePaginator
     * @phpstan-return ($encodePostsToHtml is true ? array<string, mixed> : LengthAwarePaginator)
     */
    public static function getFeedPosts($userID, $encodePostsToHtml = false, $pageNumber = false, $mediaType = false, $sortOrder = false, $searchTerm = '', $searchMode = null)
    {
        return self::getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, false, false, false, $sortOrder, $searchTerm, $searchMode);
    }

    /**
     * Gets list of posts for profile.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param int|false $pageNumber
     * @param bool $mediaType
     * @return array|LengthAwarePaginator
     * @phpstan-return ($encodePostsToHtml is true ? array<string, mixed> : LengthAwarePaginator)
     */
    public static function getUserPosts($userID, $encodePostsToHtml = false, $pageNumber = false, $mediaType = false, $hasSub = false, $accessType = 'all')
    {
        return self::getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, true, $hasSub, false, false, '', '', $accessType);
    }

    /**
     * Gets list of posts for the bookmarks page.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param int|false $pageNumber
     * @param bool $mediaType
     * @return array|LengthAwarePaginator
     * @phpstan-return ($encodePostsToHtml is true ? array<string, mixed> : LengthAwarePaginator)
     */
    public static function getUserBookmarks($userID, $encodePostsToHtml = false, $pageNumber = false, $mediaType = false, $hasSub = false)
    {
        return self::getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, false, $hasSub, true);
    }

    /**
     * Returns lists of posts, conditioned by different filters.
     * TODO: This one should get refactored a little bit - eg: remove all un-necessary params to differ between these feed pages:
     * feed - profile (logged in/not logged in) - search - bookmarks.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param int|false $pageNumber
     * @param bool $mediaType
     * @return array|LengthAwarePaginator
     * @phpstan-return ($encodePostsToHtml is true ? array<string, mixed> : LengthAwarePaginator)
     */
    public static function getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, $ownPosts, $hasSub, $bookMarksOnly, $sortOrder = false, $searchTerm = '', $searchMode = '', $accessType = 'all')
    {
        $relations = ['user', 'reactions', 'attachments', 'bookmarks', 'postPurchases', 'mentions.mentionedUser'];

        // Fetching basic posts information
        $posts = Post::withCount('tips')
            ->with($relations);

        // For profile page
        if ($ownPosts) {
            $posts->where('user_id', $userID);
            // Registered
            if(Auth::check() && Auth::user()->id !== $userID) {
                $posts = self::filterPosts($posts, $userID, 'scheduled');
                $posts = self::filterPosts($posts, $userID, 'approvedPostsOnly');
            }
            // Un-registered
            elseif (!Auth::check()){
                $posts = self::filterPosts($posts, $userID, 'scheduled');
                $posts = self::filterPosts($posts, $userID, 'approvedPostsOnly');
            }
            $posts = self::filterPosts($posts, $userID, 'pinned');
        }
        // For bookmarks page
        elseif ($bookMarksOnly) {
            $posts = self::filterPosts($posts, $userID, 'bookmarks');
            $posts = self::filterPosts($posts, $userID, 'blocked');
        }
        // For feed (& Default search) page
        else {
            if ($searchMode === 'public') {
                // public discover: always show all posts (but still exclude blocked if logged in)
                $posts = self::filterPosts($posts, $userID, 'blocked');
            } elseif ($searchMode === 'paywall' && $searchTerm) {
                if (!$userID) {
                    $posts->whereRaw('1=0');
                } else {
                    $posts = self::filterPosts($posts, $userID, 'all');
                    $posts = self::filterPosts($posts, $userID, 'blocked');
                }
            } else {
                // feed default / non-explore calls
                $posts = self::filterPosts($posts, $userID, 'all');
            }
        }

        if (!$ownPosts) { // More feed/bookmarks/search rules
            $posts = self::filterPosts($posts, $userID, 'scheduled');
            $posts = self::filterPosts($posts, $userID, 'approvedPostsOnly');
        }

        // Media type filters
        if ($mediaType) {
            $posts = self::filterPosts($posts, $userID, 'media', $mediaType);
        }

        if ($ownPosts && $accessType !== 'all') {
            $posts = self::filterPosts($posts, $userID, 'access', $accessType);
        }

        // Filtering the search term
        if($searchTerm){
            $posts = self::filterPosts($posts, $userID, 'search', false, false, $searchTerm);
        }

        // Processing sorting
        $posts = self::filterPosts($posts, $userID, 'order', false, $sortOrder);

        if ($pageNumber) {
            $posts = $posts->paginate(getSetting('feed.feed_posts_per_page'), ['*'], 'page', $pageNumber)->appends(request()->query());
        } else {
            $posts = $posts->paginate(getSetting('feed.feed_posts_per_page'))->appends(request()->query());
        }

        if(Auth::check() && Auth::user()->role_id === 1){
            $hasSub = true;
        }

        // Precomputes for splitting between paywall or public search; granting proper $hasSub
        $viewerIsAdmin = Auth::check() && Auth::user()->role_id === 1;

        $allowedAuthorIds = [];
        if ($searchMode === 'public' && $userID) {
            $allowedAuthorIds = array_values(array_unique(array_merge(
                self::getUserActiveSubs($userID),
                self::getFreeFollowingProfiles($userID),
                [$userID]
            )));
        }

        // Shared access resolver for both mappers
        $resolveIsSubbed = function ($post) use ($viewerIsAdmin, $ownPosts, $hasSub, $searchMode, $userID, $allowedAuthorIds) {
            // Admin sees everything
            if ($viewerIsAdmin) {
                return true;
            }
            // Profile page logic (single creator page)
            if ($ownPosts) {
                return (bool) $hasSub;
            }
            // Public discover search: show all posts, but only unlock if open or accessible
            if ($searchMode === 'public') {
                $isOpen = (bool) ($post->user->is_open ?? false);
                $hasAccess = $userID ? in_array($post->user_id, $allowedAuthorIds, true) : false;
                return $isOpen || $hasAccess;
            }

            // Paywall search: never unlocked for guests
            if ($searchMode === 'paywall' && !$userID) {
                return false;
            }

            // Feed/bookmarks/paywall-search (authed): preserve existing behavior
            return true;
        };

        if ($encodePostsToHtml) {
            // JS rendered feed
            $data = [
                'total' => $posts->total(),
                'currentPage' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'prev_page_url' => $posts->previousPageUrl(),
                'next_page_url' => $posts->nextPageUrl(),
                'first_page_url' => $posts->nextPageUrl(),
                'hasMore' => $posts->hasMorePages(),
            ];
            $postsData = $posts->map(function ($post) use ($resolveIsSubbed, $ownPosts, $hasSub, $data) {
                if ($ownPosts) {
                    $post->setAttribute('isSubbed', (bool) $hasSub);
                } else {
                    $post->setAttribute('isSubbed', (bool) $resolveIsSubbed($post));
                }
                $post->setAttribute('postPage', $data['currentPage']);
                return [
                    'id' => $post->id,
                    'html' => View::make('elements.feed.post-box')->with('post', $post)->render(),
                ];
            });
            $data['posts'] = $postsData;
        } else {
            // Server side rendered feed
            $postsCurrentPage = $posts->currentPage();
            $posts->map(function ($post) use ($resolveIsSubbed, $ownPosts, $hasSub, $postsCurrentPage) {
                if ($ownPosts) {
                    $post->hasSub = $hasSub;
                }
                $post->setAttribute('isSubbed', (bool) $resolveIsSubbed($post));
                $post->setAttribute('postPage', $postsCurrentPage);
                return $post;
            });
            $data = $posts;
        }
        return $data;
    }

    /**
     * Filters out posts using fast, join based queries.
     * @param $posts
     * @param $userID
     * @param $filterType
     * @param bool $mediaType
     * @return mixed
     */
    public static function filterPosts($posts, $userID, $filterType, $mediaType = false, $sortOrder = false, $searchTerm = '')
    {
        if ($filterType == 'following' || $filterType == 'all') {
            // Followers only
            if($userID){
                $posts->join('user_list_members as following', function ($join) {
                    $join->on('following.user_id', '=', 'posts.user_id');
                    $join->on('following.list_id', '=', DB::raw(Auth::user()->lists->firstWhere('type', 'following')->id));
                });
            }
        }

        if ($filterType == 'blocked' || $filterType == 'all') {
            // Blocked users
            if($userID){
                $blockedUsers = ListsHelperServiceProvider::getListMembers(Auth::user()->lists->firstWhere('type', 'blocked')->id);
                $posts->whereNotIn('posts.user_id', $blockedUsers);
            }
        }

        if ($filterType == 'subs' || $filterType == 'all') {
            if($userID){
                if($filterType == 'all'){
                    $userIds = array_merge(self::getUserActiveSubs($userID), self::getFreeFollowingProfiles($userID));
                    $followingList = Auth::user()->lists->firstWhere('type', UserList::FOLLOWING_TYPE);
                    $followingUserIds = $followingList
                        ? ListsHelperServiceProvider::getListMembers($followingList->id)
                        : [];

                    $posts->where(function ($query) use ($userIds, $followingUserIds) {
                        $query->whereIn('posts.user_id', $userIds)
                            ->orWhere(function ($query) use ($followingUserIds) {
                                $query->where('posts.is_free', true)
                                    ->whereIn('posts.user_id', $followingUserIds);
                            });
                    });
                } else {
                    // Subs only
                    $activeSubs = self::getUserActiveSubs($userID);
                    $posts->whereIn('posts.user_id', $activeSubs);
                }
            }
            // Else: Don't filter anything, return all posts, for search page when un-auth (and site settings set so)
        }

        if ($filterType == 'bookmarks') {
            $posts->join('user_bookmarks', function ($join) use ($userID) {
                $join->on('user_bookmarks.post_id', '=', 'posts.id');
                $join->on('user_bookmarks.user_id', '=', DB::raw($userID));
            });
            $posts->orderBy('user_bookmarks.created_at', 'DESC');
            // Filtering allowed userIDs only for active bookmarks
            $userIds = array_merge(self::getUserActiveSubs($userID), self::getFreeFollowingProfiles($userID), [$userID]);
            $posts->where(function ($query) use ($userIds) {
                $query->whereIn('posts.user_id', $userIds)
                    ->orWhere('posts.is_free', true);
            });
        }

        if ($filterType == 'media') {
            // This guy is not really that optimal but neither bookmarks is heavy accessed
            $mediaTypes = AttachmentServiceProvider::getTypeByExtension($mediaType);
            $posts->whereHas('attachments', function ($query) use ($mediaTypes) {
                $query->whereIn('type', $mediaTypes);
            });
        }

        if ($filterType == 'access') {
            if ($mediaType === 'free') {
                $posts->where('posts.is_free', true)
                    ->where('posts.price', '<=', 0);
            } elseif ($mediaType === 'subscription') {
                $posts->where('posts.is_free', false)
                    ->where('posts.price', '<=', 0);
            } elseif ($mediaType === 'pack') {
                $posts->where('posts.price', '>', 0);
            }
        }

        if ($filterType == 'search'){
            $posts->where(
                function ($query) use ($searchTerm) {
                    $query->where('text', 'like', '%'.$searchTerm.'%')
                        ->orWhereHas('user', function ($q) use ($searchTerm) {
                            $q->where('username', 'like', '%'.$searchTerm.'%');
                            $q->orWhere('name', 'like', '%'.$searchTerm.'%');
                        });
                }
            );
        }

        if ($filterType == 'pinned'){
            $posts->orderBy('is_pinned', 'DESC');
        }

        if ($filterType == 'order'){
            if($sortOrder){
                if($sortOrder == 'top'){
                    $relationsCount = ['reactions', 'comments'];
                    $posts->withCount($relationsCount);
                    $posts->orderBy('comments_count', 'DESC');
                    $posts->orderBy('reactions_count', 'DESC');
                }
                elseif($sortOrder == 'latest'){
                    $posts->orderBy('release_date', 'DESC');
                    $posts->orderBy('created_at', 'DESC');
                }
            }
            else{
                $posts->orderBy('release_date', 'DESC');
                $posts->orderBy('created_at', 'DESC');
            }
        }

        if ($filterType == 'scheduled') {
            $posts->notExpiredAndReleased();
        }

        if ($filterType == 'approvedPostsOnly') {
            if (!(Auth::check() && (Auth::user()->role_id === 1))) { // Admin can preview all  types of posts
                $posts->where('status', Post::APPROVED_STATUS);
            }
        }

        return $posts;
    }

    /**
     * Returns all comments for a post.
     * @param $post_id
     * @param int $limit
     * @param string $order
     * @param bool $encodePostsToHtml
     * @return array|LengthAwarePaginator
     * @phpstan-return ($encodePostsToHtml is true ? array<string, mixed> : LengthAwarePaginator)
     */
    public static function getPostComments($post_id, $limit = 9, $order = 'DESC', $encodePostsToHtml = false)
    {
        $comments = PostComment::with(['author', 'reactions', 'mentions.mentionedUser'])->orderBy('created_at', $order)->where('post_id', $post_id)->paginate($limit);

        if ($encodePostsToHtml) {
            $data = [
                'total' => $comments->total(),
                'currentPage' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'prev_page_url' => $comments->previousPageUrl(),
                'next_page_url' => $comments->nextPageUrl(),
                'first_page_url' => $comments->nextPageUrl(),
                'hasMore' => $comments->hasMorePages(),
            ];
            $commentsCount = $comments->count();
            $commentsData = $comments->map(function ($comment, $index) use ($commentsCount) {
                $post = ['id' => $comment->id, 'post_id' => $comment->post->id, 'html' => View::make('elements.feed.post-comment')->with('comment', $comment)->with('isFirst', $index === $commentsCount - 1)->render()];
                return $post;
            });
            $data['comments'] = $commentsData;
        } else {
            $data = $comments;
        }

        return $data;
    }

    /**
     * Check if user has unlocked a post.
     * @param $transactions
     * @return bool
     */
    public static function hasUserUnlockedPost($transactions)
    {
        if (Auth::check()) {
            if(Auth::user()->role_id === 1) {
                return true;
            }

            foreach ($transactions as $transaction) {
                if (Auth::user()->id == $transaction->sender_user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Check if user reacted to a post / comment.
     * @param $reactions
     * @return bool
     */
    public static function didUserReact($reactions)
    {
        if (Auth::check()) {
            foreach ($reactions as $reaction) {
                if (Auth::user()->id == $reaction->user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if post is bookmarked by current user.
     * @param $bookmarks
     * @return bool
     */
    public static function isPostBookmarked($bookmarks)
    {
        if (Auth::check()) {
            foreach ($bookmarks as $bookmark) {
                if (Auth::user()->id == $bookmark->user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user is coming back to a paginated feed post from a post page.
     * @param $page
     * @return bool
     */
    public static function isComingFromPostPage($page)
    {
        if (isset($page) && is_int(strpos($page['url'], '/posts')) && !is_int(strpos($page['url'], '/posts/create'))) {
            return true;
        }

        return false;
    }

    /**
     * Get (user session) start page of the feed pagination.
     * @param $prevPage
     * @return int
     */
    public static function getFeedStartPage($prevPage)
    {
        return Cookie::get('app_feed_prev_page') && self::isComingFromPostPage($prevPage) ? Cookie::get('app_feed_prev_page') : 1;
    }

    /**
     * Get (user session) prev page of the feed pagination.
     * @param $request
     * @return mixed
     */
    public static function getPrevPage($request)
    {
        return $request->session()->get('_previous');
    }

    /**
     * Check if the pagination cookie should be deleted when navigating.
     * @param $request
     * @return bool
     */
    public static function shouldDeletePaginationCookie($request)
    {
        if (!self::isComingFromPostPage(self::getPrevPage($request))) {
            Cookie::queue(Cookie::forget('app_feed_prev_page'));
            Cookie::queue(Cookie::forget('app_prev_post'));
            return true;
        }

        return false;
    }

    /**
     * Returns count of each attachment types for user.
     * @param $userID
     * @return array
     */
    public static function getUserMediaTypesCount($userID)
    {
        $attachments = Attachment::
        leftJoin('posts', 'posts.id', '=', 'attachments.post_id')
            ->where('attachments.user_id', $userID)->where('post_id', '<>', null)
            ->where(function ($query) {
                $query->where('posts.expire_date', '>', Carbon::now());
                $query->orWhere('posts.expire_date', null);
            })
            ->where(function ($query) {
                $query->where('posts.release_date', '<', Carbon::now());
                $query->orWhere('posts.release_date', null);
            })
            ->get();
        $typeCounts = [
            'posts' => Post::query()
                ->where('user_id', $userID)
                ->where(function ($query) {
                    $query->where('expire_date', '>', Carbon::now())
                        ->orWhereNull('expire_date');
                })
                ->where(function ($query) {
                    $query->where('release_date', '<', Carbon::now())
                        ->orWhereNull('release_date');
                })
                ->count(),
            'video' => 0,
            'audio' => 0,
            'image' => 0,
        ];
        foreach ($attachments as $attachment) {
            $typeCounts[AttachmentServiceProvider::getAttachmentType($attachment->type)] += 1;
        }
        $streams = Stream::where('user_id', $userID)->where('is_public', 1)->whereIn('status', [Stream::ENDED_STATUS, Stream::IN_PROGRESS_STATUS])->count();
        $typeCounts['streams'] = $streams;
        return $typeCounts;
    }

    /**
     * Returns profile feed counters for a selected access category.
     * Media counters represent files, while the all counter represents posts.
     *
     * @param int $userID
     * @param string $accessType
     * @return array<string, int>
     */
    public static function getUserProfileFeedCounts($userID, $accessType = 'all')
    {
        $posts = Post::query()
            ->where('posts.user_id', $userID);
        $viewerIsOwner = Auth::check() && Auth::id() === (int) $userID;
        $viewerIsAdmin = Auth::check() && Auth::user()->role_id === 1;

        if (!$viewerIsOwner) {
            $posts->where(function ($query) {
                $query->where('posts.expire_date', '>', Carbon::now())
                    ->orWhereNull('posts.expire_date');
            })
            ->where(function ($query) {
                $query->where('posts.release_date', '<', Carbon::now())
                    ->orWhereNull('posts.release_date');
            });
        }

        if (!$viewerIsOwner && !$viewerIsAdmin) {
            $posts->where('posts.status', Post::APPROVED_STATUS);
        }

        if ($accessType !== 'all') {
            $posts = self::filterPosts($posts, $userID, 'access', $accessType);
        }

        $postIds = (clone $posts)->select('posts.id');
        $attachments = Attachment::query()
            ->whereIn('attachments.post_id', $postIds)
            ->get(['attachments.type']);

        $counts = [
            'posts' => (clone $posts)->count(),
            'image' => 0,
            'video' => 0,
        ];

        foreach ($attachments as $attachment) {
            $type = AttachmentServiceProvider::getAttachmentType($attachment->type);
            if (array_key_exists($type, $counts)) {
                $counts[$type]++;
            }
        }

        return $counts;
    }

    /**
     * Check if user paid for post.
     * @param $userId
     * @param $postId
     * @return bool
     */
    public static function userPaidForPost($userId, $postId) {
        return Transaction::query()->where(
            [
                    'post_id' => $postId,
                    'sender_user_id' => $userId,
                    'type' => Transaction::POST_UNLOCK,
                    'status' => Transaction::APPROVED_STATUS,
                ]
        )->first() != null;
    }

    /**
     * Check if user paid for stream access.
     * @param $userId
     * @param $streamId
     * @return bool
     */
    public static function userPaidForStream($userId, $streamId) {
        return Transaction::query()->where(
            [
                    'stream_id' => $streamId,
                    'sender_user_id' => $userId,
                    'type' => Transaction::STREAM_ACCESS,
                    'status' => Transaction::APPROVED_STATUS,
                ]
        )->first() != null;
    }

    /**
     * Checks if user paid access for this message.
     * @param $userId
     * @param $messageId
     * @return bool
     */
    public static function userPaidForMessage($userId, $messageId) {
        return Transaction::query()->where(
            [
                    'user_message_id' => $messageId,
                    'sender_user_id' => $userId,
                    'type' => Transaction::MESSAGE_UNLOCK,
                    'status' => Transaction::APPROVED_STATUS,
                ]
        )->first() != null;
    }

    /**
     * Returns number of approved posts.
     * @param $userID
     * @return mixed
     */
    public static function getUserApprovedPostsCount($userID) {
        return $postsCount = Post::where([
            'user_id' =>  $userID,
            'status' => Post::APPROVED_STATUS,
        ])->count();
    }

    public static function getPostsCountLeftTillAutoApprove($userID) {
        return (int)getSetting('compliance.admin_approved_posts_limit') - self::getUserApprovedPostsCount(Auth::user()->id);
    }

    /**
     * Returns the default status for post to be created
     * If admin_approved_posts_limit is > 0, user must have had more posts than that number
     * Otherwise, post goes to pending state.
     * @return int
     */
    public static function getDefaultPostStatus($userID) {
        $postStatus = Post::APPROVED_STATUS;
        if(getSetting('compliance.admin_approved_posts_limit')){
            $postsCount = self::getUserApprovedPostsCount($userID);
            if((int)getSetting('compliance.admin_approved_posts_limit') > $postsCount){
                $postStatus = Post::PENDING_STATUS;
            }
        }
        return $postStatus;
    }

    /**
     * Counts types of media for a post attachments.
     * @param $attachments
     * @return array
     */
    public static function getAttachmentsTypesCount($attachments) {
        $counts = [
            'image' => 0,
            'video' => 0,
            'audio' => 0,
        ];
        foreach($attachments as $attachment){
            $attachmentType = AttachmentServiceProvider::getAttachmentType($attachment->type);
            if(isset($counts[$attachmentType])){
                $counts[$attachmentType] += 1;
            }
        }
        return $counts;
    }

    /**
     * Determines if a post has no media attachments.
     * @param $attachments
     * @return bool
     */
    public static function hasNoMedia($attachments): bool {
        $counts = self::getAttachmentsTypesCount($attachments);
        return array_sum($counts) === 0;
    }

    /**
     * Sends post-notifications to users.
     *
     * @param Post $post
     * @return void
     */
    public static function sendPostNotifications(Post $post): void
    {
        $post->loadMissing('user');
        if (!$post->user) {
            return;
        }

        $originalLocale = App::getLocale();

        // Grabbing followers
        $followers = ListsHelperServiceProvider::getUserFollowers($post->user_id);
        // Sending them email notifications, if site & user settings allows it
        foreach($followers as $follower){
            $serializedSettings = json_decode($follower['settings']);
            if(isset($serializedSettings->notification_email_new_post_created) && $serializedSettings->notification_email_new_post_created == 'true'){
                App::setLocale($serializedSettings->locale ?? $originalLocale);

                try {
                    EmailsServiceProvider::sendGenericEmail(
                        [
                            'email' => $follower['email'],
                            'subject' => __('New content from @:username', ['username' => $post->user->username]),
                            'title' => __('Hello, :name,', ['name'=>$follower['name']]),
                            'content' => __('New content from people you follow is available', ['siteName'=>getSetting('site.name')]),
                            'button' => [
                                'text' => __('View your feed'),
                                'url' => route('feed'),
                            ],
                        ]
                    );
                } finally {
                    App::setLocale($originalLocale);
                }
            }
        }
    }

    /**
     * Sends admin notifications on posts to be approved.
     * @return void
     */
    public static function sendAdminPostsApprovalNotifications()
    {
        // Sending out admin email
        $adminEmails = User::where('role_id', 1)->select(['email', 'name'])->get();
        foreach ($adminEmails as $user) {
            EmailsServiceProvider::sendGenericEmail(
                [
                    'email' => $user->email,
                    'subject' => __('Action required | New post pending approval'),
                    'title' => __('Hello, :name,', ['name' => $user->name]),
                    'content' => __('There is a new post pending your approval on :siteName.', ['siteName' => getSetting('site.name')]),
                    'button' => [
                        'text' => __('Go to admin'),
                        // TODO: Review filter
                        'url' => url()->route('filament.admin.resources.posts.index').'?tableFilters[queryBuilder][rules][O1Dc][type]=status&tableFilters[queryBuilder][rules][O1Dc][data][operator]=equals&tableFilters[queryBuilder][rules][O1Dc][data][settings][text]=0',
                    ],
                ]
            );
        }
    }

    /**
     * Creates a new poll.
     * @param $postID
     * @param $pollAnswers
     * @return true
     */
    public static function createNewPoll($postID, $pollAnswers)
    {
        $pollID = Poll::create([
            'user_id' => Auth::user()->id,
            'post_id' => $postID,
            'ends_at' => null,
        ])->id;
        foreach($pollAnswers as $pollAnswer){
            PollAnswer::create([
                'poll_id' => $pollID,
                'answer' => $pollAnswer['value'],
            ]);
        }
        return true;
    }

    /**
     * Update existing poll.
     * @param $post
     * @param $pollAnswers
     * @return true
     */
    public static function updatePoll($post, $pollAnswers)
    {
        // Get existing answers keyed by ID
        $existingAnswers = $post->poll->answers->keyBy('id');

        // Loop over answers from the request
        foreach ($pollAnswers as $answerData) {
            $answerId = $answerData['id'] ?? null;
            $answerValue = $answerData['value'];

            if ($answerId && $existingAnswers->has($answerId)) {
                // Update existing
                $existingAnswers[$answerId]->update([
                    'answer' => $answerValue,
                ]);
                // Remove from the list so it's not deleted
                $existingAnswers->forget($answerId);

            } else {
                // Create new
                PollAnswer::create([
                    'poll_id' => $post->poll->id,
                    'answer'  => $answerValue,
                ]);
            }
        }

        // Delete leftovers
        foreach ($existingAnswers as $toDelete) {
            $toDelete->delete();
        }

        return true;
    }

    /**
     * Checks if user has voted in a particular poll
     * If so, returns that poll answer id.
     * @param $pollID
     * @return int|null
     */
    public static function hasUserVotedInPoll($pollID)
    {
        if(Auth::check()) {
            $pollAnswer = PollUserAnswer::where('user_id', Auth::user()->id)->where('poll_id', $pollID)->first();
            if ($pollAnswer) {
                return $pollAnswer->answer->id;
            }
        }
        return null;
    }

    /**
     * Returns a wrap up of a posts
     * EG: Aggregates and percentages on answers.
     * @param $poll
     * @return \Illuminate\Support\Collection
     */
    public static function getPollResults($poll)
    {
        // 1) Count the total votes across all answers
        $totalVotes = $poll->answers->reduce(function ($carry, $answer) {
            return $carry + $answer->votes->count();
        }, 0);

        // 2) Map each answer to an array containing votes & percentage
        $results = $poll->answers->map(function ($answer) use ($totalVotes) {
            $votesCount = $answer->votes->count();
            return [
                'id'        => $answer->id,
                'answer'    => $answer->answer,
                'votes'     => $votesCount,
                'percentage'=> $totalVotes > 0
                    ? round(($votesCount / $totalVotes) * 100, 2)
                    : 0,
            ];
        });
        return collect([
            'totalVotes' => $totalVotes,
            'answers' => $results,
        ]);
    }

    public static function isPostSubscriptionUnlocked($post) {
        return (bool) $post->is_free
            || $post->isSubbed
            || ProfileMonetizationServiceProvider::userHasOpenProfile($post->user);
    }

    public static function shouldHidePostText($post) {
        $isLoggedIn = Auth::check();
        $isOwner = $isLoggedIn && Auth::id() === $post->user_id;
        $isSubscriptionUnlocked = self::isPostSubscriptionUnlocked($post);
        $isPPVLocked = (!$isOwner && $post->price > 0 && (!$isLoggedIn || !self::hasUserUnlockedPost($post->postPurchases)));
        $isTextPreviewDisabled = getSetting('feed.disable_posts_text_preview');
        $shouldHideText = $isTextPreviewDisabled && ($isPPVLocked || !$isSubscriptionUnlocked);
        return $shouldHideText;
    }

    public static function renderPostText(Post $post): string
    {
        $clean = GenericHelperServiceProvider::parseSafeHTML($post->text ?? '');

        $enableHashtags = (bool) getSetting('feed.enable_hashtags');
        $enableMentions = (bool) getSetting('feed.enable_mentions');

        $valid = [];

        if ($enableMentions && $post->relationLoaded('mentions')) {
            foreach ($post->mentions as $m) {
                if ($m->mentionedUser && $m->mentionedUser->username) {
                    $username = $m->mentionedUser->username;
                    $valid[Str::lower($username)] = $username; // lowercase key => canonical username
                }
            }
        }

        return self::linkifyHashtagsAndMentions($clean, $valid, $enableHashtags, $enableMentions);
    }

    public static function renderCommentText(PostComment $comment): string
    {
        $clean = GenericHelperServiceProvider::parseSafeHTML($comment->message ?? '');

        $enableHashtags = (bool) getSetting('feed.enable_hashtags');
        $enableMentions = (bool) getSetting('feed.enable_mentions');

        $valid = [];

        if ($enableMentions && $comment->relationLoaded('mentions')) {
            foreach ($comment->mentions as $m) {
                if ($m->mentionedUser && $m->mentionedUser->username) {
                    $username = $m->mentionedUser->username;
                    $valid[Str::lower($username)] = $username; // important for matching + URL building
                }
            }
        }

        return self::linkifyHashtagsAndMentions($clean, $valid, $enableHashtags, $enableMentions);
    }

    private static function linkifyHashtagsAndMentions(
        string $html,
        array $validMentionsSet = [],
        bool $enableHashtags = true,
        bool $enableMentions = true
    ): string {
        if ($enableHashtags) {
            $html = preg_replace_callback(
                '/(^|[\s(>])#([A-Za-z0-9_]{1,64})(?![A-Za-z0-9_])/u',
                function ($m) {
                    $lead = $m[1];
                    $tag = $m[2];
                    $tagLower = Str::lower($tag);

                    $href = route('search.get', ['filter' => 'top', 'query' => '#'.$tagLower]);

                    return $lead.'<a href="'.e($href).'" class="hashtag" rel="nofollow noopener">#'.e($tag).'</a>';
                },
                $html
            );
        }

        if ($enableMentions) {
            $html = preg_replace_callback(
                '/(^|[\s(>])@([A-Za-z0-9_-]{1,255})(?![A-Za-z0-9_-])/u',
                function ($m) use ($validMentionsSet) {
                    $lead = $m[1];
                    $typed = $m[2];
                    $key = Str::lower($typed);

                    if (!isset($validMentionsSet[$key])) {
                        return $lead.'@'.e($typed);
                    }

                    $canonical = $validMentionsSet[$key];
                    $href = url('/'.rawurlencode($canonical));

                    return $lead.'<a href="'.e($href).'" class="mention" rel="nofollow noopener">@'.e($typed).'</a>';
                },
                $html
            );
        }

        return $html;
    }

    // TODO: Expose $limit as admin setting
    public static function getTopHashtags(int $limit = 10, int $cacheSeconds = 300): array
    {
        $limit = max(1, min(50, $limit));

        $mode = (string) (getSetting('site.explore_mode') ?? 'public');

        $days = (int) (getSetting('feed.popular_hashtags_days') ?? 14);
        $days = $days > 0 ? $days : null;

        $viewerId = Auth::id(); // null if guest

        // Cache key must vary by viewer for paywall mode
        $cacheKey = 'hashtags:top:'
            .$limit
            .':days:'.($days ?? 'all')
            .':mode:'.$mode
            .':viewer:'.($mode === 'paywall' ? ($viewerId ?? 'guest') : 'all');

        return Cache::remember($cacheKey, $cacheSeconds, function () use ($limit, $days, $mode, $viewerId) {
            $q = HashtagLink::query()->from('hashtag_links');

            // window
            if ($days !== null) {
                $q->where('hashtag_links.created_at', '>=', Carbon::now()->subDays($days));
            }

            // paywall filtering
            if ($mode === 'paywall') {
                if (!$viewerId) {
                    return []; // paywall explore: guests see no hashtag widget
                }

                $allowedAuthorIds = array_values(array_unique(array_merge(
                    self::getUserActiveSubs($viewerId),
                    self::getFreeFollowingProfiles($viewerId),
//                    [$viewerId]
                )));

                // Link can point to post OR post_comment. For comments, author is the parent post's user_id.
                $q->leftJoin('posts as p', 'p.id', '=', 'hashtag_links.post_id')
                    ->leftJoin('post_comments as pc', 'pc.id', '=', 'hashtag_links.post_comment_id')
                    ->leftJoin('posts as cp', 'cp.id', '=', 'pc.post_id');

                $q->where(function ($w) use ($allowedAuthorIds) {
                    $w->whereIn('p.user_id', $allowedAuthorIds)
                        ->orWhereIn('cp.user_id', $allowedAuthorIds);
                });
            }

            $top = $q->selectRaw('hashtag_links.hashtag_id, COUNT(*) as uses')
                ->groupBy('hashtag_links.hashtag_id')
                ->orderByDesc('uses')
                ->limit($limit)
                ->get();

            if ($top->isEmpty()) {
                return [];
            }

            $tags = Hashtag::query()
                ->whereIn('id', $top->pluck('hashtag_id')->all())
                ->get(['id', 'tag'])
                ->keyBy('id');

            $out = [];
            foreach ($top as $row) {
                $h = $tags->get($row->hashtag_id);
                if (!$h) {
                    continue;
                }

                $out[] = [
                    'tag'  => $h->tag,
                    'uses' => (int) $row->getAttribute('uses'),
                ];
            }

            return $out;
        });
    }
}
