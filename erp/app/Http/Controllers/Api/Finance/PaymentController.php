<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Aggregates\PaymentAggregate;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => []]);
    }

    public function store(StorePaymentRequest $request)
    {
        $id = (string) Str::uuid();
        $validated = $request->validated();
        
        PaymentAggregate::retrieve($id)
            ->recordPayment(
                $validated['payment_id'],
                $validated['entreprise_id'],
                $validated['contact_id'],
                $validated['amount'],
                $validated['mode'],
                $validated['meta'] ?? []
            )
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }
}
