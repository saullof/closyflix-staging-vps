@php
    $isDarkTheme = Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark');
@endphp

<div class="side-menu px-1 px-md-2 px-lg-3 {{$isDarkTheme ? 'side-menu-theme-dark' : 'side-menu-theme-light'}}">
    <div class="user-details mb-4 d-flex open-menu pointer-cursor flex-row-no-rtl">
        <div class="ml-0 ml-md-2">
            @if(Auth::check())
                <img src="{{Auth::user()->avatar}}" class="rounded-circle user-avatar">
            @else
                <div class="avatar-placeholder">
                    @include('elements.icon',['icon'=>'person-circle','variant'=>'xlarge text-muted'])
                </div>
            @endif
        </div>
        @if(Auth::check())
            <div class="d-none d-lg-block overflow-hidden">
                <div class="pl-2 d-flex justify-content-center flex-column overflow-hidden">
                    <div class="ml-2 d-flex flex-column overflow-hidden">
                        <span class="text-bold text-truncate {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{Auth::user()->name}}</span>
                        <span class="text-muted"><span>@</span>{{Auth::user()->username}}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <ul class="nav flex-column user-side-menu">
        <li class="nav-item ">
            <a href="{{Auth::check() ? route('feed') : route('home')}}" class="h-pill h-pill-primary nav-link {{Route::currentRouteName() == 'feed' ? 'active' : ''}} d-flex justify-content-between">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center">
                        @include('elements.icon',['icon'=>'home-outline','variant'=>'large'])
                    </div>
                    <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Home')}}</span>
                </div>
            </a>
        </li>
        @if(GenericHelper::isEmailEnforcedAndValidated())
            <li class="nav-item">
                <a href="{{route('my.notifications')}}" class="nav-link h-pill h-pill-primary {{Route::currentRouteName() == 'my.notifications' ? 'active' : ''}} d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                            @include('elements.icon',['icon'=>'notifications-outline','variant'=>'large'])
                            <div class="menu-notification-badge notifications-menu-count {{(isset($notificationsCountOverride) && $notificationsCountOverride->total > 0 ) || (NotificationsHelper::getUnreadNotifications()->total > 0) ? '' : 'd-none'}}">
                                {{!isset($notificationsCountOverride) ? NotificationsHelper::getUnreadNotifications()->total : $notificationsCountOverride->total}}
                            </div>
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Notifications')}}</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route('my.messenger.get')}}" class="nav-link {{Route::currentRouteName() == 'my.messenger.get' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                            @include('elements.icon',['icon'=>'chatbubble-outline','variant'=>'large'])
                            <div class="menu-notification-badge chat-menu-count {{(NotificationsHelper::getUnreadMessages() > 0) ? '' : 'd-none'}}">
                                {{NotificationsHelper::getUnreadMessages()}}
                            </div>
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Messages')}}</span>
                    </div>
                </a>
            </li>
        @endif

        @if(\App\Providers\GenericHelperServiceProvider::shouldRenderExploreMenu())
            <li class="nav-item">
                <a href="{{route('search.get')}}" class="nav-link {{Route::currentRouteName() == 'search.get' && (request()->get('filter') != 'live') ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'compass-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Explore')}}</span>
                    </div>
                </a>
            </li>
        @endif

        @if(getSetting('reels.reels_enabled') && GenericHelper::isEmailEnforcedAndValidated())
            <li class="nav-item">
                <a href="{{route('reels.index')}}" class="nav-link {{in_array(Route::currentRouteName(), ['reels.index', 'reels.get']) ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'film-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Reels')}}</span>
                    </div>
                </a>
            </li>
        @endif

        @if(GenericHelper::isEmailEnforcedAndValidated())
            @if(getSetting('streams.streaming_driver') !== 'none')
                <li class="nav-item">
                    <a href="{{route('search.get')}}?filter=live" class="nav-link {{Route::currentRouteName() == 'search.get' && request()->get('filter') == 'live' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                                @include('elements.icon',['icon'=>'play-circle-outline','variant'=>'large'])
                                <div class="menu-notification-badge streams-menu-count {{(StreamsHelper::getPublicLiveStreamsCount() > 0) ? '' : 'd-none'}}">
                                    {{StreamsHelper::getPublicLiveStreamsCount()}}
                                </div>
                            </div>
                            <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Streams')}}</span>
                        </div>

                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a href="{{route('my.bookmarks')}}" class="nav-link {{Route::currentRouteName() == 'my.bookmarks' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'bookmark-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Bookmarks')}}</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route('my.lists.all')}}" class="nav-link {{in_array(Route::currentRouteName(), ['my.lists.all', 'my.lists.show']) ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'list-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Lists')}}</span>
                    </div>
                </a>
            </li>
            @if(\App\Providers\ProfileMonetizationServiceProvider::shouldShowSubscriptionsForUser(Auth::user()))
                <li class="nav-item">
                    <a href="{{route('my.settings',['type'=>'subscriptions'])}}" class="nav-link {{Route::currentRouteName() == 'my.settings' &&  is_int(strpos(Request::path(),'subscriptions')) ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="icon-wrapper d-flex justify-content-center align-items-center">
                                @include('elements.icon',['icon'=>'people-circle-outline','variant'=>'large'])
                            </div>
                            <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Subscriptions')}}</span>
                        </div>
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a href="{{route('profile',['username'=>Auth::user()->username])}}" class="nav-link {{Route::currentRouteName() == 'profile' && (request()->route("username") == Auth::user()->username) ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'person-circle-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('My profile')}}</span>
                    </div>
                </a>
            </li>
        @endif

        <li class="nav-item">
            <a href="#" role="button" class="open-menu nav-link h-pill h-pill-primary text-muted d-flex justify-content-between">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center">
                        @include('elements.icon',['icon'=>'ellipsis-horizontal-circle-outline','variant'=>'large'])
                    </div>
                    <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('More')}}</span>
                </div>
            </a>
        </li>

        @if(GenericHelper::isEmailEnforcedAndValidated())
            @if(getSetting('streams.streaming_driver') !== 'none' && !getSetting('site.hide_stream_create_menu'))
                <li class="nav-item-live mt-1 mb-2">
                    <a role="button" class="btn btn-round btn-outline-danger btn-block px-3 side-menu-action-btn mb-0" href="{{route('my.streams.get')}}{{StreamsHelper::getUserInProgressStream() ? '' : ( !GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? '' : '?action=create')}}">
                        <div class="d-none d-md-flex d-xl-flex d-lg-flex align-items-center justify-content-center text-truncate new-post-label side-menu-action-content">
                            <span class="side-menu-action-icon stream-on-label {{StreamsHelper::getUserInProgressStream() ? '' : 'd-none'}}">
                                <span class="side-menu-live-dot"><div class="blob red"></div></span>
                            </span>
                            <span class="side-menu-action-icon stream-off-label {{StreamsHelper::getUserInProgressStream() ? 'd-none' : ''}}">
                                @include('elements.icon',['icon'=>'ellipse','variant'=>'','classes'=>'flex-shrink-0 text-danger'])
                            </span>
                            <span class="side-menu-action-text">
                                <span class="stream-on-label {{StreamsHelper::getUserInProgressStream() ? '' : 'd-none'}}">{{__('On air')}}</span>
                                <span class="stream-off-label {{StreamsHelper::getUserInProgressStream() ? 'd-none' : ''}}">{{__('Go live')}}</span>
                            </span>
                        </div>
                        <div class="d-block d-md-none d-flex align-items-center justify-content-center">@include('elements.icon',['icon'=>'add-circle-outline','variant'=>'medium','classes'=>'flex-shrink-0'])</div>
                    </a>
                </li>
            @endif
        @endif

        @php
            $createOptions = [];

            if (!getSetting('site.hide_create_post_menu')) {
                $createOptions[] = [
                    'label' => __('Post'),
                    'url' => route('posts.create'),
                    'icon' => 'add-outline',
                ];
            }

            if (getSetting('stories.stories_enabled')) {
                $createOptions[] = [
                    'label' => __('Story'),
                    'url' => route('stories.create'),
                    'icon' => 'time-outline',
                ];
            }

            if (getSetting('reels.reels_enabled')) {
                $createOptions[] = [
                    'label' => __('Reel'),
                    'url' => route('reels.create'),
                    'icon' => 'film-outline',
                ];
            }

            $primaryCreateOption = $createOptions[0] ?? null;
        @endphp

        @if(GenericHelper::isEmailEnforcedAndValidated() && $primaryCreateOption)
            <li class="nav-item mt-1">
                <div class="btn-group btn-block side-menu-create-group">
                    <a role="button" class="btn btn-primary side-menu-action-btn mb-0 {{count($createOptions) > 1 ? 'side-menu-create-main' : 'btn-round btn-block'}}" href="{{$primaryCreateOption['url']}}">
                        <span class="d-none d-md-flex d-xl-flex d-lg-flex align-items-center justify-content-center text-truncate new-post-label side-menu-action-content">
                            <span class="side-menu-action-icon">
                                @include('elements.icon',['icon'=>'add-outline','variant'=>'medium','classes'=>'flex-shrink-0'])
                            </span>
                            <span class="side-menu-action-text">{{__('Create')}}</span>
                        </span>
                        <span class="d-block d-md-none d-flex align-items-center justify-content-center">@include('elements.icon',['icon'=>'add-circle-outline','variant'=>'medium','classes'=>'flex-shrink-0'])</span>
                    </a>

                    @if(count($createOptions) > 1)
                        <button type="button"
                                class="btn btn-primary dropdown-toggle dropdown-toggle-split side-menu-create-toggle mb-0"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <span class="sr-only">{{__('Toggle create menu')}}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right side-menu-create-dropdown">
                            @foreach($createOptions as $option)
                                <a class="dropdown-item d-flex align-items-center" href="{{$option['url']}}">
                                    <span class="side-menu-create-option-icon">
                                        @include('elements.icon',['icon'=>$option['icon'],'variant'=>'medium','centered'=>false])
                                    </span>
                                    <span class="side-menu-create-option-label">{{__('New')}} {{$option['label']}}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </li>
        @endif


    </ul>
</div>
