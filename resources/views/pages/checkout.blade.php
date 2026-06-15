@extends('layouts.user-no-nav')

@section('page_title', __('Checkout'))

@section('scripts')
    {!! Minify::javascript(['/js/pages/checkout.js'])->withFullUrl() !!}
@stop

@section('content')
    @php
        $plans = collect([
            [
                'type' => \App\Model\Transaction::ONE_MONTH_SUBSCRIPTION,
                'months' => 1,
                'amount' => (float) ($user->profile_access_price ?: getSetting('payments.default_subscription_price')),
            ],
            [
                'type' => \App\Model\Transaction::THREE_MONTHS_SUBSCRIPTION,
                'months' => 3,
                'amount' => (float) $user->profile_access_price_3_months * 3,
            ],
            [
                'type' => \App\Model\Transaction::SIX_MONTHS_SUBSCRIPTION,
                'months' => 6,
                'amount' => (float) $user->profile_access_price_6_months * 6,
            ],
            [
                'type' => \App\Model\Transaction::YEARLY_SUBSCRIPTION,
                'months' => 12,
                'amount' => (float) $user->profile_access_price_12_months * 12,
            ],
        ])->filter(fn (array $plan) => $plan['amount'] > 0)->values();

        $selectedPlan = $plans->first() ?? [
            'type' => \App\Model\Transaction::ONE_MONTH_SUBSCRIPTION,
            'amount' => 0,
        ];
    @endphp

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <img src="{{ $user->avatar }}" class="rounded-circle user-avatar mr-3" width="72" height="72">
                            <div>
                                <h4 class="mb-1">{{ __('Subscribe to') }} {{ $user->name }}</h4>
                                <div class="text-muted">@{{ $user->username }}</div>
                            </div>
                        </div>

                        @if($coupon)
                            <div class="alert alert-success">
                                {{ __('Coupon applied') }}: <strong>{{ $coupon->coupon_code }}</strong>
                            </div>
                        @endif

                        <h6 class="font-weight-bolder mb-3">{{ __('Choose your subscription plan') }}</h6>

                        <div class="subscription-plan-list mb-4">
                            @foreach($plans as $index => $plan)
                                <label class="subscription-plan-option {{ $index === 0 ? 'selected' : '' }} d-flex align-items-center justify-content-between p-3 mb-2 border rounded">
                                    <span class="d-flex align-items-center">
                                        <input
                                            type="radio"
                                            name="checkout_subscription_plan"
                                            value="{{ $plan['type'] }}"
                                            data-amount="{{ number_format($plan['amount'], 2, '.', '') }}"
                                            {{ $index === 0 ? 'checked' : '' }}
                                        >
                                        <span class="ml-3">
                                            {{ trans_choice('months', $plan['months'], ['number' => $plan['months']]) }}
                                        </span>
                                    </span>
                                    <strong>{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($plan['amount']) }}</strong>
                                </label>
                            @endforeach
                        </div>

                        <div
                            class="checkout-open-trigger d-none"
                            data-recipient-id="{{ $user->id }}"
                            data-amount="{{ number_format($selectedPlan['amount'], 2, '.', '') }}"
                            data-type="{{ $selectedPlan['type'] }}"
                            data-username="{{ $user->username }}"
                            data-first-name="{{ Auth::user()?->first_name }}"
                            data-last-name="{{ Auth::user()?->last_name }}"
                            data-billing-address="{{ Auth::user()?->billing_address }}"
                            data-name="{{ $user->name }}"
                            data-avatar="{{ $user->avatar }}"
                            data-country="{{ Auth::user()?->country }}"
                            data-city="{{ Auth::user()?->city }}"
                            data-state="{{ Auth::user()?->state }}"
                            data-postcode="{{ Auth::user()?->postcode }}"
                            data-available-credit="{{ Auth::user()?->wallet?->total ?? 0 }}"
                            data-coupon="{{ $coupon?->coupon_code }}"
                        ></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 mt-3 mt-lg-0">
                @include('elements.checkout.checkout-box', ['coupon' => $coupon, 'inlineCheckout' => true])
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trigger = document.querySelector('.checkout-open-trigger');
            const plans = document.querySelectorAll('input[name="checkout_subscription_plan"]');

            function selectPlan(plan) {
                if (!plan || !trigger) {
                    return;
                }

                trigger.dataset.type = plan.value;
                trigger.dataset.amount = plan.dataset.amount;

                document.querySelectorAll('.subscription-plan-option').forEach(function (option) {
                    option.classList.remove('selected');
                });
                plan.closest('.subscription-plan-option').classList.add('selected');
                configureCheckoutFromTrigger(trigger);
            }

            plans.forEach(function (plan) {
                plan.addEventListener('change', function () {
                    if (!this.checked) {
                        return;
                    }
                    selectPlan(this);
                });
            });

            selectPlan(document.querySelector('input[name="checkout_subscription_plan"]:checked'));
        });
    </script>

    <style>
        .subscription-plan-option {
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .subscription-plan-option:hover,
        .subscription-plan-option.selected {
            border-color: var(--primary) !important;
            background: linear-gradient(135deg, rgba(190, 21, 35, .18), rgba(155, 9, 121, .08));
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }

        .checkout-inline-mode {
            overflow: visible;
        }

        .checkout-inline-mode .modal-dialog {
            margin: 0;
            max-width: none;
            min-height: 0;
            transform: none;
        }

        .checkout-inline-mode .modal-content {
            border-radius: .75rem;
            box-shadow: 0 16px 40px rgba(0, 0, 0, .12);
        }

        @media (min-width: 992px) {
            .container > .row.justify-content-center {
                align-items: flex-start;
            }
        }
    </style>
@stop
