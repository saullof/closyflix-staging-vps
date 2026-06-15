@php
    $isDark = Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark');
@endphp

<div class="settings-payments-page {{ $isDark ? 'theme-dark' : 'theme-light' }}">
    <div class="settings-payments-overview mb-3">
        <div class="settings-payments-overview-header d-flex flex-wrap align-items-start justify-content-between">
            <div class="settings-payments-overview-copy pr-3">
                <div class="settings-payments-results mb-1">
                    {{ number_format((int) $payments->total()) }} {{ __('Results') }}
                </div>
                <div class="text-muted small">
                    {{ __('Track your purchases, earnings, and withdrawals in one place.') }}
                </div>
            </div>

            <div class="d-flex align-items-center mt-2 mt-md-0">
                <a class="settings-payments-filter-toggle {{ $hasActivePaymentFilters ? 'settings-payments-filter-toggle-active' : '' }} h-pill h-pill-primary rounded d-flex justify-content-center align-items-center" data-toggle="collapse" href="#paymentsFiltersCollapse" role="button" aria-expanded="{{ $hasActivePaymentFilters ? 'true' : 'false' }}" aria-controls="paymentsFiltersCollapse" aria-label="{{ __('Apply filters') }}" title="{{ __('Apply filters') }}">
                    @include('elements.icon',['icon'=>'filter-outline','variant'=>'medium','centered'=>true])
                    @if($activePaymentFilterCount)
                        <span class="settings-payments-filter-count">{{ $activePaymentFilterCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        <div class="settings-payments-summary row mt-3 mb-0">
            <div class="col-6 col-xl-3 mb-2">
                <div class="settings-payments-stat-card">
                    <div>
                        <div class="settings-payments-stat-label">{{ __('Total entries') }}</div>
                        <div class="settings-payments-stat-value">{{ number_format((int) $paymentSummary['total_entries']) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'list-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
            <div class="col-6 col-xl-3 mb-2">
                <div class="settings-payments-stat-card">
                    <div>
                        <div class="settings-payments-stat-label">{{ __('Received') }}</div>
                        <div class="settings-payments-stat-value">{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $paymentSummary['received_amount'], 2, '.', '')) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'download-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
            <div class="col-6 col-xl-3 mb-2">
                <div class="settings-payments-stat-card">
                    <div>
                        <div class="settings-payments-stat-label">{{ __('Sent') }}</div>
                        <div class="settings-payments-stat-value">{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $paymentSummary['sent_amount'], 2, '.', '')) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'send-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
            <div class="col-6 col-xl-3 mb-2">
                <div class="settings-payments-stat-card">
                    <div>
                        <div class="settings-payments-stat-label">{{ __('Withdrawals') }}</div>
                        <div class="settings-payments-stat-value">{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $paymentSummary['withdrawal_amount'], 2, '.', '')) }}</div>
                    </div>
                    <span class="settings-data-stat-icon">@include('elements.icon',['icon'=>'wallet-outline','variant'=>'small','centered'=>true])</span>
                </div>
            </div>
        </div>

        <div class="collapse {{ $hasActivePaymentFilters ? 'show' : '' }}" id="paymentsFiltersCollapse">
            <div class="settings-payments-filters-panel">
                <form method="GET" action="{{ route('my.settings', ['type' => 'payments']) }}">
                    <div class="form-row align-items-end">
                        <div class="col-12 col-md-6 col-xl-3 mb-2">
                            <label class="small text-muted mb-1">{{ __('Status') }}</label>
                            <select class="form-control" name="status">
                                <option value="all">{{ __('Any status') }}</option>
                                @foreach($paymentStatusOptions as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" {{ $paymentFilters['status'] === $statusValue ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3 mb-2">
                            <label class="small text-muted mb-1">{{ __('Type') }}</label>
                            <select class="form-control" name="transactionType">
                                <option value="all">{{ __('Any type') }}</option>
                                @foreach($paymentTypeOptions as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}" {{ $paymentFilters['type'] === $typeValue ? 'selected' : '' }}>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3 mb-2">
                            <label class="small text-muted mb-1">{{ __('Direction') }}</label>
                            <select class="form-control" name="direction">
                                <option value="all">{{ __('All directions') }}</option>
                                @foreach($paymentDirectionOptions as $directionValue => $directionLabel)
                                    <option value="{{ $directionValue }}" {{ $paymentFilters['direction'] === $directionValue ? 'selected' : '' }}>
                                        {{ $directionLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3 mb-2">
                            <label class="small text-muted mb-1">{{ __('Sort by') }}</label>
                            <select class="form-control" name="sort">
                                @foreach($paymentSortOptions as $sortValue => $sortLabel)
                                    <option value="{{ $sortValue }}" {{ $paymentFilters['sort'] === $sortValue ? 'selected' : '' }}>
                                        {{ $sortLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center flex-wrap mt-2">
                        @if($hasActivePaymentFilters)
                            <a class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0" href="{{ route('my.settings', ['type' => 'payments']) }}">{{ __('Reset') }}</a>
                        @endif
                        <button class="btn btn-primary mb-0" type="submit">{{ __('Apply filters') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($payments->count())
        <div class="table-wrapper settings-payments-table table-hover-primary-soft">
            <div class="settings-payments-table-head">
                <div class="col d-flex align-items-center border-bottom text-bold settings-payments-header-row">
                    <div class="col-3 col-md-3 col-lg-3 text-truncate">{{ __('Type') }}</div>
                    <div class="col-3 col-md-2 col-lg-2 text-truncate">{{ __('Status') }}</div>
                    <div class="col-4 col-md-2 col-lg-2 text-truncate">{{ __('Amount') }}</div>
                    <div class="col-md-2 col-lg-2 text-truncate d-none d-md-block">{{ __('From') }}</div>
                    <div class="col-md-2 col-lg-2 text-truncate d-none d-md-block">{{ __('To') }}</div>
                    <div class="col-2 col-md-1 col-lg-1 text-truncate"></div>
                </div>
            </div>

            <div class="settings-payments-table-body">
                @foreach($payments as $payment)
                    @php($paymentPresentation = $paymentPresentations[$payment->id])
                    @php($typePresentation = $paymentPresentation['typePresentation'])
                    <div class="col d-flex align-items-center border-bottom settings-payments-row table-hover-item">
                        <div class="col-3 col-md-3 col-lg-3 text-truncate">
                            @if($typePresentation['url'])
                                <a href="{{ $typePresentation['url'] }}" class="text-dark-r settings-payments-cell-main">
                                    {{ $typePresentation['label'] }}
                                </a>
                            @elseif($typePresentation['tooltip'])
                                <span class="settings-payments-cell-main" data-toggle="tooltip" data-placement="top" title="{{ $typePresentation['tooltip'] }}">
                                    {{ $typePresentation['label'] }}
                                </span>
                            @else
                                <span class="settings-payments-cell-main">{{ $typePresentation['label'] }}</span>
                            @endif

                            @if($typePresentation['meta'])
                                <div class="settings-payments-meta text-muted small">{{ $typePresentation['meta'] }}</div>
                            @endif
                        </div>

                        <div class="col-3 col-md-2 col-lg-2">
                            <span class="badge badge-{{ $paymentPresentation['statusClass'] }} settings-payments-status-badge">
                                {{ $paymentStatusOptions[$payment->status] ?? ucfirst(__($payment->status)) }}
                            </span>
                        </div>

                        <div class="col-4 col-md-2 col-lg-2 text-truncate">
                            <div class="settings-payments-amount">{{ $paymentPresentation['formattedAmount'] }}</div>
                            <div class="settings-payments-meta text-muted small">{{ $paymentPresentation['createdDateLabel'] }}</div>
                        </div>

                        <div class="col-md-2 col-lg-2 text-truncate d-none d-md-block">
                            @if($paymentPresentation['senderProfileUrl'])
                                <a href="{{ $paymentPresentation['senderProfileUrl'] }}" class="text-dark-r settings-payments-party-link">
                                    {{ $paymentPresentation['senderDisplayName'] }}
                                </a>
                            @else
                                —
                            @endif
                        </div>

                        <div class="col-md-2 col-lg-2 text-truncate d-none d-md-block">
                            @if($paymentPresentation['receiverProfileUrl'])
                                <a href="{{ $paymentPresentation['receiverProfileUrl'] }}" class="text-dark-r settings-payments-party-link">
                                    {{ $paymentPresentation['receiverDisplayName'] }}
                                </a>
                            @else
                                —
                            @endif
                        </div>

                        <div class="col-2 col-md-1 col-lg-1 d-flex justify-content-center settings-payments-action-cell">
                            @if($paymentPresentation['canViewInvoice'])
                                <div class="dropdown {{ GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft' }}">
                                    <a class="btn btn-sm text-dark-r text-hover btn-outline-{{ (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? 'dark' : 'light') : (Cookie::get('app_theme') == 'dark' ? 'dark' : 'light')) }} dropdown-toggle m-0 py-1 px-2 settings-payments-action-btn" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                        @include('elements.icon',['icon'=>'ellipsis-horizontal-outline','centered'=>false])
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item d-flex align-items-center" href="{{ $paymentPresentation['invoiceUrl'] }}">
                                            @include('elements.icon',['icon'=>'document-outline','centered'=>false,'classes'=>'mr-2']) {{ __('View invoice') }}
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="settings-payments-pagination mt-3 px-0 px-md-2 pb-2">
            {{ $payments->withQueryString()->links() }}
        </div>
    @else
        <div class="settings-payments-empty-state text-center">
            <div class="settings-payments-empty-title">
                {{ $hasActivePaymentFilters ? __('No payments match these filters.') : __('No payments yet') }}
            </div>
            <div class="settings-payments-empty-copy text-muted">
                {{ $hasActivePaymentFilters ? __('Try changing or resetting your filters.') : __('Your purchases, earnings, and withdrawals will show up here.') }}
            </div>
            @if($hasActivePaymentFilters)
                <a class="btn btn-outline-secondary mt-3" href="{{ route('my.settings', ['type' => 'payments']) }}">{{ __('Reset') }}</a>
            @endif
        </div>
    @endif
</div>
