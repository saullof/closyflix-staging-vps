@php
    $editingPayoutAccountId = old('payout_account_id', $editingPayoutAccount?->id);
    $formActionLabel = $editingPayoutAccountId ? __('Update payout account') : __('Save payout account');
    $defaultPayoutAccount = $payoutAccounts->firstWhere('is_default', true) ?: $payoutAccounts->first();
    $payoutAccountFormErrors = $errors->hasAny([
        'label',
        'accountHolderName',
        'iban',
        'swiftBic',
        'bankName',
        'bankAddress',
        'countryId',
        'payout_account_id',
    ]);
@endphp

<div class="modal fade" tabindex="-1" role="dialog" id="payout-account-dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('Saved bank payout accounts')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($payoutAccounts->count())
                    <div class="mb-4">
                        <div class="font-weight-bold mb-2">{{__('Your saved accounts')}}</div>
                        <div class="row">
                            @foreach($payoutAccounts as $payoutAccount)
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="font-weight-bold">{{ $payoutAccount->display_label }}</div>
                                                <div class="text-muted small">{{__('IBAN')}}: {{ $payoutAccount->masked_iban }}</div>
                                            </div>
                                            @if($payoutAccount->is_default)
                                                <span class="badge badge-primary">{{__('Default')}}</span>
                                            @endif
                                        </div>

                                        <div class="small mt-3">
                                            @foreach($payoutAccount->summary_lines as $line)
                                                <div>{{ $line }}</div>
                                            @endforeach
                                        </div>

                                        <div class="d-flex flex-column flex-sm-row mt-3">
                                            <a class="btn btn-sm btn-outline-primary mb-0 mr-sm-2" href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw', 'editPayoutAccount' => $payoutAccount->id]) }}">
                                                {{__('Edit')}}
                                            </a>
                                            <form method="POST" action="{{ route('my.settings.payout-accounts.delete', ['payoutAccount' => $payoutAccount->id]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Delete this payout account?')) }})">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger mb-0" type="submit">{{__('Delete')}}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form id="payout-account-form" method="POST" action="{{ route('my.settings.payout-accounts.save') }}">
                    @csrf
                    <input id="payout-account-mode" type="hidden" name="payout_account_mode" value="{{ $editingPayoutAccountId ? 'edit' : 'create' }}">
                    <input id="payout-account-id" type="hidden" name="payout_account_id" value="{{ $editingPayoutAccountId }}">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div
                                id="payout-account-form-title"
                                class="font-weight-bold"
                                data-create-label="{{ __('Add bank payout account') }}"
                                data-edit-label="{{ __('Edit bank payout account') }}"
                            >
                                {{ $editingPayoutAccountId ? __('Edit bank payout account') : __('Add bank payout account') }}
                            </div>
                            <div id="payout-account-form-subtitle" class="text-muted small">{{__('These details are only used for your bank transfer withdrawals.') }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="payout-account-label">{{__('Label (Optional)')}}</label>
                                <input
                                    id="payout-account-label"
                                    name="label"
                                    type="text"
                                    class="form-control @error('label') is-invalid @enderror"
                                    placeholder="{{__('Main EUR bank account')}}"
                                    value="{{ old('label', $editingPayoutAccount?->label) }}"
                                >
                                @error('label')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="payout-account-holder-name">{{__('Recipient full name')}}</label>
                                <input
                                    id="payout-account-holder-name"
                                    name="accountHolderName"
                                    type="text"
                                    class="form-control @error('accountHolderName') is-invalid @enderror"
                                    value="{{ old('accountHolderName', $editingPayoutAccount?->account_holder_name) }}"
                                >
                                @error('accountHolderName')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="payout-account-iban">{{__('IBAN')}}</label>
                                <input
                                    id="payout-account-iban"
                                    name="iban"
                                    type="text"
                                    class="form-control @error('iban') is-invalid @enderror"
                                    value="{{ old('iban', $editingPayoutAccount?->iban) }}"
                                >
                                @error('iban')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="payout-account-swift">{{__('SWIFT / BIC')}}</label>
                                <input
                                    id="payout-account-swift"
                                    name="swiftBic"
                                    type="text"
                                    class="form-control @error('swiftBic') is-invalid @enderror"
                                    value="{{ old('swiftBic', $editingPayoutAccount?->swift_bic) }}"
                                >
                                @error('swiftBic')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="payout-account-bank-name">{{__('Bank name')}}</label>
                                <input
                                    id="payout-account-bank-name"
                                    name="bankName"
                                    type="text"
                                    class="form-control @error('bankName') is-invalid @enderror"
                                    value="{{ old('bankName', $editingPayoutAccount?->bank_name) }}"
                                >
                                @error('bankName')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="payout-account-country">{{__('Country (Optional)')}}</label>
                                <select id="payout-account-country" name="countryId" class="form-control country-select @error('countryId') is-invalid @enderror">
                                    <option value="">{{__('Select country')}}</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ (string) old('countryId', $editingPayoutAccount?->country_id) === (string) $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('countryId')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label for="payout-account-bank-address">{{__('Bank address (Optional)')}}</label>
                                <textarea
                                    id="payout-account-bank-address"
                                    name="bankAddress"
                                    rows="2"
                                    class="form-control @error('bankAddress') is-invalid @enderror"
                                >{{ old('bankAddress', $editingPayoutAccount?->bank_address) }}</textarea>
                                @error('bankAddress')
                                    <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="custom-control custom-checkbox mb-3">
                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="payout-account-default"
                                    name="isDefault"
                                    value="1"
                                    {{ old('isDefault', $editingPayoutAccount?->is_default) ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="payout-account-default">{{__('Set as default payout account')}}</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row">
                        <button
                            id="payout-account-submit"
                            class="btn btn-primary mr-md-2 mb-0"
                            type="submit"
                            data-create-label="{{ __('Save payout account') }}"
                            data-edit-label="{{ __('Update payout account') }}"
                        >
                            {{ $formActionLabel }}
                        </button>
                        <a
                            id="payout-account-cancel"
                            class="btn btn-outline-secondary mb-0 {{ $editingPayoutAccountId ? '' : 'd-none' }}"
                            href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw']) }}"
                        >
                            {{__('Cancel editing')}}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($editingPayoutAccountId || $payoutAccountFormErrors)
    <script>
        window.payoutAccountModalShouldOpen = true;
    </script>
@endif
