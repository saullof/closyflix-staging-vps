/**
 * Component used for handling checkout dialog actions
 */
"use strict";
/* global app, trans, trans_choice, launchToast, getWebsiteFormattedAmount, getTaxDescription */

function getCheckoutDataFromTrigger($trigger) {
    return {
        postId: $trigger.data('post-id'),
        recipientId: $trigger.data('recipient-id'),
        amount: $trigger.data('amount'),
        type: $trigger.data('type') || $trigger.val(),
        username: $trigger.data('username'),
        firstName: $trigger.data('first-name'),
        lastName: $trigger.data('last-name'),
        billingAddress: $trigger.data('billing-address'),
        name: $trigger.data('name'),
        avatar: $trigger.data('avatar'),
        country: $trigger.data('country'),
        city: $trigger.data('city'),
        state: $trigger.data('state'),
        postcode: $trigger.data('postcode'),
        availableCredit: $trigger.data('available-credit'),
        streamId: $trigger.data('stream-id'),
        userMessageId: $trigger.data('message-id'),
        coupon: $trigger.data('coupon') || $('#coupon-input').val()
    };
}

function selectFirstAvailablePaymentMethod() {
    let $provider = $('.payment-method:not(.d-none):not(.coupon-method-hidden) .radio:visible').first();

    if (!$provider.length) {
        $provider = $('.radio-group .radio:visible').first();
    }

    if (!$provider.length) {
        return;
    }

    $('.radio-group .radio').removeClass('selected');
    $provider.addClass('selected');
    $('.payment-error').addClass('d-none');
}

function configureCheckoutFromTrigger(trigger) {
    const data = getCheckoutDataFromTrigger($(trigger));
    const activeCoupon = checkout.paymentData.coupon || data.coupon || '';

    checkout.initiatePaymentData(
        data.type,
        data.amount,
        data.postId,
        data.recipientId,
        data.firstName,
        data.lastName,
        data.billingAddress,
        data.country,
        data.city,
        data.state,
        data.postcode,
        data.availableCredit,
        data.streamId,
        data.userMessageId,
        activeCoupon
    );

    checkout.updateUserDetails(data.avatar, data.username, data.name);
    checkout.fillCountrySelectOptions();
    checkout.prefillBillingDetails();

    let paymentTitle = '';
    let paymentDescription = '';

    if (data.type === 'tip' || data.type === 'chat-tip') {
        $('.checkout-amount-input').removeClass('d-none');
        paymentTitle = trans("Send a tip");
        paymentDescription = trans("Send a tip to this user");
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    } else if ([
        'one-month-subscription',
        'three-months-subscription',
        'six-months-subscription',
        'yearly-subscription'
    ].includes(data.type)) {
        let numberOfMonths = 1;
        let showStripeProvider = !app.stripeRecurringDisabled;
        let showPaypalProvider = !app.paypalRecurringDisabled;
        let showCCBillProvider = !app.ccBillRecurringDisabled;
        let showCreditProvider = !app.localWalletRecurringDisabled;
        let verotelProvider = !app.verotelRecurringDisabled;

        if (data.type === 'three-months-subscription') numberOfMonths = 3;
        if (data.type === 'six-months-subscription') {
            numberOfMonths = 6;
            showCCBillProvider = false;
        }
        if (data.type === 'yearly-subscription') {
            numberOfMonths = 12;
            showCCBillProvider = false;
        }

        checkout.togglePaymentProviders(false, checkout.oneTimePaymentProcessorClasses);
        checkout.togglePaymentProvider(showCCBillProvider, '.ccbill-payment-method');
        checkout.togglePaymentProvider(showStripeProvider, '.stripe-payment-method');
        checkout.togglePaymentProvider(showStripeProvider, '.stripe-pix-payment-method');
        checkout.togglePaymentProvider(showPaypalProvider, '.paypal-payment-method');
        checkout.togglePaymentProvider(showCreditProvider, '.credit-payment-method');
        checkout.togglePaymentProvider(verotelProvider, '.verotel-payment-method');

        $('.checkout-amount-input').addClass('d-none');
        paymentTitle = trans(data.type);
        const subscriptionInterval = trans_choice('months', numberOfMonths, {'number': numberOfMonths});
        const key = app.currencyPosition === 'left' ? 'Subscribe to' : 'Subscribe to rightAligned';
        paymentDescription = trans(key, {
            'amount': data.amount,
            'currency': app.currencySymbol,
            'username': data.name,
            'subscription_interval': subscriptionInterval
        });
        checkout.toggleCryptoPaymentProviders(false);
    } else if (data.type === 'post-unlock') {
        $('.checkout-amount-input').addClass('d-none');
        paymentTitle = trans('Unlock post');
        paymentDescription = trans('Unlock post for') + ' ' + getWebsiteFormattedAmount(data.amount);
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    } else if (data.type === 'stream-access') {
        $('.checkout-amount-input').addClass('d-none');
        paymentTitle = trans('Join streaming');
        paymentDescription = trans('Join streaming now for') + ' ' + getWebsiteFormattedAmount(data.amount);
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    } else if (data.type === 'message-unlock') {
        $('.checkout-amount-input').addClass('d-none');
        paymentTitle = trans('Unlock message');
        paymentDescription = trans('Unlock message for') + ' ' + getWebsiteFormattedAmount(data.amount);
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    }

    $('#payment-title').text(paymentTitle);
    $('.payment-body .payment-description').toggleClass('d-none', !paymentDescription).text(paymentDescription);
    $('#checkout-amount').val(data.amount);

    if (!data.firstName || !data.lastName || !data.billingAddress || !data.city || !data.state || !data.postcode || !data.country) {
        $('#billingInformation').collapse('show');
    } else {
        $('#billingInformation').collapse('hide');
    }

    if (activeCoupon) {
        $('#coupon-input').val(activeCoupon);
        checkout.applyCoupon();
    }

    selectFirstAvailablePaymentMethod();
}

$(function () {
    // Deposit amount change event listener
    $('#checkout-amount').on('change', function () {
        $(".credit-payment-provider").css("pointer-events", "auto");
        if (!checkout.checkoutAmountValidation()) {
            return false;
        }

        // update payment amount
        checkout.paymentData.amount = parseFloat($('#checkout-amount').val());
        // fetch taxes from BE and update UI
        checkout.updatePaymentSummaryData();
    });

    // Checkout proceed button event listener
    $('.checkout-continue-btn').on('click', function (event) {
        event.preventDefault();
        checkout.initPayment();
    });

    $('#apply-coupon-btn').on('click', function () {
        checkout.applyCoupon();
    });

    $('.custom-control').on('change', function () {
        $('.error-message').hide();
    });

    $('#headingOne').on('click', function () {
        if ($('#headingOne').hasClass('collapsed')) {
            $('.card-header .label-icon').html('<ion-icon name="chevron-up-outline"></ion-icon>');
        } else {
            $('.card-header .label-icon').html('<ion-icon name="chevron-down-outline"></ion-icon>');
        }
    });

    $('#checkout-center').on('show.bs.modal', function (e) {
        configureCheckoutFromTrigger(e.relatedTarget);
    });

    $('#checkout-center').on('hidden.bs.modal', function () {
        $(this).find('#billing-agreement-form').trigger('reset');
        $('.payment-error').addClass('d-none');
        checkout.setProcessing(false);
    });

    // Radio button
    $('.radio-group .radio').on('click', function () {
        $(this).parent().parent().find('.radio').removeClass('selected');
        $(this).addClass('selected');
        $('.payment-error').addClass('d-none');
    });

    // Country change triggers BE-driven quote
    $('.country-select').on('change', function () {
        // keep paymentData.country in sync with selected name
        checkout.validateCountryField();
        checkout.updatePaymentSummaryData();
    });
});

/**
 * Checkout class
 */
var checkout = {
    allowedPaymentProcessors: ['stripe', 'stripe_pix', 'paypal', 'credit', 'nowpayments', 'ccbill', 'paystack', 'yookassa', 'mollie', 'flutterwave', 'coingate', 'xendit', 'paddle', 'cryptocom', 'oxxo', 'mercado', 'verotel', 'razorpay'],
    paymentData: {},

    // keep a reference to last quote request (abort on fast changes)
    _taxQuoteXhr: null,
    _processing: false,

    oneTimePaymentProcessorClasses: [
        '.nowpayments-payment-method',
        '.ccbill-payment-method',
        '.stripe-payment-method',
        '.stripe-pix-payment-method',
        '.paypal-payment-method',
        '.paystack-payment-method',
        '.yookassa-payment-method',
        '.mollie-payment-method',
        '.flutterwave-payment-method',
        '.coingate-payment-method',
        '.xendit-payment-method',
        '.paddle-payment-method',
        '.cryptocom-payment-method',
        '.oxxo-payment-method',
        '.mercado-payment-method',
        '.credit-payment-method',
        '.verotel-payment-method',
        '.razorpay-payment-method',
    ],

    /**
     * Initiates the payment data payload
     */
    initiatePaymentData: function (type, amount, post, recipient, firstName, lastName, billingAddress, country, city, state, postcode, availableCredit, streamId, messageId, coupon) {
        checkout.paymentData = {
            type: type,
            amount: amount,
            post: post,
            recipient: recipient,
            firstName: firstName,
            lastName: lastName,
            billingAddress: billingAddress,
            country: country,
            city: city,
            state: state,
            postcode: postcode,
            availableCredit: availableCredit,
            stream: streamId,
            messageId: messageId,
            coupon: coupon || '',
            couponDiscount: 0,
            couponDiscountType: null,
            couponPaymentMethod: 'all',

            // defaults
            taxes: { data: [], subtotal: "0.00", total: "0.00", taxesTotalAmount: "0.00" },
            totalAmount: typeof amount !== 'undefined' ? parseFloat(amount).toFixed(2) : "0.00",
        };
    },

    /**
     * Updates the payment form
     */
    updatePaymentForm: function () {
        $('#payment-type').val(checkout.paymentData.type);
        $('#post').val(checkout.paymentData.post);
        $('#recipient').val(checkout.paymentData.recipient);
        $('#provider').val(checkout.paymentData.provider);

        $('#paymentFirstName').val(checkout.paymentData.firstName);
        $('#paymentLastName').val(checkout.paymentData.lastName);
        $('#paymentBillingAddress').val(checkout.paymentData.billingAddress);
        $('#paymentCountry').val(checkout.paymentData.country); // name
        $('#paymentState').val(checkout.paymentData.state);
        $('#paymentPostcode').val(checkout.paymentData.postcode);
        $('#paymentCity').val(checkout.paymentData.city);

        // total amount comes from BE quote
        $('#payment-deposit-amount').val(checkout.paymentData.totalAmount);

        // this is BE quote not FE calculated
        $('#paymentTaxes').val(JSON.stringify(checkout.paymentData.taxes));

        $('#stream').val(checkout.paymentData.stream);
        $('#userMessage').val(checkout.paymentData.messageId);
        $('#coupon').val(checkout.paymentData.coupon || '');
    },

    stripe: null,

    /**
     * Instantiates the payment session
     */
    initPayment: function () {
        if (checkout._processing) {
            return false;
        }

        if (!checkout.checkoutAmountValidation()) {
            return false;
        }

        let processor = checkout.getSelectedPaymentMethod();
        if (!processor) {
            $('.payment-error').removeClass('d-none');
        }

        if (processor) {
            $('.paymentProcessorError').hide();
            $('.error-message').hide();
            if (checkout.allowedPaymentProcessors.includes(processor)) {
                if (!checkout.couponAllowsProvider(processor)) {
                    $('.coupon-feedback')
                        .removeClass('text-muted text-success')
                        .addClass('text-danger')
                        .text(trans('This coupon is not valid for the selected payment method.'));
                    return false;
                }

                checkout.setProcessing(true);
                checkout.updatePaymentForm();
                checkout.validateAllFields(() => { $('.payment-button').trigger('click'); });
            }
        }
    },

    /**
     * Runs backend validation check for billing data
     * @param callback
     */
    validateAllFields: function (callback) {
        checkout.clearFormErrors();
        $.ajax({
            type: 'POST',
            data: $('#pp-buyItem').serialize(),
            url: app.baseUrl + '/payment/initiate/validate',
            success: function () {
                callback();
            },
            error: function (result) {
                checkout.setProcessing(false);
                if (result.status === 500) {
                    launchToast('danger', trans('Error'), result.responseJSON.message);
                }
                $.each(result.responseJSON.errors, function (field, error) {
                    let fieldElement = $('.uifield-' + field);
                    fieldElement.addClass('is-invalid');
                    fieldElement.parent().append(
                        `
                            <span class="invalid-feedback" role="alert">
                                <strong>${error}</strong>
                            </span>
                        `
                    );
                });
            }
        });
    },

    /**
     * Clears up dialog (all) form errors
     */
    clearFormErrors: function () {
        $('.invalid-feedback').remove();
        $('input').removeClass('is-invalid');
    },

    applyCoupon: function () {
        const couponCode = ($('#coupon-input').val() || '').trim();

        if (!couponCode) {
            checkout.paymentData.coupon = '';
            checkout.paymentData.couponDiscount = 0;
            checkout.paymentData.couponDiscountType = null;
            checkout.paymentData.couponPaymentMethod = 'all';
            $('#coupon').val('');
            $('.coupon-feedback').removeClass('text-success text-danger').addClass('text-muted').text('');
            checkout.applyCouponPaymentMethodRestriction('all');
            checkout.updatePaymentSummaryData();
            return;
        }

        $.ajax({
            type: 'POST',
            url: app.baseUrl + '/coupon/validate',
            data: {
                coupon: couponCode,
                creator_id: checkout.paymentData.recipient
            },
            success: function (response) {
                checkout.paymentData.coupon = response.coupon_code;
                checkout.paymentData.couponDiscount = response.discount ? Number(response.discount.value) : 0;
                checkout.paymentData.couponDiscountType = response.discount ? response.discount.type : null;
                checkout.paymentData.couponPaymentMethod = response.payment_method || 'all';
                $('#coupon').val(response.coupon_code);
                $('.coupon-feedback').removeClass('text-muted text-danger').addClass('text-success').text(trans('Coupon applied.'));
                checkout.applyCouponPaymentMethodRestriction(checkout.paymentData.couponPaymentMethod);
                checkout.updatePaymentSummaryData();
            },
            error: function (result) {
                checkout.paymentData.coupon = '';
                checkout.paymentData.couponDiscount = 0;
                checkout.paymentData.couponDiscountType = null;
                checkout.paymentData.couponPaymentMethod = 'all';
                $('#coupon').val('');
                const message = result.responseJSON && result.responseJSON.message ? result.responseJSON.message : trans('Invalid coupon.');
                $('.coupon-feedback').removeClass('text-muted text-success').addClass('text-danger').text(message);
                checkout.applyCouponPaymentMethodRestriction('all');
                checkout.updatePaymentSummaryData();
            }
        });
    },

    applyCouponPaymentMethodRestriction: function (paymentMethod) {
        const $methods = $('.checkout-payment-box-methods [class$="-payment-method"], .checkout-payment-box-methods [class*="-payment-method "]');

        $methods.removeClass('coupon-method-hidden');

        if (paymentMethod === 'pix') {
            $methods.not('.stripe-pix-payment-method').addClass('coupon-method-hidden');
        } else if (paymentMethod === 'credit_card') {
            $methods.not('.stripe-payment-method').addClass('coupon-method-hidden');
        }

        $('.coupon-method-hidden .radio').removeClass('selected');
    },

    couponAllowsProvider: function (provider) {
        const paymentMethod = checkout.paymentData.couponPaymentMethod || 'all';

        if (paymentMethod === 'pix') {
            return provider === 'stripe_pix';
        }

        if (paymentMethod === 'credit_card') {
            return provider === 'stripe';
        }

        return true;
    },

    setProcessing: function (processing) {
        checkout._processing = processing;
        const $button = $('.checkout-continue-btn');

        $button.prop('disabled', processing).attr('aria-busy', processing ? 'true' : 'false');
        $button.find('.spinner-border').toggleClass('d-none', !processing);
    },

    /**
     * Returns currently selected payment method
     */
    getSelectedPaymentMethod: function () {
        const paypalProvider = $('.paypal-payment-provider').hasClass('selected');
        const stripeProvider = $('.stripe-payment-provider').hasClass('selected');
        const stripePixProvider = $('.stripe-pix-payment-provider').hasClass('selected');
        const creditProvider = $('.credit-payment-provider').hasClass('selected');
        const nowPaymentsProvider = $('.nowpayments-payment-provider').hasClass('selected');
        const ccbillProvider = $('.ccbill-payment-provider').hasClass('selected');
        const paystackProvider = $('.paystack-payment-provider').hasClass('selected');
        const yookassaProvider = $('.yookassa-payment-provider').hasClass('selected');
        const mollieProvider = $('.mollie-payment-provider').hasClass('selected');
        const flutterwaveProvider = $('.flutterwave-payment-provider').hasClass('selected');
        const coingateProvider = $('.coingate-payment-provider').hasClass('selected');
        const xenditProvider = $('.xendit-payment-provider').hasClass('selected');
        const paddleProvider = $('.paddle-payment-provider').hasClass('selected');
        const cryptocomProvider = $('.cryptocom-payment-provider').hasClass('selected');
        const oxxoProvider = $('.oxxo-payment-provider').hasClass('selected');
        const mercadoProvider = $('.mercado-payment-provider').hasClass('selected');
        const verotelProvider = $('.verotel-payment-provider').hasClass('selected');
        const razorpayProvider = $('.razorpay-payment-provider').hasClass('selected');

        let val = null;
        if (paypalProvider) val = 'paypal';
        else if (stripeProvider) val = 'stripe';
        else if (stripePixProvider) val = 'stripe_pix';
        else if (creditProvider) val = 'credit';
        else if (nowPaymentsProvider) val = 'nowpayments';
        else if (ccbillProvider) val = 'ccbill';
        else if (paystackProvider) val = 'paystack';
        else if (yookassaProvider) val = 'yookassa';
        else if (mollieProvider) val = 'mollie';
        else if (flutterwaveProvider) val = 'flutterwave';
        else if (coingateProvider) val = 'coingate';
        else if (xenditProvider) val = 'xendit';
        else if (paddleProvider) val = 'paddle';
        else if (cryptocomProvider) val = 'cryptocom';
        else if (oxxoProvider) val = 'oxxo';
        else if (mercadoProvider) val = 'mercado';
        else if (verotelProvider) val = 'verotel';
        else if (razorpayProvider) val = 'razorpay';

        if (val) {
            checkout.paymentData.provider = val;
            return val;
        }
        return false;
    },

    /**
     * Validates the amount field
     * @returns {boolean}
     */
    checkoutAmountValidation: function () {
        const checkoutAmount = $('#checkout-amount').val();

        // Tips min-max validation
        if (checkout.paymentData.type === 'tip') {
            if ((checkoutAmount.length > 0 && checkoutAmount >= app.tipMinAmount && checkoutAmount <= app.tipMaxAmount)) {
                $('#checkout-amount').removeClass('is-invalid');
                $('#paypal-deposit-amount').val(checkoutAmount);

                // credit gating will be updated after BE quote too
                if (checkout.paymentData.availableCredit < checkoutAmount) {
                    $(".credit-payment-provider").css("pointer-events", "none");
                }
                return true;
            } else {
                $('#checkout-amount').addClass('is-invalid');
                return false;
            }
        }

        return true;
    },

    /**
     * Validates FN field
     */
    validateFirstNameField: function () {
        let firstNameField = $('input[name="firstName"]');
        checkout.paymentData.firstName = firstNameField.val();
    },

    /**
     * Validates LN field
     */
    validateLastNameField: function () {
        let lastNameField = $('input[name="lastName"]');
        checkout.paymentData.lastName = lastNameField.val();
    },

    /**
     * Validates Adress field
     */
    validateBillingAddressField: function () {
        let billingAddressField = $('textarea[name="billingAddress"]');
        checkout.paymentData.billingAddress = billingAddressField.val();
    },

    /**
     * Validates city field
     */
    validateCityField: function () {
        let cityField = $('input[name="billingCity"]');
        checkout.paymentData.city = cityField.val();
    },

    /**
     * Validates state field
     */
    validateStateField: function () {
        let stateField = $('input[name="billingState"]');
        checkout.paymentData.state = stateField.val();
    },

    /**
     * Validates the ZIP code
     */
    validatePostcodeField: function () {
        let postcodeField = $('input[name="billingPostcode"]');
        checkout.paymentData.postcode = postcodeField.val();
    },

    /**
     * Validates the country field
     */
    validateCountryField: function () {
        var $countryField = $('.country-select');
        var selected = $countryField.find('option:selected');
        var name = (selected && selected.length) ? (selected.text() || '').trim() : '';

        if (name) {
            $countryField.removeClass('is-invalid');
            checkout.paymentData.country = name;
        } else {
            checkout.paymentData.country = '';
        }
    },

    /**
     * Prefills user billing data, if available
     */
    prefillBillingDetails: function () {
        $('input[name="firstName"]').val(checkout.paymentData.firstName);
        $('input[name="lastName"]').val(checkout.paymentData.lastName);
        $('input[name="billingCity"]').val(checkout.paymentData.city);
        $('input[name="billingState"]').val(checkout.paymentData.state);
        $('input[name="billingPostcode"]').val(checkout.paymentData.postcode);
        $('textarea[name="billingAddress"]').val(checkout.paymentData.billingAddress);
    },

    /**
     * Updates user details
     */
    updateUserDetails: function (userAvatar, username, name) {
        $('.payment-body .user-avatar').attr('src', userAvatar);
        $('.payment-body .name').text(name);
        $('.payment-body .username').text('@' + username);
    },

    /**
     * Fetches list of countries (no need to attach taxes to options anymore)
     * Preselects by checkout.paymentData.country (name) if provided
     * Then triggers BE quote once options exist
     */
    fillCountrySelectOptions: function () {
        $.ajax({
            type: 'GET',
            url: app.baseUrl + '/countries',
            success: function (result) {
                if (result && result.countries && result.countries.length > 0) {
                    let $select = $('.country-select');

                    // destroy selectize before rebuilding options
                    if ($select[0] && $select[0].selectize) {
                        $select[0].selectize.destroy();
                    }

                    $select.empty();

                    $.each(result.countries, function (i, item) {
                        const isSelected = checkout.paymentData.country && checkout.paymentData.country === item.name;
                        $select.append($('<option>', {
                            value: item.id, // keep id as value
                            text: item.name,
                            selected: isSelected
                        }));
                    });

                    checkout.initCountrySelectize();

                    // sync paymentData.country + update quote
                    checkout.updatePaymentSummaryData();
                }
            }
        });
    },

    /**
     * BE-driven: fetch quote and render taxes/totals.
     * - Called on amount change, country change, and after countries load.
     */
    updatePaymentSummaryData: function () {
        const $countrySelect = $('.country-select');
        const countryName = $countrySelect.val()
            ? ($countrySelect.find('option:selected').text() || '').trim()
            : null;

        // Keep paymentData.country in sync (important for form submit)
        checkout.paymentData.country = countryName || '';

        // Base amount: tips/deposits use input; for other types BE overrides internally
        const baseAmount = checkout.getDiscountedBaseAmount();

        // If no country selected, clear taxes UI and show base totals only
        if (!countryName) {
            checkout.paymentData.taxes = { data: [], subtotal: baseAmount.toFixed(2), total: baseAmount.toFixed(2), taxesTotalAmount: "0.00" };
            checkout.paymentData.totalAmount = baseAmount.toFixed(2);

            $('.taxes-details').empty();
            $('.subtotal-amount b').html(getWebsiteFormattedAmount(baseAmount.toFixed(2)));
            $('.total-amount b').html(getWebsiteFormattedAmount(baseAmount.toFixed(2)));

            $('.available-credit').html('(' + getWebsiteFormattedAmount(checkout.paymentData.availableCredit) + ')');
            if (parseFloat(checkout.paymentData.availableCredit || 0) < baseAmount) {
                $(".credit-payment-provider").css("pointer-events", "none");
            } else {
                $(".credit-payment-provider").css("pointer-events", "auto");
            }
            return;
        }

        const payload = {
            transaction_type: checkout.paymentData.type,
            amount: baseAmount, // used only for tip/deposit; ignored otherwise
            recipient_user_id: checkout.paymentData.recipient,
            post_id: checkout.paymentData.post,
            user_message_id: checkout.paymentData.messageId,
            stream: checkout.paymentData.stream,
            country: countryName,
        };

        // Abort previous quote request (avoid race conditions)
        if (checkout._taxQuoteXhr && checkout._taxQuoteXhr.readyState !== 4) {
            checkout._taxQuoteXhr.abort();
        }

        checkout._taxQuoteXhr = $.ajax({
            type: 'POST',
            url: app.baseUrl + '/payment/taxes/quote',
            data: payload,
            success: function (res) {
                const quote = (res && res.quote) ? res.quote : null;
                if (!quote) return;

                checkout.paymentData.taxes = quote;
                checkout.paymentData.totalAmount = baseAmount;

                $('.taxes-details').empty();

                (quote.data || []).forEach(function (t) {
                    if (t.hidden) return;
                    const item =
                        `<div class="row ml-2">
                            <span class="col-sm left">${getTaxDescription(t.taxName, t.taxPercentage, t.taxType)}</span>
                            <span class="country-tax col-sm right text-right"><b>${getWebsiteFormattedAmount(t.taxAmount)}</b></span>
                         </div>`;
                    $('.taxes-details').append(item);
                });

                $('.subtotal-amount b').html(getWebsiteFormattedAmount(quote.subtotal));
                $('.total-without-tax-amount b').html(getWebsiteFormattedAmount(quote.netSubtotal));
                $('.total-amount b').html(getWebsiteFormattedAmount(quote.total));

                // Credit gating uses total
                $('.available-credit').html('(' + getWebsiteFormattedAmount(checkout.paymentData.availableCredit) + ')');
                if (parseFloat(checkout.paymentData.availableCredit || 0) < parseFloat(quote.total || 0)) {
                    $(".credit-payment-provider").css("pointer-events", "none");
                } else {
                    $(".credit-payment-provider").css("pointer-events", "auto");
                }
            }
        });
    },

    getDiscountedBaseAmount: function () {
        let amount = parseFloat(checkout.paymentData.amount || 0);
        const discountValue = Number(checkout.paymentData.couponDiscount || 0);

        if (discountValue > 0 && checkout.paymentData.couponDiscountType === 'percent') {
            amount -= amount * (discountValue / 100);
        } else if (discountValue > 0 && checkout.paymentData.couponDiscountType === 'fixed') {
            amount -= discountValue;
        }

        return Math.max(0, amount);
    },

    toggleCryptoPaymentProviders: function (toggle) {
        let nowPaymentsPaymentMethod = $('.nowpayments-payment-method');
        if (toggle) {
            if (nowPaymentsPaymentMethod.hasClass('d-none')) nowPaymentsPaymentMethod.removeClass('d-none');
        } else {
            if (!nowPaymentsPaymentMethod.hasClass('d-none')) nowPaymentsPaymentMethod.addClass('d-none');
        }
    },

    togglePaymentProvider: function (toggle, paymentMethodClass) {
        let paymentMethod = $(paymentMethodClass);
        if (toggle) {
            if (paymentMethod.hasClass('d-none')) paymentMethod.removeClass('d-none');
        } else {
            if (!paymentMethod.hasClass('d-none')) paymentMethod.addClass('d-none');
        }
    },

    togglePaymentProviders: function (toggle, paymentMethodClasses) {
        paymentMethodClasses.forEach(function (paymentMethodClass) {
            checkout.togglePaymentProvider(toggle, paymentMethodClass);
        });
    },

    initCountrySelectize: function () {
        let $select = $('.country-select');
        if (!$select.length) return;
        if (typeof $select.selectize !== 'function') return;

        // already initialized
        if ($select[0] && $select[0].selectize) return;

        $select.selectize({
            placeholder: trans("Select a country"),
            allowEmptyOption: true,
            searchField: ['text'],
            sortField: [{ field: 'text', direction: 'asc' }],
            closeAfterSelect: true,
            onChange: function () {
                // sync paymentData.country (uses selected text)
                checkout.validateCountryField();
                // fetch BE quote + update UI
                checkout.updatePaymentSummaryData();
            }
        });
    },

};
