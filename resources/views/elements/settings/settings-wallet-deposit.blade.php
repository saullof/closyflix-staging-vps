@php
    $isDark = Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark');
    $depositPaymentMethods = [
        [
            'enabled' => config('paypal.client_id') && config('paypal.secret'),
            'id' => 'deposit-payment-paypal',
            'value' => 'payment-paypal',
            'name' => __('Paypal'),
            'logo' => asset('/img/logos/paypal.svg'),
            'type' => __('Wallet & cards'),
            'note' => __('Pay with PayPal balance or linked cards.'),
        ],
        [
            'enabled' => getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key'),
            'id' => 'deposit-payment-stripe',
            'value' => 'payment-stripe',
            'name' => __('Stripe'),
            'logo' => asset('/img/logos/stripe.svg'),
            'type' => __('Cards & local methods'),
            'note' => __('Secure card checkout with supported local options.'),
        ],
        [
            'enabled' => getSetting('payments.nowpayments_api_key'),
            'id' => 'deposit-payment-nowpayments',
            'value' => 'payment-nowpayments',
            'name' => __('NowPayments'),
            'logo' => asset('/img/logos/nowpayments.svg'),
            'type' => __('Crypto'),
            'note' => __('Pay using supported cryptocurrencies.'),
        ],
        [
            'enabled' => getSetting('payments.mercado_access_token'),
            'id' => 'deposit-payment-mercado',
            'value' => 'payment-mercado',
            'name' => __('MercadoPago'),
            'logo' => asset('/img/logos/mercado.svg'),
            'type' => __('Cards & wallet'),
            'note' => __('Regional card and wallet payments.'),
        ],
        [
            'enabled' => \App\Providers\PaymentsServiceProvider::ccbillCredentialsProvided(),
            'id' => 'deposit-payment-ccbill',
            'value' => 'payment-ccbill',
            'name' => __('CCBill'),
            'logo' => asset('/img/logos/ccbill.svg'),
            'type' => __('Card payments'),
            'note' => __('Card checkout through CCBill.'),
        ],
        [
            'enabled' => getSetting('payments.paystack_secret_key'),
            'id' => 'deposit-payment-paystack',
            'value' => 'payment-paystack',
            'name' => __('Paystack'),
            'logo' => asset('/img/logos/paystack.svg'),
            'type' => __('Cards & bank'),
            'note' => __('Card and bank payments where supported.'),
        ],
        [
            'enabled' => getSetting('payments.yookassa_shop_id') && getSetting('payments.yookassa_secret_key'),
            'id' => 'deposit-payment-yookassa',
            'value' => 'payment-yookassa',
            'name' => __('YooMoney'),
            'logo' => asset('/img/logos/yookassa.svg'),
            'type' => __('Cards & wallet'),
            'note' => __('YooKassa checkout with supported methods.'),
        ],
        [
            'enabled' => getSetting('payments.mollie_api_key'),
            'id' => 'deposit-payment-mollie',
            'value' => 'payment-mollie',
            'name' => __('Mollie'),
            'logo' => asset('/img/logos/mollie.svg'),
            'type' => __('Cards & local methods'),
            'note' => __('Mollie checkout with regional options.'),
        ],
        [
            'enabled' => getSetting('payments.flutterwave_secret_key'),
            'id' => 'deposit-payment-flutterwave',
            'value' => 'payment-flutterwave',
            'name' => __('Flutterwave'),
            'logo' => asset('/img/logos/flutterwave.svg'),
            'type' => __('Cards & mobile money'),
            'note' => __('Card and mobile money payments.'),
        ],
        [
            'enabled' => getSetting('payments.coingate_api_token'),
            'id' => 'deposit-payment-coingate',
            'value' => 'payment-coingate',
            'name' => __('CoinGate'),
            'logo' => asset('/img/logos/coingate.svg'),
            'type' => __('Crypto'),
            'note' => __('Pay using supported cryptocurrencies.'),
        ],
        [
            'enabled' => getSetting('payments.xendit_secret_key'),
            'id' => 'deposit-payment-xendit',
            'value' => 'payment-xendit',
            'name' => __('Xendit'),
            'logo' => asset('/img/logos/xendit.svg'),
            'type' => __('Cards, e-wallets & bank'),
            'note' => __('Regional payment methods through Xendit.'),
        ],
        [
            'enabled' => getSetting('payments.paddle_api_key') && getSetting('payments.paddle_hosted_checkout_url'),
            'id' => 'deposit-payment-paddle',
            'value' => 'payment-paddle',
            'name' => __('Paddle'),
            'logo' => asset('/img/logos/paddle.svg'),
            'type' => __('Cards & wallets'),
            'note' => __('Hosted checkout with card and wallet options.'),
        ],
        [
            'enabled' => getSetting('payments.cryptocom_secret_key'),
            'id' => 'deposit-payment-cryptocom',
            'value' => 'payment-cryptocom',
            'name' => __('Crypto.com'),
            'logo' => asset('/img/logos/cryptocom.svg'),
            'type' => __('Crypto'),
            'note' => __('Pay using Crypto.com supported assets.'),
        ],
        [
            'enabled' => getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && getSetting('payments.stripe_oxxo_provider_enabled'),
            'id' => 'deposit-payment-oxxo',
            'value' => 'payment-oxxo',
            'name' => __('Oxxo'),
            'logo' => asset('/img/logos/oxxo.svg'),
            'type' => __('Cash voucher'),
            'note' => __('Pay in cash using an OXXO voucher.'),
        ],
        [
            'enabled' => getSetting('payments.verotel_merchant_id') && getSetting('payments.verotel_shop_id') && getSetting('payments.verotel_signature_key'),
            'id' => 'deposit-payment-verotel',
            'value' => 'payment-verotel',
            'name' => __('Verotel'),
            'logo' => asset('/img/logos/verotel.svg'),
            'type' => __('Card payments'),
            'note' => __('Card checkout through Verotel.'),
        ],
        [
            'enabled' => getSetting('payments.razorpay_api_key') && getSetting('payments.razorpay_api_secret'),
            'id' => 'deposit-payment-razorpay',
            'value' => 'payment-razorpay',
            'name' => __('Razorpay'),
            'logo' => asset('/img/logos/razorpay.svg'),
            'type' => __('Cards, UPI & banking'),
            'note' => __('Regional checkout with card and bank options.'),
        ],
        [
            'enabled' => getSetting('payments.allow_manual_payments'),
            'id' => 'deposit-payment-manual',
            'value' => 'payment-manual',
            'name' => __('Bank transfer'),
            'logo' => null,
            'type' => __('Manual review'),
            'note' => __('Send funds by bank transfer and attach proof.'),
            'icon' => 'business-outline',
            'full_width_when_odd' => true,
        ],
    ];
    $enabledDepositPaymentMethods = array_values(array_filter($depositPaymentMethods, fn ($method) => $method['enabled']));
    $enabledDepositPaymentMethodCount = count($enabledDepositPaymentMethods);
@endphp

<div class="deposit-nice-shell {{ $isDark ? 'deposit-theme-dark' : 'deposit-theme-light' }}">
    <div class="deposit-card">
        <div class="deposit-card-title">{{__('Add funds')}}</div>
        <div class="deposit-card-copy">{{__('Choose how much you want to add to your wallet before continuing to payment.')}}</div>
        <div class="deposit-amount-field">
            <div class="input-group deposit-form-wrapper">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="amount-label">@include('elements.icon',['icon'=>'cash-outline','variant'=>'medium'])</span>
                </div>
                <input class="form-control" placeholder="{{\App\Providers\PaymentsServiceProvider::getDepositLimitAmounts()}}"
                       aria-label="{{__('Amount')}}"
                       aria-describedby="amount-label"
                       id="deposit-amount"
                       type="number"
                       min="{{\App\Providers\PaymentsServiceProvider::getDepositMinimumAmount()}}"
                       step="1"
                       max="{{\App\Providers\PaymentsServiceProvider::getDepositMaximumAmount()}}">
            </div>
            <div class="invalid-feedback deposit-amount-feedback">{{__('Please enter a valid amount.')}}</div>
        </div>
    </div>

    <div class="deposit-card">
        <div class="deposit-card-title">{{__('Payment method')}}</div>
        <div class="deposit-card-copy">{{__('Select the provider you want to use for your wallet top-up.')}}</div>
        <div class="payment-method row mx-n1">
            @foreach($enabledDepositPaymentMethods as $method)
                <div class="{{!empty($method['full_width_when_odd']) && $enabledDepositPaymentMethodCount % 2 === 1 ? 'col-12' : 'col-12 col-md-6'}} px-1 mb-2">
                    <div class="custom-control custom-radio deposit-provider-option h-100">
                        <input type="radio" id="{{$method['id']}}" name="payment-radio-option" class="custom-control-input"
                               value="{{$method['value']}}">
                        <label class="custom-control-label stepTooltip d-flex align-items-center w-100 h-100 mb-0 py-2 pr-3 pl-5 border rounded" for="{{$method['id']}}" title="">
                            <span class="deposit-provider-logo d-inline-flex align-items-center justify-content-center flex-shrink-0 mr-3" aria-hidden="true">
                                @if($method['logo'])
                                    <img src="{{$method['logo']}}" alt="">
                                @else
                                    @include('elements.icon',['icon'=>$method['icon'],'variant'=>'medium'])
                                @endif
                            </span>
                            <span class="deposit-provider-copy d-flex flex-column">
                                <span class="font-weight-bold text-body">{{$method['name']}}</span>
                                <span class="small text-primary font-weight-bold">{{$method['type']}}</span>
                                <span class="small text-muted text-truncate mt-1">{{$method['note']}}</span>
                            </span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        @if(getSetting('payments.allow_manual_payments'))
            <div class="manual-details d-none">
                <h5 class="mt-4 mb-3">{{__("Add payment details")}}</h5>

                @if(getSetting('payments.offline_payments_iban'))
                <div class="alert alert-primary text-white font-weight-bold" role="alert">
                    <p class="mb-0">{{__('Once confirmed, your credit will be available and you will be notified via email.')}}</p>
                    <ul class="mt-2 mb-2">
                        <li>{{__('IBAN')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_iban')}}</span></li>
                        <li>{{__('BIC/SWIFT')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_swift')}}</span></li>
                        <li>{{__('Bank name')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_bank_name')}}</span></li>
                        <li>{{__('Account owner')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_owner')}}</span></li>
                        <li>{{__('Account number')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_account_number')}}</span></li>
                        <li>{{__('Routing number')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_routing_number')}}</span></li>
                    </ul>
                </div>
                @endif

                @if(getSetting('payments.offline_payments_custom_message_box'))
                    @include('elements.settings.custom-info-box', [
                        'message' => getSetting('payments.offline_payments_custom_message_box'),
                        'classes' => '',
                    ])
                @endif

                <div>
                    <label for="manualPaymentDescription" title="">{{__("Notes")}}</label>
                    <textarea class="form-control" id="manualPaymentDescription" rows="1"></textarea>
                    <span class="invalid-feedback" role="alert">
                        <strong>{{__("Payment notes are required")}}</strong>
                    </span>
                </div>
                <p class="mb-1 mt-2">{{__("Please attach clear photos with one the following: check, money order or bank transfer.")}}</p>
                <div class="dropzone-previews dropzone manual-payment-uploader w-100 ppl-0 pr-0 pt-1 pb-1 border rounded"></div>
                <small class="form-text text-muted mb-2">{{__("Allowed file types")}}: {{str_replace(',',', ',AttachmentHelper::filterExtensions('manualPayments'))}}.</small>
                <div class="text-danger invalid-files d-none">{{trans_choice('Please upload at least one file', (int)getSetting('payments.offline_payments_minimum_attachments_required'), ['num' => (int)getSetting('payments.offline_payments_minimum_attachments_required')])}}</div>
            </div>
        @endif
        <div class="deposit-provider-summary">{{__('You will be redirected to the selected provider to complete the wallet top-up securely.')}}</div>
    </div>

    <div>
        <div class="payment-error error text-danger d-none mt-1">{{__('Please select your payment method')}}</div>
        <button class="btn btn-primary btn-block rounded mr-0 mt-3 deposit-continue-btn" type="submit">{{__('Add funds')}}</button>
    </div>
</div>
@include('elements.uploaded-file-preview-template')
