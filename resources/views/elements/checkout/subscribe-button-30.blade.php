<button class="btn btn-round btn-lg btn-primary btn-block d-flex justify-content-between mt-3 mb-2 px-5 to-tooltip {{((Auth::check() && !GenericHelper::isEmailEnforcedAndValidated()) || (Auth::check() && !GenericHelper::creatorCanEarnMoney($user)) ) ? 'disabled' : ''}}"
        @if(Auth::check())
            @if(!Auth::user()->email_verified_at && getSetting('site.enforce_email_validation'))
                data-placement="top"
                title="{{__('Please verify your account')}}"
            @elseif(!GenericHelper::creatorCanEarnMoney($user))
                data-placement="top"
                title="{{__('This creator cannot earn money yet')}}"
            @else
                onclick="window.location.href='{{ url($user->username . '/checkout') }}'"
            @endif
        @else
            data-toggle="modal"
            data-target="#login-dialog"
    @endif
>
    <span>{{__('Subscribe')}}</span>
    <span class="">
        {{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price)}}
        {{__('for')}}
        {{trans_choice('days', 30,['number'=>30])}}</span>
</button>
