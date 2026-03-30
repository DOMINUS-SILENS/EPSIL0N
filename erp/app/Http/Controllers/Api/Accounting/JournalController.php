<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Aggregates\JournalAggregate;
use App\Http\Requests\StoreJournalEntryRequest;
use Illuminate\Support\Str;

class JournalController extends Controller
{
    public function store(StoreJournalEntryRequest $request)
    {
        $id = (string) Str::uuid();
        $validated = $request->validated();
        
        JournalAggregate::retrieve($id)
            ->postEntry($validated, now()->toIso8601String())
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }
}
