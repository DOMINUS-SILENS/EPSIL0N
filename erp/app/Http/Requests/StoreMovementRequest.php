<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovementRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'mouvement_id' => 'required|integer',
            'entreprise_id' => 'required|integer',
            'lines' => 'required|array|min:1',
            'lines.*.article_id' => 'required|integer',
            'lines.*.quantite' => 'required|numeric|min:0.01',
            'data' => 'nullable|array'
        ];
    }
}
