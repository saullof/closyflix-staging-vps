@php
    $isDark = Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark');
    $checkoutCouponCode = isset($coupon) ? $coupon?->coupon_code : '';
    $inlineCheckout = $inlineCheckout ?? false;
    $checkoutFormAction = Auth::check() ? route('payment.initiatePayment') : route('guest.checkout.initiate');
    $checkoutValidationUrl = Auth::check() ? route('payment.initiatePaymentValidator') : route('guest.checkout.validate');
@endphp

<style>
    .coupon-method-hidden {
        display: none !important;
    }
</style>

<div class="row checkout-dialog {{ $isDark ? 'checkout-theme-dark' : 'checkout-theme-light' }}">
    <div class="col-lg-6 mx-auto">
        {{-- Paypal and stripe actual buttons --}}
        <div class="paymentOption paymentPP d-none">
            <form id="pp-buyItem" method="post" action="{{ $checkoutFormAction }}" data-validation-url="{{ $checkoutValidationUrl }}">
                @csrf
                <input type="hidden" name="amount" id="payment-deposit-amount" value="">
                <input type="hidden" name="transaction_type" id="payment-type" value="">
                <input type="hidden" name="post_id" id="post" value="">
                <input type="hidden" name="user_message_id" id="userMessage" value="">
                <input type="hidden" name="recipient_user_id" id="recipient" value="">
                <input type="hidden" name="provider" id="provider" value="">
                <input type="hidden" name="first_name" id="paymentFirstName" value="">
                <input type="hidden" name="last_name" id="paymentLastName" value="">
                <input type="hidden" name="billing_address" id="paymentBillingAddress" value="">
                <input type="hidden" name="city" id="paymentCity" value="">
                <input type="hidden" name="state" id="paymentState" value="">
                <input type="hidden" name="postcode" id="paymentPostcode" value="">
                <input type="hidden" name="country" id="paymentCountry" value="">
                <input type="hidden" name="taxes" id="paymentTaxes" value="">
                <input type="hidden" name="stream" id="stream" value="">
                <input type="hidden" name="coupon" id="coupon" value="{{ $checkoutCouponCode }}">
                <button class="payment-button" type="submit"></button>
            </form>
        </div>

        <div class="paymentOption ml-2 paymentStripe d-none">
            <button id="stripe-checkout-button">{{__('Checkout')}}</button>
        </div>

        <!-- Modal -->
        <div class="checkout-popup modal {{ $inlineCheckout ? 'checkout-inline-mode d-block position-static' : 'fade' }}" id="checkout-center" tabindex="-1" role="dialog" aria-labelledby="checkout" aria-hidden="{{ $inlineCheckout ? 'false' : 'true' }}">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="payment-title"></h5>
                        @unless($inlineCheckout)
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{__('Close')}}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        @endunless
                    </div>
                    <div class="modal-body">
                        <div class="payment-body">
                            <div class="checkout-top-section">
                            <div class="checkout-top-header d-flex flex-row align-items-center">
                                <div class="ml-0 mb-0 checkout-top-avatar">
                                    <img src="" class="rounded-circle user-avatar">
                                </div>
                                <div class="d-lg-block flex-grow-1">
                                    <div class="pl-2 d-flex justify-content-center flex-column">
                                        <div class="ml-2 checkout-top-copy">
                                            <div class="text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}} name"></div>
                                            <div class="text-muted username"><span>@</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="payment-description checkout-top-description mb-0 mt-2 d-none"></div>
                            </div>
                        </div>

                        <div id="accordion" class="mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between px-3 py-2" id="headingOne" data-toggle="collapse" data-target="#billingInformation" aria-expanded="true" aria-controls="billingInformation">
                                    <span class="mb-0 text-muted">
                                        {{__('Billing agreement details')}}
                                    </span>
                                    <div class="ml-1 label-icon">
                                        @include('elements.icon',['icon'=>'chevron-down-outline','centered'=>false])
                                    </div>
                                </div>
                                <div id="billingInformation" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                    <div class="card-body">
                                        <form id="billing-agreement-form">
                                            <div class="tab-content">
                                                <!-- credit card info-->
                                                <div id="individual" class="tab-pane fade show active pt-1">
                                                    <div class="row form-group">
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="firstName">
                                                                    <span>{{__('First name')}}</span>
                                                                </label>
                                                                <input type="text" name="firstName" placeholder="{{__('First name')}}" onchange="checkout.validateFirstNameField();" required class="form-control uifield-first_name">
                                                            </div>

                                                        </div>
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="lastName">
                                                                    <span>{{__('Last name')}}</span>
                                                                </label>
                                                                <input type="text" name="lastName" placeholder="{{__('Last name')}}" onblur="checkout.validateLastNameField()" required class="form-control uifield-last_name">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="countrySelect">
                                                            <span>{{__('Country')}}</span>
                                                        </label>
                                                        <select class="country-select form-control input-sm uifield-country" id="countrySelect" required onchange="checkout.validateCountryField()"></select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="billingCity">
                                                            <span>{{__('City')}}</span>
                                                        </label>
                                                        <input type="text" name="billingCity" placeholder="{{__('City')}}" onblur="checkout.validateCityField()" required class="form-control uifield-city">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="billingState">
                                                                    <span>{{__('State')}}</span>
                                                                </label>
                                                                <input type="text" name="billingState" placeholder="{{__('State')}}" onblur="checkout.validateStateField()" required class="form-control uifield-state">
                                                            </div>

                                                        </div>
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="billingPostcode">
                                                                    <span>{{__('Postcode')}}</span>
                                                                </label>
                                                                <input type="text" name="billingPostcode" placeholder="{{__('Postcode')}}" onblur="checkout.validatePostcodeField()" required class="form-control uifield-postcode">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="cardNumber">
                                                            <span>{{__('Address')}}</span>
                                                        </label>
                                                        <textarea rows="2" type="text" name="billingAddress" onblur="checkout.validateBillingAddressField()" placeholder="{{__('Street address, apartment, suite, unit')}}" class="form-control w-100 uifield-billing_address" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="billing-agreement-error error text-danger d-none">{{__('Please complete all billing details')}}</div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-payment-box mb-3">
                            <div class="checkout-payment-box-section checkout-amount-input d-none">
                                <h6 class="font-weight-bolder">{{__('Amount')}}</h6>
                                <div class="input-group mt-2 mb-0">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="amount-label">
                                            @include('elements.icon',['icon'=>'cash-outline','variant'=>'medium','centered'=>false])
                                        </span>
                                    </div>
                                    <input class="form-control uifield-amount" placeholder="{{__(\App\Providers\SettingsServiceProvider::leftAlignedCurrencyPosition() ? 'Amount ($5 min, $500 max)' : 'Amount (5$ min, 500$ max)',['min'=>getSetting('payments.min_tip_value'),'max'=>getSetting('payments.max_tip_value'),'currency'=>config('app.site.currency_symbol')])}}" aria-label="Username" aria-describedby="amount-label" id="checkout-amount" type="number" min="0" step="1" max="500" >
                                    <div class="invalid-feedback">{{__('Please enter a valid amount.')}}</div>
                                </div>
                            </div>

                            <div class="checkout-payment-box-section">
                            <h6 class="font-weight-bolder">{{__('Payment summary')}}</h6>
                            <div class="subtotal row mb-1">
                                <span class="col-sm left ">{{__('Subtotal')}}:</span>
                                <span class="subtotal-amount col-sm right text-right">
                                        <b>$0.00</b>
                                    </span>
                            </div>
                            <div class="total-without-tax row mb-1">
                                <span class="col-sm left ">{{__('Total excluding tax')}}:</span>
                                <span class="total-without-tax-amount col-sm right text-right">
                                        <b>$0.00</b>
                                    </span>
                            </div>
                            <div class="taxes row mb-1">
                                <span class="col-sm left ">{{__('Taxes')}}</span>
                            </div>
                            <div class="taxes-details mb-1"></div>
                            <div class="total row">
                                <span class="col-sm left ">{{__('Total')}}:</span>
                                <span class="total-amount col-sm right text-right">
                                        <b>$0.00</b>
                                    </span>
                            </div>
                            </div>

                            <div class="checkout-coupon-section mb-3">
                                <h6 class="font-weight-bolder">{{__('Coupon')}}</h6>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="coupon-input"
                                        name="coupon"
                                        value="{{ $checkoutCouponCode }}"
                                        placeholder="{{__('Coupon code')}}"
                                    >
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary mb-0" type="button" id="apply-coupon-btn">{{__('Apply')}}</button>
                                    </div>
                                </div>
                                <small class="coupon-feedback form-text text-muted"></small>
                            </div>

                            <div class="checkout-payment-box-section checkout-payment-box-methods">
                            <h6 class="font-weight-bolder d-flex align-items-center">
                                {{__('Payment method')}}
                                {{--                                <span class="to-tooltip" title="{{__('After clicking the button, you’ll be taken to a secure payment page and then redirected back to the website once payment is complete.')}}">--}}
                                {{--                                  @include('elements.icon',['icon'=>'information-circle-outline','variant'=>'small','centered'=>false, 'classes' => 'ml-1'])--}}
                                {{--                                </span>--}}
                            </h6>
                            <div class="d-flex text-left radio-group row px-2 my-2">
                                @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 stripe-payment-method" >
                                        <div class="radio mx-auto stripe-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="stripe">
                                            <img src="{{asset('/img/logos/stripe.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.stripe_pix_secret_key') && getSetting('payments.stripe_pix_public_key') && !getSetting('payments.stripe_pix_checkout_disabled') && strtoupper(config('app.site.currency_code')) === 'BRL')
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 stripe-pix-payment-method">
                                        <div class="radio mx-auto stripe-pix-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="stripe_pix">
                                            <img src="{{asset('/img/logos/pix.png')}}">
                                            <span class="sr-only">{{__('PIX')}}</span>
                                        </div>
                                    </div>
                                @endif
                                @if(Auth::check() && config('paypal.client_id') && config('paypal.secret') && !getSetting('payments.paypal_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 paypal-payment-method">
                                        <div class="radio mx-auto paypal-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="paypal">
                                            <img src="{{asset('/img/logos/paypal.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.nowpayments_api_key') && !getSetting('payments.nowpayments_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none nowpayments-payment-method">
                                        <div class="radio mx-auto nowpayments-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="nowpayments">
                                            <img src="{{asset('/img/logos/nowpayments.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(\App\Providers\PaymentsServiceProvider::ccbillCredentialsProvided())
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none ccbill-payment-method">
                                        <div class="radio mx-auto ccbill-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="ccbill">
                                            <img src="{{asset('/img/logos/ccbill.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.paystack_secret_key') && !getSetting('payments.paystack_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none paystack-payment-method">
                                        <div class="radio mx-auto paystack-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="paystack">
                                            <img src="{{asset('/img/logos/paystack.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.yookassa_shop_id') && getSetting('payments.yookassa_secret_key') && !getSetting('payments.yookassa_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none yookassa-payment-method">
                                        <div class="radio mx-auto yookassa-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="yookassa">
                                            <img src="{{asset('/img/logos/yookassa.svg')}}">
                                            <span class="sr-only">{{__('YooMoney')}}</span>
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.mollie_api_key') && !getSetting('payments.mollie_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none mollie-payment-method">
                                        <div class="radio mx-auto mollie-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="mollie">
                                            <img src="{{asset('/img/logos/mollie.svg')}}">
                                            <span class="sr-only">{{__('Mollie')}}</span>
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.flutterwave_secret_key') && !getSetting('payments.flutterwave_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none flutterwave-payment-method">
                                        <div class="radio mx-auto flutterwave-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="flutterwave">
                                            <img src="{{asset('/img/logos/flutterwave.svg')}}">
                                            <span class="sr-only">{{__('Flutterwave')}}</span>
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.coingate_api_token') && !getSetting('payments.coingate_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none coingate-payment-method">
                                        <div class="radio mx-auto coingate-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="coingate">
                                            <img src="{{asset('/img/logos/coingate.svg')}}">
                                            <span class="sr-only">{{__('CoinGate')}}</span>
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.xendit_secret_key') && !getSetting('payments.xendit_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none xendit-payment-method">
                                        <div class="radio mx-auto xendit-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="xendit">
                                            <img src="{{asset('/img/logos/xendit.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.paddle_api_key') && getSetting('payments.paddle_hosted_checkout_url') && !getSetting('payments.paddle_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none paddle-payment-method">
                                        <div class="radio mx-auto paddle-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="paddle">
                                            <img src="{{asset('/img/logos/paddle.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.cryptocom_secret_key') && !getSetting('payments.cryptocom_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none cryptocom-payment-method">
                                        <div class="radio mx-auto cryptocom-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="cryptocom">
                                            <img src="{{asset('/img/logos/cryptocom.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(Auth::check() && getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled') && getSetting('payments.stripe_oxxo_provider_enabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none oxxo-payment-method">
                                        <div class="radio mx-auto oxxo-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="oxxo">
                                            <img src="{{asset('/img/logos/oxxo.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.mercado_access_token') && !getSetting('payments.mercado_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none mercado-payment-method">
                                        <div class="radio mx-auto mercado-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="mercado">
                                            <img src="{{asset('/img/logos/mercado.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.verotel_merchant_id') && getSetting('payments.verotel_shop_id') && getSetting('payments.verotel_signature_key') && !getSetting('payments.verotel_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none verotel-payment-method">
                                        <div class="radio mx-auto verotel-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="verotel">
                                            <img src="{{asset('/img/logos/verotel.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.razorpay_api_key') && getSetting('payments.razorpay_api_secret') && !getSetting('payments.razorpay_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none razorpay-payment-method">
                                        <div class="radio mx-auto razorpay-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="razorpay">
                                            <img src="{{asset('/img/logos/razorpay.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(Auth::check())
                                    <div class="credit-payment-method p-1 col-6 col-md-3 col-lg-3 col-md-3" {{--data-toggle="tooltip"--}} {!! Auth::user()->wallet->total <= 0 ? 'data-toggle="tooltip" data-placement="right"' : '' !!} title="{{__('You can use the wallet deposit page to add credit.')}}">
                                        <div class="radio mx-auto credit-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center my-0" data-value="credit">
                                            <div class="credit-provider-text">
                                                <b>{{__("Credit")}}</b>
                                                <div class="available-credit">({{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount('0')}})</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            </div>
                        </div>
                        <div class="payment-error error text-danger text-bold d-none mb-1">{{__('Please select your payment method')}}</div>
                        @if(Auth::check())
                            <p class="text-muted mt-1 small mb-0"> {{__('Prefer to add funds? Visit your')}} <a  target="_blank" href="{{route('my.settings', ['type' => 'wallet', 'active' => 'deposit'])}}">{{__('Wallet page')}}</a>. </p>
                        @endif
                        <p class="text-muted mt-1 small mb-0"> {{__('Click the continue button to pay securely and return once finished.')}}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary checkout-continue-btn">{{__('Continue')}}
                            <div class="spinner-border spinner-border-sm ml-2 d-none" role="status">
                                <span class="sr-only">{{__('Loading...')}}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
