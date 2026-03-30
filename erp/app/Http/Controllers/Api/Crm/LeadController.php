<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Aggregates\LeadAggregate;
use App\Http\Requests\StoreLeadRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = Contact::query()->paginate($request->get('per_page', 15));
        return response()->json($leads);
    }

    public function show($id)
    {
        return response()->json(Contact::findOrFail($id));
    }

    public function store(StoreLeadRequest $request)
    {
        $id = (string) Str::uuid();
        LeadAggregate::retrieve($id)
            ->createLead($request->validated())
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function update(StoreLeadRequest $request, $id)
    {
        LeadAggregate::retrieve($id)
            ->updateLead($request->validated())
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function handleCommand(Request $request, $id = null)
    {
        $type = $request->input('type');
        $payload = $request->input('payload', []);

        return match ($type) {
            'CreateLead' => $this->store(new StoreLeadRequest($payload)),
            'UpdateLead' => $this->update(new StoreLeadRequest($payload), $id),
            'ConvertLead' => $this->convert($request, $id),
            default => response()->json(['error' => "Unknown command type: {$type}"], 400),
        };
    }

    public function convert(Request $request, $id)
    {
        LeadAggregate::retrieve($id)
            ->convertToCustomer()
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }
}
