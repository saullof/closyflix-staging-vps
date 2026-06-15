@if(\App\Providers\ProfileMonetizationServiceProvider::canReceiveProfileSubscriptions($user))
    @if(Auth::check())
        @if( !(isset($hasSub) && $hasSub) && !(isset($post) && PostsHelper::hasActiveSub(Auth::user()->id, $post->user->id)) && Auth::user()->id !== $user->id)
            <div class="card mt-3 rounded-lg">
                <div class="card-body">
                    <h5 class="card-title text-uppercase fs-point-85 font-weight-bold">{{__('Subscription')}}</h5>
                    <button class="btn btn-round btn-outline-primary btn-block d-flex align-items-center justify-content-center justify-content-lg-between mt-3 mb-0 to-tooltip {{(Auth::check() && !GenericHelper::isEmailEnforcedAndValidated() || !GenericHelper::creatorCanEarnMoney($user)) ? 'disabled' : ''}}"
                            @if(!Auth::user()->email_verified_at && getSetting('site.enforce_email_validation'))
                                data-placement="top"
                            title="{{__('Please verify your account')}}"
                            @elseif(!GenericHelper::creatorCanEarnMoney($user))
                                data-placement="top"
                            title="{{__('This creator cannot earn money yet')}}"
                            @else
                                onclick="window.location.href='{{ url($user->username . '/checkout') }}'"
                        @endif
                    >
                        <span class="d-none d-md-block d-xl-block d-lg-block">{{__('Subscribe')}}</span>
                        <span class="d-none d-lg-block">{{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price)}} {{__('for')}} {{trans_choice('days',30,['number'=>30])}}</span>
                    </button>
                </div>
            </div>
        @endif
    @else
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title text-uppercase fs-point-85 font-weight-bold">{{__('Subscription')}}</h5>
                <button class="btn btn-round btn-outline-primary btn-block d-flex align-items-center justify-content-center justify-content-lg-between mt-3 mb-0"
                        data-toggle="modal"
                        data-target="#login-dialog"
                >
                    <span class="d-none d-md-block d-xl-block d-lg-block">{{__('Subscribe')}}</span>
                    <span class="d-none d-lg-block">{{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price)}} {{__('for')}} {{trans_choice('days',30,['number'=>30])}}</span>
                </button>
            </div>
        </div>
    @endif
@elseif(!Auth::check() || (Auth::check() && Auth::user()->id !== $user->id))
    @if(Auth::check())
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title text-uppercase fs-point-85 font-weight-bold">{{__('Follow this creator')}}</h5>
                <button class="btn btn-round btn-outline-primary btn-block mt-3 mb-0 manage-follow-button" onclick="Lists.manageFollowsAction('{{$user->id}}')">
                    <span class="manage-follows-text">{{\App\Providers\ListsHelperServiceProvider::getUserFollowingType($user->id, true)}}</span>
                </button>
            </div>
        </div>
    @else
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title text-uppercase fs-point-85 font-weight-bold">{{__('Follow this creator')}}</h5>
                <button class="btn btn-round btn-outline-primary btn-block mt-3 mb-0 text-center"
                        data-toggle="modal"
                        data-target="#login-dialog"
                >
                    <span class="d-none d-md-block d-xl-block d-lg-block">{{__('Follow')}}</span>
                </button>
            </div>
        </div>
    @endif
@endif
