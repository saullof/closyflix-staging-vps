@php
    $isDark = Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark');
    $activeTabLabel = $activeSubsTab == 'subscriptions' ? __('Subscriptions') : __('Subscribers');
    $summaryAmountLabel = $activeSubsTab == 'subscriptions' ? __('Active spend') : __('Active revenue');
@endphp

@if($subscribersCount)
    <div class="mt-3 mb-3 inline-border-tabs">
        <nav class="nav nav-pills nav-justified">
            @foreach(['subscriptions', 'subscribers'] as $tab)
                <a class="nav-item nav-link {{$activeSubsTab == $tab ? 'active' : ''}}" href="{{route('my.settings',['type' => 'subscriptions', 'active' => $tab])}}">
                    <div class="d-flex align-items-center justify-content-center">
                        @if($tab == 'subscriptions')
                            @include('elements.icon',['icon'=>'people','variant'=>'medium','classes'=>'mr-2'])
                        @else
                            @include('elements.icon',['icon'=>'logo-usd','variant'=>'medium','classes'=>'mr-2'])
                        @endif
                        {{ucfirst(__($tab))}}
                    </div>
                </a>
            @endforeach
        </nav>
    </div>
@endif

<div class="settings-data-page settings-subscriptions-page {{ $isDark ? 'theme-dark' : 'theme-light' }}">
    <div class="settings-data-overview mb-3">
        <div class="settings-data-overview-copy">
            <div class="settings-data-results mb-1">
                {{ number_format((int) $subscriptionSummary['total_entries']) }} {{ $activeTabLabel }}
            </div>
            <div class="text-muted small">
                {{ $activeSubsTab == 'subscriptions' ? __('Manage creators you currently support.') : __('Review members currently supporting you.') }}
            </div>
        </div>

        <div class="settings-data-summary row mt-3 mb-0">
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="settings-data-stat-card">
                    <div>
                        <div class="settings-data-stat-label">{{ __('Total entries') }}</div>
                        <div class="settings-data-stat-value">{{ number_format((int) $subscriptionSummary['total_entries']) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'people-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="settings-data-stat-card">
                    <div>
                        <div class="settings-data-stat-label">{{ __('Active') }}</div>
                        <div class="settings-data-stat-value">{{ number_format((int) $subscriptionSummary['active_entries']) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'checkmark-circle-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="settings-data-stat-card">
                    <div>
                        <div class="settings-data-stat-label">{{ __('Ending soon') }}</div>
                        <div class="settings-data-stat-value">{{ number_format((int) $subscriptionSummary['ending_soon_entries']) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'time-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="settings-data-stat-card">
                    <div>
                        <div class="settings-data-stat-label">{{ $summaryAmountLabel }}</div>
                        <div class="settings-data-stat-value">{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $subscriptionSummary['active_amount'], 2, '.', '')) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'cash-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
        </div>
    </div>

    @include('elements/message-alert', ['classes' =>'p-2'])

    @if($subscriptions->count())
        <div class="table-wrapper settings-data-table settings-subscriptions-table table-hover-primary-soft">
            <div class="settings-data-table-head">
                <div class="col d-flex align-items-center border-bottom text-bold settings-data-header-row">
                    <div class="col-4 col-md-3 text-truncate">{{$activeSubsTab == 'subscriptions' ? __('To') : __('From')}}</div>
                    <div class="col-3 col-md-2 text-truncate">{{__('Status')}}</div>
                    <div class="col-2 text-truncate d-none d-md-block">{{__('Paid with')}}</div>
                    <div class="col-4 col-md-2 text-truncate">{{__('Renews')}}</div>
                    <div class="col-2 text-truncate d-none d-md-block">{{__('Expires at')}}</div>
                    <div class="col-1 text-truncate"></div>
                </div>
            </div>

            <div class="settings-data-table-body">
                @foreach($subscriptions as $subscription)
                    @php($subscriptionPresentation = $subscriptionPresentations[$subscription->id])
                    <div class="col d-flex align-items-center border-bottom settings-data-row settings-subscriptions-row table-hover-item">
                        <div class="col-4 col-md-3 text-truncate">
                            @if($subscriptionPresentation['profileUrl'])
                                <a href="{{ $subscriptionPresentation['profileUrl'] }}" class="settings-subscriptions-avatar-link mr-2">
                                    @if($subscriptionPresentation['profileAvatar'])
                                        <img src="{{ $subscriptionPresentation['profileAvatar'] }}" class="rounded-circle user-avatar" width="35">
                                    @endif
                                </a>
                                <a href="{{ $subscriptionPresentation['profileUrl'] }}" class="text-dark-r settings-data-party-link">
                                    {{ $subscriptionPresentation['profileName'] }}
                                </a>
                            @else
                                <span class="settings-data-cell-main">{{ $subscriptionPresentation['profileName'] }}</span>
                            @endif
                        </div>

                        <div class="col-3 col-md-2">
                            <span class="badge badge-{{ $subscriptionPresentation['statusClass'] }} settings-data-status-badge">
                                {{ $subscriptionPresentation['statusLabel'] }}
                            </span>
                        </div>

                        <div class="col-2 text-truncate d-none d-md-block settings-data-meta">
                            {{ $subscriptionPresentation['providerLabel'] }}
                        </div>

                        <div class="col-4 col-md-2 text-truncate">
                            <div class="settings-data-meta text-muted {{ $subscriptionPresentation['renewsIsPlaceholder'] ? 'text-center' : '' }}">
                                {{ $subscriptionPresentation['renewsLabel'] }}
                            </div>
                        </div>

                        <div class="col-2 text-truncate d-none d-md-block">
                            <div class="settings-data-meta text-muted {{ $subscriptionPresentation['expiresIsPlaceholder'] ? 'text-center' : '' }}">
                                {{ $subscriptionPresentation['expiresLabel'] }}
                            </div>
                        </div>

                        <div class="col-1 d-flex justify-content-center settings-data-action-cell">
                            @if($subscriptionPresentation['canCancel'])
                                <div class="dropdown {{GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft'}}">
                                    <a class="btn btn-sm text-dark-r text-hover btn-outline-{{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? 'dark' : 'light') : (Cookie::get('app_theme') == 'dark' ? 'dark' : 'light'))}} dropdown-toggle m-0 py-1 px-2 settings-data-action-btn" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                        @include('elements.icon',['icon'=>'ellipsis-horizontal-outline','centered'=>false])
                                    </a>
                                    <div class="dropdown-menu">
                                        @if($subscription->provider !== 'ccbill' || \App\Providers\SettingsServiceProvider::providedCCBillSubscriptionCancellingCredentials())
                                            <a class="dropdown-item d-flex align-items-center" href="javascript:void(0)" onclick="SubscriptionsSettings.confirmSubCancelation({{$subscription->id}},{{$activeSubsTab == 'subscriptions' ? '"subscriptions"' : '"subscribers"'}})">
                                                @include('elements.icon',['icon'=>'trash-outline','centered'=>false,'classes'=>'mr-2']) {{__('Cancel subscription')}}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="settings-data-pagination mt-3 px-0 px-md-2 pb-2">
            {{ $subscriptions->withQueryString()->links() }}
        </div>
    @else
        <div class="settings-data-empty-state settings-subscriptions-empty-state text-center">
            <div class="settings-subscriptions-empty-icon mx-auto mb-3">
                @include('elements.icon',['icon'=>$activeSubsTab == 'subscriptions' ? 'people-circle-outline' : 'cash-outline','variant'=>'medium','centered'=>true])
            </div>
            <div class="settings-data-empty-title">
                {{ $activeSubsTab == 'subscriptions' ? __('No subscriptions yet') : __('No subscribers yet') }}
            </div>
            <div class="settings-data-empty-copy text-muted">
                {{ $activeSubsTab == 'subscriptions' ? __('Creators you support will show up here.') : __('Members who subscribe to you will show up here.') }}
            </div>
            <div class="settings-subscriptions-empty-hint text-muted small mt-2">
                {{ $activeSubsTab == 'subscriptions' ? __('Explore creators and subscribe to keep them listed here.') : __('Active supporters will appear here as soon as they subscribe.') }}
            </div>
        </div>
    @endif
</div>

@include('elements.settings.transaction-cancel-dialog')
