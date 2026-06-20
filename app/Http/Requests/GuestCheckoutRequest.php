<?php

namespace App\Http\Requests;

use App\Model\Transaction;
use App\Model\User;
use App\Providers\ProfileMonetizationServiceProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuestCheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'recipient_user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required'],
            'provider' => ['required', Rule::in([Transaction::STRIPE_PROVIDER, Transaction::STRIPE_PIX_PROVIDER])],
            'transaction_type' => ['required', Rule::in(ProfileMonetizationServiceProvider::subscriptionTypes())],
            'billing_address' => ['nullable', 'min:3', 'max:255'],
            'first_name' => ['nullable', 'min:1', 'max:255'],
            'last_name' => ['nullable', 'min:1', 'max:255'],
            'country' => ['nullable', 'min:1', 'max:255'],
            'state' => ['nullable', 'min:1', 'max:255'],
            'postcode' => ['nullable', 'min:1', 'max:255'],
            'city' => ['nullable', 'min:1', 'max:255'],
            'coupon' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
