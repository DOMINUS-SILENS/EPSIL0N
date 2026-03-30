<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMissionRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'entreprise_id' => 'required|integer',
        ];
    }
}
