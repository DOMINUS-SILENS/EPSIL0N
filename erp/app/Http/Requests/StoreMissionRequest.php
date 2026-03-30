<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMissionRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'mission_id' => 'required|integer',
            'entreprise_id' => 'required|integer',
            'points' => 'required|array|min:1',
            'points.*.mission_point_id' => 'required|integer',
            'points.*.contact_id' => 'required|integer',
        ];
    }
}
