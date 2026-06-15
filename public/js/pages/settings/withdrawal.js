/**
 * Money settings component
 */
"use strict";
/* global app, launchToast, trans, updateButtonState, user, onEnter, withdrawalPayoutDetails */

$(function () {
    Wallet.handleTaxInformationEnforce();
    Wallet.setPaymentMethodUi();
    Wallet.handleStripeConnect();
    Wallet.updateSavedPayoutAccountPreview();
    Wallet.initCountrySelectize();
    // Deposit amount change event listener
    $('#withdrawal-amount').on('change', function () {
        if (!Wallet.withdrawalAmountValidation()) {
            return false;
        }
    });
    // Checkout proceed button event listener
    $('.withdrawal-continue-btn').on('click', function () {
        Wallet.initWithdrawal();
    });
    $('.custom-control').on('change', function () {
        $('.withdrawal-error-message').hide();
    });
    $('#payment-methods').on('change', function() {
        Wallet.setPaymentMethodUi();
        Wallet.handleStripeConnect();
    });
    $('#withdrawal-payout-account-id').on('change', function () {
        Wallet.updateSavedPayoutAccountPreview();
        $(this).removeClass('is-invalid');
    });
    $('#withdrawal-payout-account-id, #withdrawal-payment-identifier, #withdrawal-message, #withdrawal-pix-key-type, #withdrawal-pix-beneficiary-name').on('change keyup', function () {
        $(this).removeClass('is-invalid');
    });

    $('.open-payout-account-modal').on('click', function (event) {
        event.preventDefault();
        Wallet.preparePayoutAccountModal($(this).data('mode'));
        $('#payout-account-dialog').modal('show');
    });

    $('#payout-account-dialog').on('hidden.bs.modal', function () {
        Wallet.resetPayoutAccountModalUrl();
        Wallet.preparePayoutAccountModal('create');
    });

    $('#payout-account-dialog').on('shown.bs.modal', function () {
        Wallet.initCountrySelectize();
    });

    if(window.payoutAccountModalShouldOpen) {
        $('#payout-account-dialog').modal('show');
    }

    onEnter('.withdrawals-form-wrapper', function () {
        Wallet.initWithdrawal();
    });

});

var Wallet = {

    /**
     * Instantiate withdrawal request
     * @returns {boolean}
     */
    initWithdrawal: function () {

        const provider = Wallet.getSelectedProvider();
        if(user.dac7_tax_required){return false;}

        if(provider === 'stripe_connect') {
            if (!user.stripe_connect_verified || !user.user_country_id) {
                return false;
            }
        }

        let submitButton = $('.withdrawal-continue-btn');
        updateButtonState('loading',submitButton, trans('Request withdrawal'),'white');

        if(!Wallet.withdrawalAmountValidation()){
            updateButtonState('loaded',submitButton, trans('Request withdrawal'));
            return false;
        }

        $('.withdrawal-error-message').hide();
        $.ajax({
            type: 'POST',
            data: {
                amount: $('#withdrawal-amount').val(),
                message: $('#withdrawal-message').val(),
                identifier: $('#withdrawal-payment-identifier').val(),
                pix_key_type: $('#withdrawal-pix-key-type').val(),
                pix_beneficiary_name: $('#withdrawal-pix-beneficiary-name').val(),
                payout_account_id: $('#withdrawal-payout-account-id').val(),
                method: provider,
            },
            url: app.baseUrl + '/withdrawals/request',
            success: function (result) {
                // eslint-disable-next-line no-undef
                const msgType = result.success ? 'success' : 'danger';
                const msgLabel = result.success ? trans('Success') : trans('Error');
                launchToast(msgType, msgLabel, result.message);

                // append new amounts
                $('.wallet-total-amount').html(result.totalAmount);
                $('.wallet-pending-amount').html(result.pendingBalance);

                // clear inputs
                $('#withdrawal-amount').val('');
                if (Wallet.shouldPersistMethodDetails()) {
                    Wallet.rememberCurrentMethodDetails();
                } else {
                    $('#withdrawal-message').val('');
                    $('#withdrawal-payment-identifier').val('');
                }

                // Clearing up err messages
                $('#withdrawal-amount').removeClass('is-invalid');
                $('#withdrawal-message').removeClass('is-invalid');
                $('#withdrawal-payment-identifier').removeClass('is-invalid');
                $('#withdrawal-payout-account-id').removeClass('is-invalid');

                updateButtonState('loaded',submitButton, trans('Request withdrawal'));

            },
            error: function (result) {
                if(result.status === 429) {
                    launchToast('danger', trans('Error'), result.responseJSON.message || trans('Too many attempts. Please try again later.'));
                } else if((result.status === 422 || result.status === 500) && result.responseJSON && result.responseJSON.errors) {
                    $.each(result.responseJSON.errors, function (field) {
                        if (field === 'amount') {
                            $('#withdrawal-amount').addClass('is-invalid');
                        }
                        if(field === 'message'){
                            $('#withdrawal-message').addClass('is-invalid');
                        }
                        if(field === 'identifier'){
                            $('#withdrawal-payment-identifier').addClass('is-invalid');
                        }
                        if(field === 'payout_account_id'){
                            $('#withdrawal-payout-account-id').addClass('is-invalid');
                        }
                        if(field === 'pix_key_type'){
                            $('#withdrawal-pix-key-type').addClass('is-invalid');
                        }
                        if(field === 'pix_beneficiary_name'){
                            $('#withdrawal-pix-beneficiary-name').addClass('is-invalid');
                        }
                    });
                }

                if(result.responseJSON && result.responseJSON.message) {
                    launchToast('danger', trans('Error'), result.responseJSON.message);
                }
                updateButtonState('loaded',submitButton, trans('Request withdrawal'));
            }
        });
    },

    /**
     * Validates the withdrawal amount
     * @returns {boolean}
     */
    withdrawalAmountValidation: function () {
        let withdrawalAmount = $('#withdrawal-amount').val();
        if (withdrawalAmount.length === 0
            || (withdrawalAmount.length > 0 && (parseFloat(withdrawalAmount) < parseFloat(app.withdrawalsMinAmount)
                || parseFloat(withdrawalAmount) > parseFloat(app.withdrawalsMaxAmount)))) {
            $('#withdrawal-amount').addClass('is-invalid');
            return false;
        } else {
            $('#withdrawal-amount').removeClass('is-invalid');
            return true;
        }
    },

    /**
     * Get withdrawal payment identifier based on payment method from dropdown
     * @returns {string}
     */
    getPaymentIdentifierTitle: function() {
        let title;
        switch (Wallet.getSelectedProvider()) {
        case 'bank_transfer':
            title = trans('Bank payout account');
            break;
        case 'paypal':
            title = trans('PayPal email');
            break;
        case 'pix':
            title = trans('PIX key');
            break;
        case 'crypto':
            title = trans('Wallet address');
            break;
        case 'custom':
            title = trans('Payout destination');
            break;
        default:
            title = trans('Payment account');
            break;
        }
        return title;
    },

    getSelectedProvider: function () {
        return $('#payment-methods').val();
    },

    requiresSavedPayoutAccount: function () {
        return Wallet.getSelectedProvider() === 'bank_transfer';
    },

    isCustomMethod: function () {
        return Wallet.getSelectedProvider() === 'custom';
    },

    shouldShowWithdrawalMessageBox: function () {
        const provider = Wallet.getSelectedProvider();
        return provider && provider !== 'stripe_connect';
    },

    shouldPersistMethodDetails: function () {
        return ['paypal', 'pix', 'crypto', 'custom'].includes(Wallet.getSelectedProvider());
    },

    getStoredMethodDetails: function (methodKey) {
        if (typeof withdrawalPayoutDetails === 'undefined' || !withdrawalPayoutDetails) {
            return {};
        }

        return withdrawalPayoutDetails[methodKey] || {};
    },

    rememberCurrentMethodDetails: function () {
        const methodKey = Wallet.getSelectedProvider();
        if (!['paypal', 'pix', 'crypto', 'custom'].includes(methodKey)) {
            return;
        }

        if (typeof withdrawalPayoutDetails === 'undefined' || !withdrawalPayoutDetails) {
            window.withdrawalPayoutDetails = {};
        }

        withdrawalPayoutDetails[methodKey] = {
            identifier: $('#withdrawal-payment-identifier').val(),
            message: $('#withdrawal-message').val(),
            pix_key_type: $('#withdrawal-pix-key-type').val(),
            pix_beneficiary_name: $('#withdrawal-pix-beneficiary-name').val()
        };
    },

    hydrateStoredMethodDetails: function () {
        const methodKey = Wallet.getSelectedProvider();
        if (!['paypal', 'pix', 'crypto', 'custom'].includes(methodKey)) {
            return;
        }

        const details = Wallet.getStoredMethodDetails(methodKey);
        $('#withdrawal-payment-identifier').val(details.identifier || '');
        $('#withdrawal-message').val(details.message || '');
        $('#withdrawal-pix-key-type').val(details.pix_key_type || 'cpf');
        $('#withdrawal-pix-beneficiary-name').val(details.pix_beneficiary_name || '');
    },

    setPaymentMethodUi: function () {
        let paymentIdentifierTitle = trans(Wallet.getPaymentIdentifierTitle());
        $('#payment-identifier-label').text(paymentIdentifierTitle);

        const usesSavedPayoutAccounts = Wallet.requiresSavedPayoutAccount();
        const hasSavedPayoutAccounts = $('#withdrawal-payout-account-id option').length > 1;
        const isCustomMethod = Wallet.isCustomMethod();
        const isPixMethod = Wallet.getSelectedProvider() === 'pix';

        $('.saved-payout-account-box').toggleClass('d-none', !usesSavedPayoutAccounts);
        $('.input-label').toggleClass('d-none', usesSavedPayoutAccounts);
        $('.pix-withdrawal-fields').toggleClass('d-none', !isPixMethod);
        $('.saved-payout-account-helper').toggleClass('d-none', !usesSavedPayoutAccounts);
        $('.saved-payout-account-preview').toggleClass('d-none', !(usesSavedPayoutAccounts && hasSavedPayoutAccounts));
        $('.custom-withdrawal-message-box').toggleClass('d-none', !Wallet.shouldShowWithdrawalMessageBox());

        if(usesSavedPayoutAccounts) {
            $('#withdrawal-message-label').text(trans('Notes (Optional)'));
            $('#withdrawal-message').attr('placeholder', trans('Optional notes for this bank transfer'));
        } else if (Wallet.getSelectedProvider() === 'paypal') {
            $('#withdrawal-message-label').text(trans('Notes (Optional)'));
            $('#withdrawal-message').attr('placeholder', trans('Optional notes for this PayPal payout'));
        } else if (isPixMethod) {
            $('#withdrawal-message-label').text(trans('Notes (Optional)'));
            $('#withdrawal-message').attr('placeholder', trans('Optional notes for this PIX payout'));
        } else if (Wallet.getSelectedProvider() === 'crypto') {
            $('#withdrawal-message-label').text(trans('Notes (Optional)'));
            $('#withdrawal-message').attr('placeholder', trans('Optional notes for this crypto payout'));
        } else if (isCustomMethod) {
            $('#withdrawal-message-label').text(trans('Details (Optional)'));
            $('#withdrawal-message').attr('placeholder', trans('Add any extra payout details or notes'));
        } else {
            $('#withdrawal-message-label').text(trans('Message (Optional)'));
            $('#withdrawal-message').attr('placeholder', trans('Payout details, notes, etc'));
        }

        if(!usesSavedPayoutAccounts) {
            $('#withdrawal-payout-account-id').removeClass('is-invalid');
        }

        Wallet.updateSavedPayoutAccountPreview();
        Wallet.hydrateStoredMethodDetails();
    },

    handleStripeConnect: function() {
        const provider = Wallet.getSelectedProvider();
        if(provider === 'stripe_connect') {
            $('.saved-payout-account-box').addClass('d-none');
            $('.input-label').addClass('d-none');
            $('.input-message').addClass('d-none');
            $('.pix-withdrawal-fields').addClass('d-none');
            $('.custom-withdrawal-message-box').addClass('d-none');
            if(!user.stripe_connect_verified || !user.user_country_id) {
                $('.stripe-connect-label').removeClass('d-none');
                $('.stripe-connect-buttons').removeClass('d-none');
                $('.withdrawal-continue-btn').addClass('disabled');
                $('#withdrawal-amount').attr("disabled", true);
            }
            if(user.stripe_connect_verified) {
                $('.update-stripe-connect-box').removeClass('d-none');
            }
            $('.stripe-connect-pending-onboarding').removeClass('d-none');
        } else {
            Wallet.setPaymentMethodUi();
            $('.input-message').removeClass('d-none');
            $('.withdrawal-continue-btn').removeClass('disabled');
            $('#withdrawal-amount').attr("disabled", false);
            $('.stripe-connect-label').addClass('d-none');
            $('.stripe-connect-buttons').addClass('d-none');
            $('.update-stripe-connect-box').addClass('d-none');
            $('.stripe-connect-pending-onboarding').addClass('d-none');

            if(Wallet.requiresSavedPayoutAccount() && $('#withdrawal-payout-account-id option').length <= 1) {
                $('.withdrawal-continue-btn').addClass('disabled');
            }
        }
    },

    updateSavedPayoutAccountPreview: function () {
        const selectedOption = $('#withdrawal-payout-account-id option:selected');
        const payoutAccountId = selectedOption.val();

        if(!Wallet.requiresSavedPayoutAccount() || !payoutAccountId) {
            $('.saved-payout-account-preview').addClass('d-none');
            return;
        }

        const label = selectedOption.data('label') || '';
        const details = [
            selectedOption.data('holder'),
            selectedOption.data('iban'),
            selectedOption.data('bank'),
            selectedOption.data('country')
        ].filter(Boolean).join(' • ');

        $('.saved-payout-account-preview-label').text(label);
        $('.saved-payout-account-preview-details').text(details);
        $('.saved-payout-account-edit-link').attr('href', app.baseUrl + '/my/settings/wallet?active=withdraw&editPayoutAccount=' + payoutAccountId);
        $('.saved-payout-account-preview').removeClass('d-none');
    },

    initCountrySelectize: function () {
        let $select = $('#payout-account-country.country-select');
        if (!$select.length) return;
        if (typeof $select.selectize !== 'function') return;

        if ($select[0] && $select[0].selectize) return;

        $select.selectize({
            placeholder: trans('Select country'),
            allowEmptyOption: true,
            searchField: ['text'],
            sortField: [{ field: 'text', direction: 'asc' }],
            closeAfterSelect: true
        });
    },

    preparePayoutAccountModal: function (mode) {
        if(mode === 'create' || mode === 'manage') {
            Wallet.resetPayoutAccountForm();
        }
    },

    resetPayoutAccountForm: function () {
        const $form = $('#payout-account-form');
        if (!$form.length) return;

        const formElement = $form.get(0);
        if (formElement) {
            formElement.reset();
        }

        $('#payout-account-mode').val('create');
        $('#payout-account-id').val('');
        $('#payout-account-label').val('');
        $('#payout-account-holder-name').val('');
        $('#payout-account-iban').val('');
        $('#payout-account-swift').val('');
        $('#payout-account-bank-name').val('');
        $('#payout-account-bank-address').val('');
        $('#payout-account-default').prop('checked', false);
        $('#payout-account-form-title').text($('#payout-account-form-title').data('create-label'));
        $('#payout-account-submit').text($('#payout-account-submit').data('create-label'));
        $('#payout-account-cancel').addClass('d-none');

        $form.find('.is-invalid').removeClass('is-invalid');

        const countrySelect = $('#payout-account-country').get(0);
        if (countrySelect && countrySelect.selectize) {
            countrySelect.selectize.clear(true);
        } else {
            $('#payout-account-country').val('');
        }
    },

    resetPayoutAccountModalUrl: function () {
        if(!window.history || !window.history.replaceState) {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.delete('editPayoutAccount');

        if(url.searchParams.get('active') !== 'withdraw') {
            url.searchParams.set('active', 'withdraw');
        }

        const nextUrl = url.searchParams.toString() ? url.pathname + '?' + url.searchParams.toString() : url.pathname;
        window.history.replaceState({}, '', nextUrl);
    },

    handleTaxInformationEnforce() {
        if(user.dac7_tax_required) {
            $('.withdrawal-continue-btn').addClass('disabled');
        }
    }
};
