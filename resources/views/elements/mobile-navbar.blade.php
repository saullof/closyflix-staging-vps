@php
    $mobileCreateOptions = [];

    if (!getSetting('site.hide_create_post_menu')) {
        $mobileCreateOptions[] = [
            'label' => __('Post'),
            'url' => route('posts.create'),
            'icon' => 'add-outline',
        ];
    }

    if (getSetting('stories.stories_enabled')) {
        $mobileCreateOptions[] = [
            'label' => __('Story'),
            'url' => route('stories.create'),
            'icon' => 'time-outline',
        ];
    }

    if (getSetting('reels.reels_enabled')) {
        $mobileCreateOptions[] = [
            'label' => __('Reel'),
            'url' => route('reels.create'),
            'icon' => 'film-outline',
        ];
    }

    $mobilePrimaryCreateOption = $mobileCreateOptions[0] ?? null;
    $mobileStreamOption = null;

    if (getSetting('streams.streaming_driver') !== 'none' && !getSetting('site.hide_stream_create_menu')) {
        $mobileUserInProgressStream = StreamsHelper::getUserInProgressStream();
        $mobileStreamOption = [
            'label' => $mobileUserInProgressStream ? __('On air') : __('Go live'),
            'url' => route('my.streams.get') . ($mobileUserInProgressStream ? '' : (!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? '' : '?action=create')),
            'icon' => 'play-circle-outline',
        ];
    }

    $mobileCreateActionCount = count($mobileCreateOptions) + ($mobileStreamOption ? 1 : 0);
    $mobileSingleCreateOption = $mobilePrimaryCreateOption ?: $mobileStreamOption;
    $mobileCreateRoutes = ['posts.create', 'stories.create', 'reels.create', 'my.streams.get'];
@endphp

<div class="mobile-bottom-nav border-top z-index-3 py-1 neutral-bg">
    <div class="d-flex justify-content-between w-100 py-2 px-2">
        <a href="{{Auth::check() ? route('feed') : route('home')}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{Route::currentRouteName() == 'feed' ? 'active' : ''}}">
            <div class="d-flex justify-content-center align-items-center">
                <div class="icon-wrapper d-flex justify-content-center align-items-center">
                    @include('elements.icon',['icon'=>'home-outline','variant'=>'large'])
                </div>
            </div>
        </a>
        @if(Auth::check())
            <a href="{{route('my.notifications')}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{Route::currentRouteName() == 'my.notifications' ? 'active' : ''}}">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                        @include('elements.icon',['icon'=>'notifications-outline','variant'=>'large'])
                        <div class="menu-notification-badge notifications-menu-count {{(isset($notificationsCountOverride) && $notificationsCountOverride->total > 0 ) || (NotificationsHelper::getUnreadNotifications()->total > 0) ? '' : 'd-none'}}">
                            {{!isset($notificationsCountOverride) ? NotificationsHelper::getUnreadNotifications()->total : $notificationsCountOverride->total}}
                        </div>
                    </div>
                </div>
            </a>
            @if(GenericHelper::isEmailEnforcedAndValidated() && $mobileCreateActionCount)
                @if($mobileCreateActionCount > 1)
                    <div class="dropup mobile-create-group">
                        <button type="button"
                                class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 mobile-create-toggle {{in_array(Route::currentRouteName(), $mobileCreateRoutes) ? 'active' : ''}}"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <span class="d-flex justify-content-center align-items-center">
                                <span class="icon-wrapper d-flex justify-content-center align-items-center">
                                    @include('elements.icon',['icon'=>'add-circle-outline','variant'=>'large'])
                                </span>
                            </span>
                            <span class="sr-only">{{__('Toggle create menu')}}</span>
                        </button>
                        <div class="dropdown-menu mobile-create-dropdown">
                            @foreach($mobileCreateOptions as $option)
                                <a class="dropdown-item d-flex align-items-center" href="{{$option['url']}}">
                                    <span class="side-menu-create-option-icon">
                                        @include('elements.icon',['icon'=>$option['icon'],'variant'=>'medium','centered'=>false])
                                    </span>
                                    <span class="side-menu-create-option-label">{{__('New')}} {{$option['label']}}</span>
                                </a>
                            @endforeach
                            @if($mobileStreamOption)
                                @if(count($mobileCreateOptions))
                                    <div class="dropdown-divider"></div>
                                @endif
                                <a class="dropdown-item d-flex align-items-center" href="{{$mobileStreamOption['url']}}">
                                    <span class="side-menu-create-option-icon">
                                        @include('elements.icon',['icon'=>$mobileStreamOption['icon'],'variant'=>'medium','centered'=>false])
                                    </span>
                                    <span class="side-menu-create-option-label">{{$mobileStreamOption['label']}}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <a href="{{$mobileSingleCreateOption['url']}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{in_array(Route::currentRouteName(), $mobileCreateRoutes) ? 'active' : ''}}">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="icon-wrapper d-flex justify-content-center align-items-center">
                                @include('elements.icon',['icon'=>'add-circle-outline','variant'=>'large'])
                            </div>
                        </div>
                    </a>
                @endif
            @endif
            <a href="{{route('my.messenger.get')}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{Route::currentRouteName() == 'my.messenger.get' ? 'active' : ''}}">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                        @include('elements.icon',['icon'=>'chatbubble-outline','variant'=>'large'])
                        <div class="menu-notification-badge chat-menu-count {{(NotificationsHelper::getUnreadMessages() > 0) ? '' : 'd-none'}}">
                            {{NotificationsHelper::getUnreadMessages()}}
                        </div>
                    </div>
                </div>
            </a>
        @endif
        <a href="javascript:void(0)" class="open-menu h-pill h-pill-primary nav-link d-flex justify-content-between px-3">
            <div class="d-flex justify-content-center align-items-center">
                <div class="icon-wrapper d-flex justify-content-center align-items-center">
                    @if(Auth::check())
                        <img src="{{Auth::user()->avatar}}" class="rounded-circle user-avatar w-32">
                    @else
                        <div class="avatar-placeholder">
                            @include('elements.icon',['icon'=>'person-circle','variant'=>'large'])
                        </div>
                    @endif
                </div>
            </div>
        </a>
    </div>
</div>
