<?php

namespace App\Http\Controllers\Api\Erp;

use App\Http\Controllers\Controller;
use App\Models\ArticleMovement;
use App\Aggregates\MovementAggregate;
use App\Http\Requests\StoreMovementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MovementController extends Controller
{
    public function index(Request $request)
    {
        $movements = ArticleMovement::with('article') // Assuming there's an article relation
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $movements->map(fn($m) => [
                'id' => $m->id,
                'product' => $m->article?->designation ?? 'Inconnu',
                'depot' => $m->depot_id,
                'movement_type' => $m->mouvement_type_id,
                'quantity' => $m->article_mouvement_quantite,
                'source_reference' => $m->article_mouvement_ref_piece,
                'created_at' => $m->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'total' => $movements->total(),
            ]
        ]);
    }

    public function store(StoreMovementRequest $request)
    {
        $id = (string) Str::uuid();
        $validated = $request->validated();
        
        MovementAggregate::retrieve($id)
            ->create($validated['data'] ?? [], $validated['lines'] ?? [])
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function deliver(Request $request, $id)
    {
        MovementAggregate::retrieve($id)
            ->deliver($request->input('entreprise_id', 1))
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }
}
