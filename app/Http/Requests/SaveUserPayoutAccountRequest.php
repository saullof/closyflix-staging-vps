<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserPayoutAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payout_account_id' => [
                'nullable',
                'integer',
                Rule::exists('user_payout_accounts', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'label' => 'nullable|string|max:191',
            'accountHolderName' => 'required|string|max:191',
            'iban' => 'required|string|max:64',
            'swiftBic' => 'nullable|string|max:64',
            'bankName' => 'required|string|max:191',
            'bankAddress' => 'nullable|string|max:1000',
            'countryId' => 'nullable|exists:countries,id',
            'isDefault' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'accountHolderName.required' => __('Please enter the recipient full name.'),
            'iban.required' => __('Please enter the bank account IBAN.'),
            'bankName.required' => __('Please enter the bank name.'),
        ];
    }
}
