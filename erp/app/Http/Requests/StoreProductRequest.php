<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reference' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0'
        ];
    }
}
