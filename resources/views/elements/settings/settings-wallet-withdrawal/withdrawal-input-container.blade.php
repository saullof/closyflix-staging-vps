<div class="input-group mb-3 mt-3 withdrawals-form-wrapper">
    <div class="input-group-prepend">
        <span class="input-group-text" id="amount-label">@include('elements.icon',['icon'=>'cash-outline','variant'=>'medium'])</span>
    </div>
    <input class="form-control"
           placeholder="{{ \App\Providers\PaymentsServiceProvider::getWithdrawalAmountLimitations() }}"
           aria-label="Username"
           aria-describedby="amount-label"
           id="withdrawal-amount"
           type="number"
           min="{{\App\Providers\PaymentsServiceProvider::getWithdrawalMinimumAmount()}}"
           step="1"
           max="{{\App\Providers\PaymentsServiceProvider::getWithdrawalMaximumAmount()}}">
    <div class="invalid-feedback">{{__('Please enter a valid amount')}}</div>
    <div class="input-group mb-3 mt-3">
        <div class="d-flex flex-row w-100">
            <div class="form-group w-50 pr-2">
                <label for="paymentMethod">{{__('Payment method')}}</label>
                <select class="form-control" id="payment-methods" name="payment-methods">
                    @foreach(\App\Providers\PaymentsServiceProvider::getWithdrawalMethodOptions() as $paymentMethodKey => $paymentMethodLabel)
                        <option value="{{ $paymentMethodKey }}">{{ $paymentMethodLabel }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    {{__('Choose how you want to receive this withdrawal.')}}
                </small>
            </div>
            <div class="form-group w-50 pl-2 update-stripe-connect-box d-none">
                <label id="update-stripe-connect-label" for="update-stripe-connect">{{__('Update details')}}</label>
                <a href="{{route('withdrawals.onboarding')}}">
                    <button id="update-stripe-connect" class="btn btn-primary btn-block rounded mr-0">{{__('Update')}}</button>
                </a>
            </div>
            <div class="form-group w-50 pl-2 saved-payout-account-box d-none">
                <label for="withdrawal-payout-account-id">{{__('Bank payout account')}}</label>
                <select class="form-control" id="withdrawal-payout-account-id" name="payout-account-id">
                    <option value="">{{__('Select a saved payout account')}}</option>
                    @foreach($payoutAccounts as $payoutAccount)
                        <option
                            value="{{ $payoutAccount->id }}"
                            data-label="{{ $payoutAccount->display_label }}"
                            data-holder="{{ $payoutAccount->account_holder_name }}"
                            data-iban="{{ $payoutAccount->masked_iban }}"
                            data-bank="{{ $payoutAccount->bank_name }}"
                            data-country="{{ $payoutAccount->country?->name }}"
                            {{ $payoutAccount->is_default ? 'selected' : '' }}
                        >
                            {{ $payoutAccount->display_label }} - {{ $payoutAccount->masked_iban }}
                        </option>
                    @endforeach
                </select>
                <span class="invalid-feedback" role="alert">
                    {{__('Please select a saved payout account.')}}
                </span>
                <small class="form-text text-muted saved-payout-account-helper d-none">
                    @if($payoutAccounts->count())
                        {{__('Saved bank details.')}}
                        <a
                            class="open-payout-account-modal"
                            href="#"
                            data-toggle="modal"
                            data-target="#payout-account-dialog"
                            data-mode="manage"
                        >
                            {{__('Manage accounts')}}
                        </a>
                    @else
                        {{__('No bank payout account saved yet.')}}
                        <a
                            class="open-payout-account-modal"
                            href="#"
                            data-toggle="modal"
                            data-target="#payout-account-dialog"
                            data-mode="create"
                        >
                            {{__('Add one now')}}
                        </a>
                    @endif
                </small>
            </div>
            <div class="form-group w-50 pl-2 input-label">
                <label id="payment-identifier-label" for="withdrawal-payment-identifier">{{__("Payment account")}}</label>
                <input class="form-control" type="text" id="withdrawal-payment-identifier" name="payment-identifier">
                <span class="invalid-feedback" role="alert">
                    {{__('Please add your payment account details.')}}
                </span>
            </div>
        </div>
        <div class="saved-payout-account-preview card bg-light border-0 d-none w-100 mb-3">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="font-weight-bold saved-payout-account-preview-label"></div>
                        <div class="small text-muted saved-payout-account-preview-details"></div>
                    </div>
                    <a
                        class="btn btn-sm btn-outline-secondary saved-payout-account-edit-link mb-0"
                        href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw']) }}"
                    >
                        {{__('Edit')}}
                    </a>
                </div>
            </div>
        </div>
        <div class="form-group w-100 input-message">
            <label id="withdrawal-message-label" for="withdrawal-message">{{__('Message (Optional)')}}</label>
            <textarea placeholder="{{__('Payout details, notes, etc')}}" class="form-control" id="withdrawal-message" rows="2"></textarea>
            <span class="invalid-feedback" role="alert">
                {{__('Please add your withdrawal notes: EG: Paypal or Bank account.')}}
            </span>
        </div>
    </div>
    <div class="stripe-connect-label d-none">
        @if(!Auth::user()->country_id)
            <span>{{__("You must set the country on your profile before you can start onboarding and withdraw money")}}</span>
        @elseif(!Auth::user()->stripe_onboarding_verified)
            <span>{{__("We're using Stripe to get you paid quickly and keep your personal and payment information secure. Thousands of companies around the world trust Stripe to process payments for their users. Set up a Stripe account to get paid with us")}}</span>
        @endif
    </div>
    <div class="payment-error error text-danger d-none mt-3">{{__('Add all required info')}}</div>
    <div class="stripe-connect-buttons d-none w-100">
        @if(!Auth::user()->country_id)
            <div class="mt-3">
                <div>
                    <a href="{{route('my.settings',['type'=>'profile'])}}">
                        <button class="btn btn-primary btn-block rounded mr-0">{{__('Set your country')}}</button>
                    </a>
                </div>
            </div>
        @elseif(!Auth::user()->stripe_onboarding_verified)
            <div class="mt-3">
                <div>
                    <a href="{{route('withdrawals.onboarding')}}">
                        <button class="btn btn-primary btn-block rounded mr-0">{{!Auth::user()->stripe_account_id ? __('Start onboarding') : __('Update details')}}</button>
                    </a>
                </div>
            </div>
        @endif
    </div>
    <button class="btn btn-primary btn-block rounded mr-0 withdrawal-continue-btn" type="submit">{{__('Request withdrawal')}}</button>
</div>
