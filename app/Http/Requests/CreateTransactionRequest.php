<?php

namespace App\Http\Requests;

use App\Model\Transaction;
use App\Model\User;
use App\Providers\ProfileMonetizationServiceProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateTransactionRequest extends FormRequest
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
        return [
            'recipient_user_id' => '',
            'post_id' => '',
            'taxes' => '',
            'amount' => 'required',
            'provider' => ['required', Rule::in(Transaction::NEW_PAYMENT_PROVIDERS)],
            'transaction_type' => 'required',
            'billing_address' => 'min:3|max:255',
            'first_name' => 'min:1|max:255',
            'last_name' => 'min:1|max:255',
            'country' => 'min:1|max:255',
            'state' => 'min:1|max:255',
            'postcode' => 'min:1|max:255',
            'city' => 'min:1|max:255',
            'manual_payment_files' => '',
            'manual_payment_description' => '',
            'coupon' => 'nullable|string|max:255',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $transactionType = $this->get('transaction_type');

            if (!in_array($transactionType, ProfileMonetizationServiceProvider::subscriptionTypes(), true)) {
                return;
            }

            $recipientUser = User::query()->find($this->get('recipient_user_id'));
            if (!ProfileMonetizationServiceProvider::canReceiveProfileSubscriptions($recipientUser)) {
                $validator->errors()->add(
                    'transaction_type',
                    __('Profile subscriptions are disabled for this profile.')
                );
            }
        });
    }
}
