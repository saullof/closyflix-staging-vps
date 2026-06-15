<div class="mt-3 inline-border-tabs">
    <nav class="nav nav-pills nav-justified text-bold">
        <a class="nav-item nav-link {{$activeFilter == false ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username])}}">{{trans_choice('posts', $posts->total(), ['number'=> short_number($posts->total()) ])}} </a>

        @if($filterTypeCounts['image'] > 0)
            <a class="nav-item nav-link {{$activeFilter == 'image' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=image'}}">{{trans_choice('images', $filterTypeCounts['image'], ['number'=> short_number($filterTypeCounts['image'])])}}</a>
        @endif

        @if($filterTypeCounts['video'] > 0)
            <a class="nav-item nav-link {{$activeFilter == 'video' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=video'}}">{{trans_choice('videos', $filterTypeCounts['video'], ['number'=> short_number($filterTypeCounts['video'])])}}</a>

        @endif

        @if($filterTypeCounts['audio'] > 0)
            <a class="nav-item nav-link {{$activeFilter == 'audio' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=audio'}}">{{trans_choice('audio', $filterTypeCounts['audio'], ['number'=> short_number($filterTypeCounts['audio'])])}}</a>
        @endif

        @if(getSetting('streams.streaming_driver') !== 'none')
            @if(isset($filterTypeCounts['streams']) && $filterTypeCounts['streams'] > 0)
                <a class="nav-item nav-link {{$activeFilter == 'streams' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=streams'}}"> {{short_number($filterTypeCounts['streams'])}} {{trans_choice('streams', $filterTypeCounts['streams'], ['number'=> short_number($filterTypeCounts['streams'])])}}</a>
            @endif
        @endif

        @if(getSetting('reels.reels_enabled') && isset($filterTypeCounts['reels']) && ($filterTypeCounts['reels'] > 0 || (Auth::check() && Auth::user()->id === $user->id)))
            <a class="nav-item nav-link {{$activeFilter == 'reels' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=reels'}}"> {{short_number($filterTypeCounts['reels'])}} {{__('Reels')}}</a>
        @endif

    </nav>
</div>
<div class="justify-content-center align-items-center {{(Cookie::get('app_feed_prev_page') && PostsHelper::isComingFromPostPage(request()->session()->get('_previous'))) ? 'mt-3' : 'mt-4'}}">
    @if($activeFilter === 'reels')
        <div id="reels-feed"
             class="reels-feed profile-reels-feed"
             data-feed-url="{{ route('profile.reels', ['username' => $user->username]) }}"
             data-base-url="{{ route('profile', ['username' => $user->username]) . '?filter=reels' }}"
             @if(Auth::check() && Auth::user()->id === $user->id)
                 data-empty-action-url="{{ route('reels.create') }}"
                 data-empty-action-label="{{ __('Create your reel') }}"
             @endif
             data-allow-progress-scrubbing="{{ getSetting('reels.allow_progress_scrubbing') ? 1 : 0 }}"
             data-permalink-template="{{ route('reels.get', ['reel_id' => '__REEL_ID__']) }}">
            <div class="reels-empty-state">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    @elseif($activeFilter !== 'streams')
        @include('elements.feed.posts-load-more', ['classes' => 'mb-2'])
        <div class="feed-box mt-0 posts-wrapper">
            @include('elements.feed.posts-wrapper',['posts'=>$posts])
        </div>
    @else
        <div class="streams-box mt-4 streams-wrapper mb-4">
            @include('elements.search.streams-wrapper',['streams'=>$streams,'showLiveIndicators'=>true, 'showUsername' => false])
        </div>
    @endif
    @include('elements.feed.posts-loading-spinner')
</div>
