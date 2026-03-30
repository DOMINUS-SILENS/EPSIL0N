<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize() { return true; }
    
    public function rules() {
        return [
            'entreprise_id' => 'required|integer',
            'reference' => 'nullable|string|max:100',
            'description' => 'required|string',
            'entry_date' => 'required|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ];
    }
}
