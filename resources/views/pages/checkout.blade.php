@extends('layouts.no-nav')

@section('page_title', __('Checkout'))

@section('styles')
    {!! Minify::stylesheet(['/css/pages/checkout.css'])->withFullUrl() !!}
@stop

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
                'helper' => 'Acesso por 30 dias',
            ],
            [
                'type' => \App\Model\Transaction::THREE_MONTHS_SUBSCRIPTION,
                'months' => 3,
                'amount' => (float) $user->profile_access_price_3_months * 3,
                'helper' => 'Menos renovações',
            ],
            [
                'type' => \App\Model\Transaction::SIX_MONTHS_SUBSCRIPTION,
                'months' => 6,
                'amount' => (float) $user->profile_access_price_6_months * 6,
                'helper' => 'Acesso prolongado',
            ],
            [
                'type' => \App\Model\Transaction::YEARLY_SUBSCRIPTION,
                'months' => 12,
                'amount' => (float) $user->profile_access_price_12_months * 12,
                'helper' => 'Melhor opção de longo prazo',
            ],
        ])->filter(fn (array $plan) => $plan['amount'] > 0)->values();

        $selectedPlan = $plans->first() ?? [
            'type' => \App\Model\Transaction::ONE_MONTH_SUBSCRIPTION,
            'amount' => 0,
        ];
    @endphp

    <main class="legacy-checkout-page py-4 py-md-5">
        <div class="container">
            <div class="legacy-checkout-shell mx-auto">
                <section class="legacy-checkout-card">
                    <div class="legacy-checkout-timer" role="timer" aria-live="polite">
                        <div class="legacy-checkout-timer-copy">
                            <i class="far fa-clock"></i>
                            <span>Oferta reservada por</span>
                        </div>
                        <strong><span class="checkout-timer-minutes">15</span>:<span class="checkout-timer-seconds">00</span></strong>
                    </div>

                    <div class="legacy-checkout-cover">
                        <img src="{{ $user->cover }}" alt="{{ __('Cover image') }}">
                        <div class="legacy-checkout-cover-overlay"></div>
                    </div>

                    <div class="legacy-checkout-profile">
                        <img src="{{ $user->avatar }}" class="legacy-checkout-avatar" alt="{{ $user->name }}">
                        <div class="legacy-checkout-profile-copy">
                            <h1>{{ $user->name }}</h1>
                            <span>{{ '@'.$user->username }}</span>
                        </div>
                    </div>

                    <div class="legacy-checkout-intro">
                        <h2>Tenha acesso ao conteúdo exclusivo</h2>
                        <ul class="legacy-checkout-benefits">
                            <li><i class="fas fa-check"></i> Acesso a fotos e vídeos exclusivos</li>
                            <li><i class="fas fa-check"></i> Converse diretamente comigo</li>
                            <li><i class="fas fa-check"></i> Cancele sua assinatura quando quiser</li>
                        </ul>
                    </div>

                    @if($coupon)
                        <div class="legacy-checkout-coupon">
                            <i class="fas fa-tag"></i>
                            <span>Cupom aplicado: <strong>{{ $coupon->coupon_code }}</strong></span>
                        </div>
                    @endif

                    <section class="legacy-checkout-step">
                        <div class="legacy-checkout-step-heading">
                            <div>
                                <h3>Escolha seu plano de assinatura</h3>
                                <p>Selecione por quanto tempo deseja acessar este perfil.</p>
                            </div>
                        </div>

                        <div class="legacy-checkout-plans">
                            @foreach($plans as $index => $plan)
                                <label class="legacy-plan-option {{ $index === 0 ? 'selected' : '' }}">
                                    <input
                                        type="radio"
                                        name="checkout_subscription_plan"
                                        value="{{ $plan['type'] }}"
                                        data-amount="{{ number_format($plan['amount'], 2, '.', '') }}"
                                        {{ $index === 0 ? 'checked' : '' }}
                                    >
                                    <span class="legacy-plan-indicator"></span>
                                    <span class="legacy-plan-copy">
                                        <strong>{{ trans_choice('months', $plan['months'], ['number' => $plan['months']]) }}</strong>
                                        <small>{{ $plan['helper'] }}</small>
                                        @if($plan['months'] === 12)
                                            <span class="legacy-plan-badge">Melhor oferta</span>
                                        @endif
                                    </span>
                                    <strong class="legacy-plan-price">
                                        {{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($plan['amount']) }}
                                    </strong>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="legacy-checkout-step legacy-checkout-payment-step">
                        <div class="legacy-checkout-step-heading">
                            <div>
                                <h3>Finalize sua compra</h3>
                                <p>Confira os detalhes e escolha PIX ou cartão para pagar com segurança.</p>
                            </div>
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

                        @include('elements.checkout.checkout-box', ['coupon' => $coupon, 'inlineCheckout' => true])
                    </section>
                </section>

                <div class="legacy-checkout-security">
                    <i class="fas fa-lock"></i>
                    <span>Pagamento seguro. Seus dados financeiros são processados pelo provedor de pagamento.</span>
                </div>
            </div>
        </div>
    </main>

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

                document.querySelectorAll('.legacy-plan-option').forEach(function (option) {
                    option.classList.remove('selected');
                });
                plan.closest('.legacy-plan-option').classList.add('selected');
                configureCheckoutFromTrigger(trigger);
            }

            plans.forEach(function (plan) {
                plan.addEventListener('change', function () {
                    if (this.checked) {
                        selectPlan(this);
                    }
                });
            });

            selectPlan(document.querySelector('input[name="checkout_subscription_plan"]:checked'));

            if (typeof send_initial_checkout_pixels === 'function') {
                send_initial_checkout_pixels();
            }

            const minutesDisplay = document.querySelector('.checkout-timer-minutes');
            const secondsDisplay = document.querySelector('.checkout-timer-seconds');
            let remainingSeconds = 15 * 60;

            window.setInterval(function () {
                if (!minutesDisplay || !secondsDisplay || remainingSeconds <= 0) {
                    return;
                }

                remainingSeconds -= 1;
                minutesDisplay.textContent = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
                secondsDisplay.textContent = String(remainingSeconds % 60).padStart(2, '0');
            }, 1000);
        });
    </script>

    <style>
        .legacy-checkout-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at 15% 0, rgba(188, 21, 34, .18), transparent 32rem),
                linear-gradient(180deg, #111 0, #181818 100%);
        }

        .legacy-checkout-shell {
            max-width: 760px;
        }

        .legacy-checkout-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 18px;
            background: #202020;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .38);
        }

        .legacy-checkout-timer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem 1.25rem;
            color: #fff;
            background: linear-gradient(135deg, #e13b49, #9f101c);
        }

        .legacy-checkout-timer-copy {
            display: flex;
            align-items: center;
            gap: .65rem;
            font-weight: 600;
        }

        .legacy-checkout-timer-copy i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .16);
        }

        .legacy-checkout-timer strong {
            padding: .35rem .65rem;
            border-radius: 6px;
            font-size: 1.1rem;
            font-variant-numeric: tabular-nums;
            background: rgba(255, 255, 255, .16);
        }

        .legacy-checkout-cover {
            position: relative;
            height: 210px;
            overflow: hidden;
            background: #161616;
        }

        .legacy-checkout-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .legacy-checkout-cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 35%, rgba(32, 32, 32, .96) 100%);
        }

        .legacy-checkout-profile {
            position: relative;
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            margin-top: -58px;
            padding: 0 2rem;
        }

        .legacy-checkout-avatar {
            width: 112px;
            height: 112px;
            border: 4px solid #202020;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .3);
        }

        .legacy-checkout-profile-copy {
            padding-bottom: .65rem;
        }

        .legacy-checkout-profile-copy h1 {
            margin: 0;
            color: #fff;
            font-size: 1.5rem;
        }

        .legacy-checkout-profile-copy span,
        .legacy-checkout-step-heading p,
        .legacy-plan-copy small {
            color: #aaa;
        }

        .legacy-checkout-intro,
        .legacy-checkout-step {
            padding: 1.5rem 2rem;
        }

        .legacy-checkout-intro h2 {
            color: #fff;
            font-size: 1.15rem;
        }

        .legacy-checkout-benefits {
            display: grid;
            gap: .65rem;
            margin: 1rem 0 0;
            padding: 0;
            color: #ddd;
            list-style: none;
        }

        .legacy-checkout-benefits i {
            width: 22px;
            color: #e13b49;
        }

        .legacy-checkout-coupon {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin: 0 2rem 1rem;
            padding: .85rem 1rem;
            border: 1px solid rgba(40, 167, 69, .35);
            border-radius: 10px;
            color: #bce8c6;
            background: rgba(40, 167, 69, .12);
        }

        .legacy-checkout-step {
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .legacy-checkout-step-heading {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .legacy-checkout-step-heading h3 {
            margin: 0 0 .15rem;
            color: #fff;
            font-size: 1.05rem;
        }

        .legacy-checkout-step-heading p {
            margin: 0;
            font-size: .86rem;
        }

        .legacy-checkout-plans {
            display: grid;
            gap: .75rem;
        }

        .legacy-plan-option {
            display: flex;
            align-items: center;
            gap: .85rem;
            margin: 0;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 12px;
            cursor: pointer;
            background: #282828;
            transition: border-color .15s ease, transform .15s ease, background .15s ease;
        }

        .legacy-plan-option:hover {
            transform: translateY(-1px);
            border-color: rgba(225, 59, 73, .55);
        }

        .legacy-plan-option.selected {
            border-color: #e13b49;
            background: linear-gradient(135deg, rgba(188, 21, 34, .3), rgba(45, 45, 45, .75));
            box-shadow: 0 0 0 1px rgba(225, 59, 73, .2);
        }

        .legacy-plan-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .legacy-plan-indicator {
            width: 18px;
            height: 18px;
            border: 2px solid #777;
            border-radius: 50%;
            box-shadow: inset 0 0 0 4px #282828;
        }

        .legacy-plan-option.selected .legacy-plan-indicator {
            border-color: #e13b49;
            background: #e13b49;
        }

        .legacy-plan-copy {
            display: flex;
            flex: 1;
            flex-direction: column;
            color: #fff;
        }

        .legacy-plan-badge {
            align-self: flex-start;
            margin-top: .35rem;
            padding: .15rem .5rem;
            border-radius: 999px;
            color: #fff;
            font-size: .68rem;
            background: #bc1522;
        }

        .legacy-plan-price {
            color: #fff;
            white-space: nowrap;
        }

        .legacy-checkout-payment-step .checkout-dialog {
            margin: 0;
        }

        .legacy-checkout-payment-step .checkout-dialog > div {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
            padding: 0;
        }

        .legacy-checkout-payment-step .checkout-inline-mode {
            position: static !important;
            display: block;
            overflow: visible;
        }

        .legacy-checkout-payment-step .modal-dialog {
            max-width: none;
            min-height: 0;
            margin: 0;
            transform: none;
        }

        .legacy-checkout-payment-step .modal-content {
            border: 0;
            border-radius: 12px;
            color: #e5e5e5;
            background: #282828;
            box-shadow: none;
        }

        .legacy-checkout-payment-step .modal-header {
            display: none;
        }

        .legacy-checkout-payment-step .modal-body {
            padding: 1rem;
        }

        .legacy-checkout-payment-step .modal-footer {
            padding: 0 1rem 1rem;
            border: 0;
        }

        .legacy-checkout-payment-step .checkout-continue-btn {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 999px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #e13b49, #9f101c);
        }

        .legacy-checkout-payment-step a,
        .legacy-checkout-payment-step .text-primary {
            color: #e13b49 !important;
        }

        .legacy-checkout-payment-step .btn-primary,
        .legacy-checkout-payment-step .btn-primary:hover,
        .legacy-checkout-payment-step .btn-primary:focus {
            border-color: #bc1522;
            color: #fff;
            background: linear-gradient(135deg, #e13b49, #9f101c);
            box-shadow: none;
        }

        .legacy-checkout-payment-step .btn-outline-primary {
            border-color: #bc1522;
            color: #f0606c;
            background: transparent;
        }

        .legacy-checkout-payment-step .btn-outline-primary:hover,
        .legacy-checkout-payment-step .btn-outline-primary:focus {
            border-color: #e13b49;
            color: #fff;
            background: #bc1522;
            box-shadow: none;
        }

        .legacy-checkout-payment-step .form-control,
        .legacy-checkout-payment-step .selectize-input,
        .legacy-checkout-payment-step .selectize-dropdown,
        .legacy-checkout-payment-step .card,
        .legacy-checkout-payment-step .card-header {
            border-color: #4a4a4a;
            color: #eee;
            background: #1e1e1e;
        }

        .legacy-checkout-payment-step .form-control:focus,
        .legacy-checkout-payment-step .selectize-input.focus {
            border-color: #e13b49;
            box-shadow: 0 0 0 .2rem rgba(225, 59, 73, .18);
        }

        .legacy-checkout-payment-step .selectize-dropdown .option {
            color: #ddd;
            background: #1e1e1e;
        }

        .legacy-checkout-payment-step .selectize-dropdown .active {
            color: #fff;
            background: #bc1522;
        }

        .legacy-checkout-payment-step .checkout-payment-provider {
            border-color: #555;
            background: #1e1e1e;
        }

        .legacy-checkout-payment-step .checkout-payment-provider.selected {
            border-color: #e13b49 !important;
            box-shadow: 0 0 0 2px rgba(225, 59, 73, .2);
        }

        .legacy-checkout-payment-step .credit-provider-text,
        .legacy-checkout-payment-step .credit-provider-text b,
        .legacy-checkout-payment-step .available-credit {
            color: #fff !important;
        }

        .legacy-checkout-payment-step .stripe-payment-provider img {
            width: 84px;
            height: 50px;
            object-fit: contain;
        }

        .legacy-checkout-payment-step .text-muted {
            color: #aaa !important;
        }

        .legacy-checkout-security {
            display: flex;
            justify-content: center;
            gap: .5rem;
            padding: 1rem;
            color: #929292;
            font-size: .82rem;
            text-align: center;
        }

        @media (max-width: 575.98px) {
            .legacy-checkout-page {
                padding-top: 0 !important;
            }

            .legacy-checkout-page .container {
                padding: 0;
            }

            .legacy-checkout-card {
                border-width: 0;
                border-radius: 0;
            }

            .legacy-checkout-cover {
                height: 175px;
            }

            .legacy-checkout-profile,
            .legacy-checkout-intro,
            .legacy-checkout-step {
                padding-right: 1rem;
                padding-left: 1rem;
            }

            .legacy-checkout-profile {
                margin-top: -48px;
            }

            .legacy-checkout-avatar {
                width: 92px;
                height: 92px;
            }

            .legacy-checkout-coupon {
                margin-right: 1rem;
                margin-left: 1rem;
            }
        }
    </style>
@stop
