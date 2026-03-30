<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Aggregates\MissionAggregate;
use App\Http\Requests\StoreMissionRequest;
use App\Http\Requests\LoadMissionRequest;
use App\Http\Requests\VisitStopRequest;
use App\Http\Requests\CompleteMissionRequest;
use Illuminate\Support\Str;

class MissionController extends Controller
{
    public function store(StoreMissionRequest $request)
    {
        $id = (string) Str::uuid();
        $validated = $request->validated();
        
        MissionAggregate::retrieve($id)
            ->create($validated, $validated['points'] ?? [])
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function load(LoadMissionRequest $request, $id)
    {
        MissionAggregate::retrieve($id)
            ->loadPhysicalStock($request->validated('entreprise_id'))
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function visitStop(VisitStopRequest $request, $id)
    {
        $validated = $request->validated();
        
        MissionAggregate::retrieve($id)
            ->visitStop(
                $validated['entreprise_id'],
                $validated['point_id'],
                $validated['route_id'],
                $validated['visited_at'],
                $validated['delivery_data']
            )
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function complete(CompleteMissionRequest $request, $id)
    {
        MissionAggregate::retrieve($id)
            ->complete($request->validated('entreprise_id'))
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }
}
