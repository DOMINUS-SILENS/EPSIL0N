<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function turnover(Request $request)
    {
        // Reads from Stats projector tables
        return response()->json([]);
    }

    public function topProducts(Request $request)
    {
        return response()->json([]);
    }

    public function topClients(Request $request)
    {
        return response()->json([]);
    }

    public function sfaPerformance(Request $request)
    {
        return response()->json([]);
    }

    public function accountingSummary(Request $request)
    {
        return response()->json([]);
    }
}
