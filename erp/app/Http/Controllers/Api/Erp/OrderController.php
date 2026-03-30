<?php

namespace App\Http\Controllers\Api\Erp;

use App\Http\Controllers\Controller;
use App\Aggregates\OrderAggregate;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $orders->map(fn($o) => [
                'id' => $o->id,
                'reference' => $o->reference,
                'customer_id' => $o->customer_id,
                'customer_name' => $o->customer_name,
                'status' => $o->status,
                'subtotal_amount' => $o->subtotal_amount,
                'total_amount' => $o->total_amount,
                'created_at' => $o->created_at?->toIso8601String(),
                'created_by' => $o->created_by,
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    public function show($id)
    {
        $order = Order::with('lines')->findOrFail($id);

        return response()->json([
            'id' => $order->id,
            'reference' => $order->reference,
            'status' => $order->status,
            'customer' => [
                'id' => $order->customer_id,
                'name' => $order->customer_name,
            ],
            'lines' => $order->lines->map(fn($l) => [
                'product_id' => $l->product_id,
                'product_name' => $l->product_name,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'total' => $l->total,
            ]),
            'totals' => [
                'ht' => $order->subtotal_amount,
                'ttc' => $order->total_amount,
            ],
            'created_at' => $order->created_at?->toIso8601String(),
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        $id = (string) Str::uuid();
        $validated = $request->validated();
        $reference = 'CMD-' . date('Y') . '-' . strtoupper(Str::random(4));

        // 1. Write canonical command/event flow (Aggregate)
        OrderAggregate::retrieve($id)
            ->createOrder(array_merge($validated, ['reference' => $reference]))
            ->persist();

        // 2. Persist read model synchronously for launch stability (Bridge)
        DB::transaction(function () use ($id, $reference, $validated, $request) {
            $order = Order::create([
                'id' => $id,
                'reference' => $reference,
                'customer_id' => $validated['customer_id'] ?? 1,
                'customer_name' => $validated['customer_name'] ?? 'Inconnu', // Optional enrichment
                'status' => 'submitted',
                'subtotal_amount' => $validated['subtotal_amount'] ?? 0,
                'total_amount' => $validated['total_amount'] ?? 0,
                'created_by' => $request->user()?->id,
            ]);

            if (!empty($validated['lines'])) {
                foreach ($validated['lines'] as $line) {
                    OrderLine::create([
                        'order_id' => $id,
                        'product_id' => $line['product_id'],
                        'product_name' => $line['product_name'] ?? 'Article',
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'total' => ($line['quantity'] * $line['unit_price']),
                    ]);
                }
            }
        });

        // 3. Return stable response DTO
        return response()->json([
            'success' => true,
            'order_id' => $id,
            'reference' => $reference,
        ], 201);
    }

    public function update(StoreOrderRequest $request, $id)
    {
        OrderAggregate::retrieve($id)
            ->updateOrder($request->validated())
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function confirm(Request $request, $id)
    {
        OrderAggregate::retrieve($id)
            ->confirmOrder()
            ->persist();
        
        // Update Read Model
        Order::where('id', $id)->update(['status' => 'validated']);

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function cancel(Request $request, $id)
    {
        OrderAggregate::retrieve($id)
            ->cancelOrder()
            ->persist();
            
        // Update Read Model
        Order::where('id', $id)->update(['status' => 'cancelled']);

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }
}
