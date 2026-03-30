<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitStopRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'entreprise_id' => 'required|exists:entreprise,id',
            'mission_id' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'timestamp' => 'required|integer',
            'point_id' => 'required|integer',
            'route_id' => 'required|integer',
            'visited_at' => 'required|string',
            'delivery_data' => 'required|array',
            'delivery_data.quantite_livree' => 'nullable|numeric',
            'delivery_data.montant_encaisse' => 'nullable|numeric',
        ];
    }
}
