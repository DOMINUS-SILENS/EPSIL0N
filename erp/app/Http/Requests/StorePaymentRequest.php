<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_id' => 'required|integer',
            'entreprise_id' => 'required|integer',
            'contact_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'mode' => 'required|string|max:50',
            'meta' => 'nullable|array'
        ];
    }
}
