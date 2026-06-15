<?php

namespace App\Http\Requests;

use App\Providers\PaymentsServiceProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'amount' => 'required|numeric',
            'method' => [
                'required',
                'string',
                Rule::in(array_keys(PaymentsServiceProvider::getWithdrawalMethodOptions())),
            ],
            'identifier' => 'nullable|string|max:191',
            'message' => 'nullable|string|max:5000',
            'pix_key_type' => 'nullable|string|in:cpf,cnpj,email,phone,random',
            'pix_beneficiary_name' => 'nullable|string|max:191',
            'payout_account_id' => [
                'nullable',
                'integer',
                Rule::exists('user_payout_accounts', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->where('is_active', true)),
            ],
        ];

        if (PaymentsServiceProvider::isBankTransferMethod($this->input('method'))) {
            $rules['payout_account_id'][0] = 'required';
        }

        if (PaymentsServiceProvider::getWithdrawalMethodKey($this->input('method')) === PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX) {
            $rules['identifier'] = 'required|string|max:191';
            $rules['pix_key_type'] = 'required|string|in:cpf,cnpj,email,phone,random';
            $rules['pix_beneficiary_name'] = 'required|string|max:191';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'payout_account_id.required' => __('Please select a saved payout account for bank transfer withdrawals.'),
            'payout_account_id.exists' => __('The selected payout account is no longer available.'),
        ];
    }
}
