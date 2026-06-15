<?php

namespace App\Http\Requests;

use App\Rules\AllowedHyperlinks;
use App\Rules\PPVMinMax;
use Illuminate\Foundation\Http\FormRequest;

class SaveNewMessageRequest extends FormRequest
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
            'price' => [new PPVMinMax('message')],
        ];

        // Build message rules
        if (getSetting('websockets.driver') === 'pusher') {
            $rules['message'] = ['nullable', 'string', 'max:800', new AllowedHyperlinks()];
        } else {
            $rules['message'] = ['required', 'string', new AllowedHyperlinks()];
        }

        return $rules;
    }
}
