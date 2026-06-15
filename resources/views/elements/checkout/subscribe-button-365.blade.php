<button class="btn btn-round btn-outline-primary btn-block d-flex justify-content-between mt-2 mb-2 px-5 to-tooltip {{((Auth::check() && !GenericHelper::isEmailEnforcedAndValidated()) || (Auth::check() && !GenericHelper::creatorCanEarnMoney($user)) ) ? 'disabled' : ''}}"
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
    <span class="d-flex">
        {{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price_12_months * 12)}}
        {{__('for')}}
        {{trans_choice('months', 12,['number'=>12])}}
        <span class="d-none d-md-flex ml-1">
            @if(isset($offer['discountAmount']['365']) && $offer['discountAmount']['365'] > 0)
                ({{round($offer['discountAmount']['365'])}}% {{__('off')}})
            @endif
        </span>
    </span>
</button>
