@php
    $profileFilterUrl = static function ($media, $access, $view) use ($user) {
        $query = [];

        if (in_array($media, ['image', 'video', 'reels'], true)) {
            $query['filter'] = $media;
        }
        if ($media !== 'reels' && in_array($access, ['free', 'subscription', 'pack'], true)) {
            $query['access'] = $access;
        }
        if ($media !== 'reels' && $view === 'grid') {
            $query['view'] = 'grid';
        }

        return route('profile', ['username' => $user->username])
            . ($query ? '?'.http_build_query($query) : '');
    };
    $selectedMedia = in_array($activeFilter, ['image', 'video', 'reels'], true) ? $activeFilter : null;
@endphp

<div class="profile-feed-navigation mt-3 inline-border-tabs">
    <nav class="profile-media-tabs" aria-label="Tipos de mídia">
        <a class="profile-media-tab {{$selectedMedia === null ? 'active' : ''}}" href="{{$profileFilterUrl(null, $accessFilter, $profileFeedView)}}">
            <span>Todos</span>
            <strong>({{short_number($profileFeedCounts['posts'])}})</strong>
        </a>
        <a class="profile-media-tab {{$selectedMedia === 'image' ? 'active' : ''}}" href="{{$profileFilterUrl('image', $accessFilter, $profileFeedView)}}">
            <span>Fotos</span>
            <strong>({{short_number($profileFeedCounts['image'])}})</strong>
        </a>
        <a class="profile-media-tab {{$selectedMedia === 'video' ? 'active' : ''}}" href="{{$profileFilterUrl('video', $accessFilter, $profileFeedView)}}">
            <span>Vídeos</span>
            <strong>({{short_number($profileFeedCounts['video'])}})</strong>
        </a>
        @if(getSetting('reels.reels_enabled'))
            <a class="profile-media-tab {{$selectedMedia === 'reels' ? 'active' : ''}}" href="{{$profileFilterUrl('reels', 'all', 'list')}}">
                <span>Reels</span>
                <strong>({{short_number($filterTypeCounts['reels'] ?? 0)}})</strong>
            </a>
        @endif
    </nav>

    @if($activeFilter !== 'reels')
        <div class="profile-feed-toolbar">
            <nav class="profile-access-tabs" aria-label="Acesso às postagens">
                <a class="profile-access-tab {{$accessFilter === 'all' ? 'active' : ''}}" href="{{$profileFilterUrl($selectedMedia, 'all', $profileFeedView)}}">Ver tudo</a>
                <a class="profile-access-tab {{$accessFilter === 'free' ? 'active' : ''}}" href="{{$profileFilterUrl($selectedMedia, 'free', $profileFeedView)}}">Gratuito</a>
                <a class="profile-access-tab {{$accessFilter === 'subscription' ? 'active' : ''}}" href="{{$profileFilterUrl($selectedMedia, 'subscription', $profileFeedView)}}">Assinatura</a>
                <a class="profile-access-tab {{$accessFilter === 'pack' ? 'active' : ''}}" href="{{$profileFilterUrl($selectedMedia, 'pack', $profileFeedView)}}">Packs</a>
            </nav>

            <div class="profile-view-tabs" aria-label="Visualização do feed">
                <a class="profile-view-tab {{$profileFeedView === 'grid' ? 'active' : ''}}" href="{{$profileFilterUrl($selectedMedia, $accessFilter, 'grid')}}" title="Visualização em grade" aria-label="Visualização em grade">
                    @include('elements.icon',['icon'=>'grid-outline','centered'=>true])
                </a>
                <a class="profile-view-tab {{$profileFeedView === 'list' ? 'active' : ''}}" href="{{$profileFilterUrl($selectedMedia, $accessFilter, 'list')}}" title="Visualização em lista" aria-label="Visualização em lista">
                    @include('elements.icon',['icon'=>'list-outline','centered'=>true])
                </a>
            </div>
        </div>
    @endif
</div>

<div class="profile-feed-content profile-feed-view-{{$profileFeedView}} justify-content-center align-items-center {{(Cookie::get('app_feed_prev_page') && PostsHelper::isComingFromPostPage(request()->session()->get('_previous'))) ? 'mt-3' : 'mt-4'}}">
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
