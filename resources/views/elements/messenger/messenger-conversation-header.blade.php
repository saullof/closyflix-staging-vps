<div class="conversation-header d-none">
    <div class="details-holder border-bottom">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center flex-fill overflow-hidden pr-3">
{{--                <span data-toggle="tooltip" title="" class="pointer-cursor flex-shrink-0" data-original-title="{{__('Back')}}">--}}
                    <a class="h-pill h-pill-primary pointer-cursor rounded-circle d-flex align-items-center justify-content-center messenger-mobile-back d-lg-none mr-2">
                        @include('elements.icon',['icon'=>'arrow-back-outline','variant'=>'medium'])
                    </a>
{{--                </span>--}}
                <div class="d-flex align-items-center overflow-hidden">
                    <div class="flex-shrink-0">
                        <a class="conversation-profile-link" target="_blank">
                            <img class="conversation-header-avatar" src="{{asset('/img/default-avatar.jpg')}}" onerror="this.onerror=null;this.src='{{asset('/img/default-avatar.jpg')}}';" />
                        </a>
                    </div>
                    <div class="d-flex flex-column pl-3 overflow-hidden">
                        <span class="conversation-header-title d-flex align-items-center overflow-hidden">
                            <span class="conversation-header-user text-truncate font-weight-bold">{{--Contact name placeholder--}}</span>
                            <span class="conversation-header-verified-badge"></span>
                        </span>
                        <a class="conversation-header-username small text-muted conversation-profile-link text-truncate" target="_blank">{{--Contact username placeholder--}}</a>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center flex-shrink-0">
                <a title="{{__('Search messages')}}" class="h-pill h-pill-primary ml-2 pointer-cursor conversation-message-search-toggle rounded-circle d-flex align-items-center justify-content-center mb-0 mr-2" data-toggle="tooltip" data-placement="bottom" data-original-title="{{__('Search messages')}}">
                    @include('elements.icon',['icon'=>'search-outline','variant'=>'mediumish'])
                </a>
                <a title="{{__('Send a tip')}}" class="h-pill h-pill-primary pointer-cursor conversation-tip-toggle tip-btn to-tooltip rounded-circle d-flex align-items-center justify-content-center mb-0 mr-2"
                   data-placement="bottom"
                   data-original-title="{{__('Send a tip')}}"
                   data-toggle="modal"
                   data-target="#checkout-center"
                   data-type="chat-tip"
                   data-first-name="{{Auth::user()->first_name}}"
                   data-last-name="{{Auth::user()->last_name}}"
                   data-billing-address="{{Auth::user()->billing_address}}"
                   data-country="{{Auth::user()->country}}"
                   data-city="{{Auth::user()->city}}"
                   data-state="{{Auth::user()->state}}"
                   data-postcode="{{Auth::user()->postcode}}"
                   data-available-credit="{{Auth::user()->wallet->total}}"
                >
                    @include('elements.icon',['icon'=>'gift-outline','variant'=>'mediumish'])
                </a>
                <div class="dropdown {{GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft'}}">
                    <a class="h-pill h-pill-primary pointer-cursor conversation-header-menu-toggle rounded-circle d-flex align-items-center justify-content-center mb-0" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                        @include('elements.icon',['icon'=>'ellipsis-horizontal-outline', 'variant'=>'mediumish'])
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item d-flex align-items-center conversation-profile-link" href="#" target="_blank">{{__('Go to profile')}}</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item unfollow-btn" href="javascript:void(0);">{{__('Unfollow')}}</a>
                        <a class="dropdown-item block-btn" href="javascript:void(0);">{{__('Block')}}</a>
                        <a class="dropdown-item report-btn" href="javascript:void(0);">{{__('Report')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
