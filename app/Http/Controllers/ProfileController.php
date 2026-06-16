<?php

namespace App\Http\Controllers;

use App\Model\Country;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\ListsHelperServiceProvider;
use App\Providers\ProfileMonetizationServiceProvider;
use App\Providers\PostsHelperServiceProvider;
use App\Providers\ReelsServiceProvider;
use App\Providers\StreamsServiceProvider;
use Carbon\Carbon;
use Cookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use JavaScript;
use Session;

class ProfileController extends Controller
{
    protected $user;

    protected $hasSub = false;

    protected $isOwner = false;

    protected $isPublic = false;

    protected $viewerHasChatAccess = false;

    public function __construct(Request $request)
    {
        $username = $request->route('username');
        $this->user = PostsHelperServiceProvider::getUserByUsername($username);
    }

    /**
     * Renders the main profile page & first feed posts, if available.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function index(Request $request)
    {
        // Forcing no cache, in order to be able to return from post over
        // profile w/o saving state, and be able to paginate from where we left of
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
        header('Pragma: no-cache'); // HTTP 1.0.
        header('Expires: 0 '); // Proxies.

        // Valid profile sluck checker
        if (!$this->user) {
            abort(404);
        }

        // General access rules
        $this->setAccessRules();
        if (!$this->user->public_profile && !Auth::check()) {
            abort(403, __('Profile access is denied.'));
        }

        // Geoblocking rule
        if($this->isGeoLocationBlocked()){
            abort(403, __('Profile access is denied.'));
        }

        $data['showLoginDialog'] = false;
        $errors = session()->get('errors', app(ViewErrorBag::class));
        if ($errors->getBag('default')->has('email') || $errors->getBag('default')->has('name') || $errors->getBag('default')->has('password')) {
            $data['showLoginDialog'] = true;
        }

        $postsFilter = $request->get('filter') ? $request->get('filter') : false;
        $postsFilter = in_array($postsFilter, ['image', 'video', 'reels', 'streams'], true) ? $postsFilter : false;
        $accessFilter = in_array($request->get('access'), ['free', 'subscription', 'pack'], true)
            ? $request->get('access')
            : 'all';
        $profileFeedView = $request->get('view') === 'grid' ? 'grid' : 'list';
        $postMediaFilter = in_array($postsFilter, ['image', 'video'], true) ? $postsFilter : false;
        $startPage = PostsHelperServiceProvider::getFeedStartPage(PostsHelperServiceProvider::getPrevPage($request));
        $posts = PostsHelperServiceProvider::getUserPosts($this->user->id, false, $startPage, $postMediaFilter, $this->hasSub, $accessFilter);
        PostsHelperServiceProvider::shouldDeletePaginationCookie($request);
        $posts = $posts->appends($_GET);
        $offer = [];
        if ($this->user->offer && !getSetting('profiles.disable_profile_offers')) {
            $discount30 = ($this->user->profile_access_price && $this->user->offer->old_profile_access_price) ? 100 - (($this->user->profile_access_price * 100) / $this->user->offer->old_profile_access_price) : 100;
            $discount90 = 100 - (($this->user->profile_access_price_3_months * 100) / ($this->user->offer->old_profile_access_price_3_months ?: 1));
            $discount182 = 100 - (($this->user->profile_access_price_6_months * 100) / ($this->user->offer->old_profile_access_price_6_months ?: 1));
            $discount365 = 100 - (($this->user->profile_access_price_12_months * 100) / ($this->user->offer->old_profile_access_price_12_months ?: 1));
            $expiringDate = $this->user->offer->expires_at;
            $currentDate = Carbon::now();
            if ($expiringDate > $currentDate) {
                $offer = [
                    'discountAmount' => [
                        '30' => $discount30,
                        '90' => $discount90,
                        '182' => $discount182,
                        '365' => $discount365,
                    ],
                    'daysRemaining' => (int)$currentDate->diffInDays($expiringDate),
                    'expiresAt' => $expiringDate,
                ];
            }
        }

        $data = array_merge($data, [
            'user' => $this->user,
            'hasSub' => $this->hasSub,
            'posts' => $posts,
            'activeFilter' => $postsFilter,
            'accessFilter' => $accessFilter,
            'profileFeedView' => $profileFeedView,
            'filterTypeCounts' => PostsHelperServiceProvider::getUserMediaTypesCount($this->user->id),
            'profileFeedCounts' => PostsHelperServiceProvider::getUserProfileFeedCounts($this->user->id, $accessFilter),
            'offer'=> $offer,
            'viewerHasChatAccess'=> $this->viewerHasChatAccess,
        ]);
        $data['filterTypeCounts']['reels'] = getSetting('reels.reels_enabled')
            ? ReelsServiceProvider::profileCount($request->user(), $this->user)
            : 0;

        $streams = null;
        if($postsFilter == 'streams'){
            $streams = StreamsServiceProvider::getPublicStreams(['userId' => $this->user->id, 'status' => 'all']);
            $data['streams'] = $streams;
        }
        $data['hasActiveStream'] = StreamsServiceProvider::getUserInProgressStream(true, $this->user->id) ? true : false;

        $data['recentMedia'] = false;
        if ($this->hasSub || (Auth::check() && Auth::user()->id == $this->user->id) || ProfileMonetizationServiceProvider::userHasOpenProfile($this->user)) {
            $data['recentMedia'] = PostsHelperServiceProvider::getLatestUserAttachments($this->user->id, 'image');
        }

        $additionalAssets = ['js' => [], 'css' => []];
        if(getSetting('profiles.allow_profile_qr_code')){
            $additionalAssets['js'][] = '/libs/easyqrcodejs/dist/easy.qrcode.min.js';
        }

        if(getSetting('stories.stories_enabled')){
            $additionalAssets['js'][] = '/js/stories/stories-player.js';
            $additionalAssets['js'][] = '/js/stories/stories-swiper.js';
            $additionalAssets['js'][] = '/js/stories/stories-profile.js';
            $additionalAssets['js'][] = '/js/messenger/messenger-modal-dm.js';
            $additionalAssets['css'][] = '/css/stories.css';
        }

        if(getSetting('reels.reels_enabled') && $postsFilter === 'reels'){
            $additionalAssets['js'][] = '/js/reels/reels-api.js';
            $additionalAssets['js'][] = '/js/reels/reels-renderer.js';
            $additionalAssets['js'][] = '/js/reels/reels-comments.js';
            $additionalAssets['js'][] = '/js/reels/reels-player.js';
            $additionalAssets['css'][] = '/css/pages/reels.css';
        }

        $data['additionalAssets'] = $additionalAssets;

        $profilePostsRoute = route('profile.posts', ['username' => $this->user->username]);
        $profileRoute = route('profile', ['username' => $this->user->username]);
        $toProfilePostsRoute = static function ($url) use ($profilePostsRoute, $profileRoute) {
            return $url ? str_replace($profileRoute, $profilePostsRoute, $url) : null;
        };

        $paginatorConfig = [
            'next_page_url' => $toProfilePostsRoute($posts->nextPageUrl()),
            'prev_page_url' => $toProfilePostsRoute($posts->previousPageUrl()),
            'current_page' => $posts->currentPage(),
            'total' => $posts->total(),
            'per_page' => $posts->perPage(),
            'hasMore' => $posts->hasMorePages(),
        ];

        if($streams) {
            $paginatorConfig = [
                'next_page_url' => str_replace(['?page=', '?filter='], ['/streams?page=', '/streams?filter='], $streams->nextPageUrl()),
                'prev_page_url' => str_replace(['?page=', '?filter='], ['/streams?page=', '/streams?filter='], $streams->previousPageUrl()),
                'current_page' => $streams->currentPage(),
                'total' => $streams->total(),
                'per_page' => $streams->perPage(),
                'hasMore' => $streams->hasMorePages(),
            ];
        }

        // Seo description for share urls
        $rawDescription = getSetting('profiles.allow_profile_bio_markdown') && $this->user->bio ? strip_tags(GenericHelperServiceProvider::parseProfileMarkdownBio($this->user->bio)) : $this->user->bio;
        $data['seo_description'] = $rawDescription ? str_replace(["\n", "\r"], ' ', substr($rawDescription, 0, 90)).(strlen($rawDescription) > 90 ? '...' : '') : null;

        Session::put('lastProfileUrl', $request->fullUrl());

        $initialPostIDs = collect($posts->items())->pluck('id')->toArray();

        if ($request->ajax() && $request->get('partial') === 'profile-feed') {
            return response()->json([
                'success' => true,
                'html' => view('elements.profile.profile-feed', $data)->render(),
                'paginatorConfig' => $paginatorConfig,
                'initialPostIDs' => $initialPostIDs,
                'postsFilter' => $postsFilter ?: false,
                'url' => $request->fullUrlWithoutQuery('partial'),
            ]);
        }

        JavaScript::put([
            'paginatorConfig' => $paginatorConfig,
            'messengerVars' => [
                'bootFullMessenger' => false,
            ],
            'initialPostIDs' => $initialPostIDs,
            'profileVars' => [
                'user_id' =>  $this->user->id,
                'username' => $this->user->username,
            ],
            'showLoginDialog' => $data['showLoginDialog'],
            'postsFilter' => $postsFilter,
            'storiesDeepLink' => request()->filled('story')
                ? ['story_id' => (int) request('story')]
                : null,
            'showDisabledPaywallWarning' => (Auth::check() && Auth::user()->role_id === 1 && Auth::user()->id !== $this->user->id) ? true : false,
        ]);

        // Stories
        $storiesEnabled = (bool) getSetting('stories.stories_enabled');
        $allowHighlights = (bool) getSetting('stories.allow_highlights');
        $viewer = $request->user(); // may be null
        $hasHighlights = false;
        if ($storiesEnabled) {
            // highlights check only if feature enabled
            if ($allowHighlights) {
                $hasHighlights = \App\Providers\StoriesServiceProvider::hasHighlightsForViewer($viewer, $this->user);
            }
        }

        $data = array_merge($data, [
            'storiesEnabled' => $storiesEnabled,
            'allowHighlights' => $allowHighlights,
            'hasHighlights' => $hasHighlights,
        ]);

        // Social links
        $data['profileSocialLinks'] = [];
        if(getSetting('profiles.social_links_enabled')) {
            $profileSocialLinks = GenericHelperServiceProvider::getProfileSocialLinkItems($this->user);
            $data['profileSocialLinks'] = $profileSocialLinks;
        }

        // Spotify widget
        if(getSetting('profiles.spotify_enabled')){
            $spotifyAccount = \App\Model\UserSpotifyAccount::where('user_id', $this->user->id)->first();
            $spotifyAnthem = null;
            if ($spotifyAccount && $spotifyAccount->anthem_track_id) {
                try {
                    $spotify = app(\App\Services\SpotifyService::class);
                    $track = $spotify->apiGet($spotifyAccount, '/tracks/'.$spotifyAccount->anthem_track_id);

                    $spotifyAnthem = [
                        'id' => $track['id'] ?? null,
                        'name' => $track['name'] ?? null,
                        'artist' => data_get($track, 'artists.0.name'),
                        'image' => data_get($track, 'album.images.0.url'),
                        'url' => data_get($track, 'external_urls.spotify'),
                    ];
                } catch (\Exception $e) {
                    $spotifyAnthem = null;
                }
            }
            $data['spotifyAnthem'] = $spotifyAnthem;
            $data['spotifyAccount'] = $spotifyAccount;
        }

        return view('pages.profile', $data);
    }

    /**
     * Fetches user posts, to be paginated into the profile page.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPosts(Request $request)
    {
        $this->setAccessRules();
        $postsFilter = $request->get('filter') ? $request->get('filter') : false;
        $postsFilter = in_array($postsFilter, ['image', 'video'], true) ? $postsFilter : false;
        $accessFilter = in_array($request->get('access'), ['free', 'subscription', 'pack'], true)
            ? $request->get('access')
            : 'all';

        return response()->json([
            'success' => true,
            'data' => PostsHelperServiceProvider::getUserPosts($this->user->id, true, false, $postsFilter, $this->hasSub, $accessFilter),
        ]);
    }

    /**
     * Fetches paginated user (public) streams.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStreams(Request $request) {
        $this->setAccessRules();
        return response()->json([
            'success'=>true,
            'data'=>StreamsServiceProvider::getPublicStreams(['encodePostsToHtml'=>true, 'status' => 'all', 'showUsername'=>false]),
        ]);
    }

    public function getUserReels(Request $request)
    {
        if (!getSetting('reels.reels_enabled')) abort(404);

        $this->setAccessRules();

        $limit = (int) ($request->get('limit') ?: 10);
        $limit = max(1, min($limit ?: 10, 30));
        $offset = max(0, (int) $request->get('offset', 0));

        $reels = ReelsServiceProvider::forProfile($request->user(), $this->user, $limit + 1, $offset);
        $hasMore = $reels->count() > $limit;
        $reels = $reels->take($limit)->values();

        return response()->json(array_merge(
            ReelsServiceProvider::toFrontendPayload($reels, $request->user()),
            [
                'has_more' => $hasMore,
                'next_offset' => $offset + $reels->count(),
            ]
        ));
    }

    /**
     * Checks if current logged user (if any) has rights to view the profile media.
     */
    protected function setAccessRules()
    {
        $viewerUser = null;
        if (Auth::check()) {
            $viewerUser = Auth::user();
        }
        if ($viewerUser) {
            $this->hasSub = PostsHelperServiceProvider::hasActiveSub($viewerUser->id, $this->user->id);
            if ($viewerUser->id === $this->user->id) {
                $this->hasSub = true;
                $this->isOwner = true;
                $this->viewerHasChatAccess = true;
            }
            if(!ProfileMonetizationServiceProvider::userHasPaidProfile($this->user) && ListsHelperServiceProvider::loggedUserIsFollowingUser($this->user->id)){
                $this->hasSub = true;
                $this->viewerHasChatAccess = true;
            }
            if(ProfileMonetizationServiceProvider::userHasOpenProfile($this->user) && ListsHelperServiceProvider::loggedUserIsFollowingUser($this->user->id)){
                $this->hasSub = true;
                $this->viewerHasChatAccess = true;
            }
            if($viewerUser->role_id === 1){
                // These only act upon profile page itself, not feed items, so disabling them allowing admins to sub as well if they want
                // It was creating confusion before
//                $this->hasSub = true;
//                $this->isOwner = true;
                $this->viewerHasChatAccess = true;
            }
        }
    }

    protected function isGeoLocationBlocked() {
        if(Auth::check() && Auth::user()->role_id === 1){
            return false;
        }
        if(getSetting('security.allow_geo_blocking') && getSetting('security.abstract_api_key')){
            if($this->user->enable_geoblocking){
                if(isset($this->user->settings['geoblocked_countries'])){
                    $countries = json_decode($this->user->settings['geoblocked_countries']);
                    $blockedCountries = Country::whereIn('name', $countries)->get();
                    $client = new \GuzzleHttp\Client();
                    $apiRequest = $client->get('https://ipgeolocation.abstractapi.com/v1/?api_key='.getSetting('security.abstract_api_key').'&ip_address='.$_SERVER['REMOTE_ADDR']);
                    $apiData = json_decode($apiRequest->getBody()->getContents());
                    foreach($blockedCountries as $country){
                        if($country->country_code == $apiData->country_code){
                            if(!(Auth::check() && Auth::user()->id === $this->user->id)){
                                return true;
                            }
                        }
                    }

                }
            }
        }
        return false;
    }
}
