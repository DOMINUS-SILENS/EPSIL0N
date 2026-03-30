<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Crm\LeadController;
use App\Http\Controllers\Api\Erp\ProductController;
use App\Http\Controllers\Api\Erp\OrderController;
use App\Http\Controllers\Api\Erp\MovementController;
use App\Http\Controllers\Api\Accounting\JournalController;
use App\Http\Controllers\Api\Finance\PaymentController;
use App\Http\Controllers\Api\Sfa\VisitController;
use App\Http\Controllers\Api\Report\ReportController;

Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login_redirect'); // Fallback for redirect attempts

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Events API (Optimized polling for mobile sync)
    Route::get('/events/poll', [\App\Http\Controllers\Api\EventsController::class, 'poll']);
    Route::get('/events/long-poll', [\App\Http\Controllers\Api\EventsController::class, 'longPoll']);
    Route::get('/events/stream', [\App\Http\Controllers\Api\EventsController::class, 'stream']);
    Route::get('/events/latest-id', [\App\Http\Controllers\Api\EventsController::class, 'latestEventId']);
    Route::post('/events/batch', [\App\Http\Controllers\Api\EventsController::class, 'batch']);

    // Delivery Tours
    Route::apiResource('delivery/tours', \App\Http\Controllers\Api\Delivery\TourController::class);
    Route::post('delivery/tours/{id}/start', [\App\Http\Controllers\Api\Delivery\TourController::class, 'start']);
    Route::post('delivery/tours/{id}/complete', [\App\Http\Controllers\Api\Delivery\TourController::class, 'complete']);
    Route::post('delivery/tours/{id}/stops/reorder', [\App\Http\Controllers\Api\Delivery\TourController::class, 'reorderStops']);
    Route::post('delivery/tours/{id}/stops/{stopId}/checkin', [\App\Http\Controllers\Api\Delivery\TourController::class, 'checkInStop']);
    Route::post('delivery/tours/{id}/stops/{stopId}/checkout', [\App\Http\Controllers\Api\Delivery\TourController::class, 'checkOutStop']);
    Route::post('delivery/tours/{id}/stops/{stopId}/deliver', [\App\Http\Controllers\Api\Delivery\TourController::class, 'deliverStop']);
    Route::post('delivery/tours/{id}/stops/{stopId}/fail', [\App\Http\Controllers\Api\Delivery\TourController::class, 'failStop']);

    // Fleet
    Route::apiResource('fleet/vehicles', \App\Http\Controllers\Api\Fleet\VehicleController::class);
    Route::post('fleet/vehicles/{id}/location', [\App\Http\Controllers\Api\Fleet\VehicleController::class, 'updateLocation']);
    Route::get('fleet/vehicles/{id}/locations', [\App\Http\Controllers\Api\Fleet\VehicleController::class, 'locationHistory']);
    Route::post('fleet/vehicles/{id}/maintenance', [\App\Http\Controllers\Api\Fleet\VehicleController::class, 'addMaintenance']);

    // Pre-Sales
    Route::apiResource('presales/campaigns', \App\Http\Controllers\Api\PreSales\CampaignController::class);
    Route::post('presales/campaigns/{id}/start', [\App\Http\Controllers\Api\PreSales\CampaignController::class, 'start']);
    Route::post('presales/campaigns/{id}/complete', [\App\Http\Controllers\Api\PreSales\CampaignController::class, 'complete']);
    Route::apiResource('presales/demos', \App\Http\Controllers\Api\PreSales\DemoController::class);
    Route::post('presales/demos/{id}/complete', [\App\Http\Controllers\Api\PreSales\DemoController::class, 'complete']);
    Route::apiResource('presales/samples', \App\Http\Controllers\Api\PreSales\SampleOrderController::class);
    Route::post('presales/samples/{id}/approve', [\App\Http\Controllers\Api\PreSales\SampleOrderController::class, 'approve']);
    Route::post('presales/samples/{id}/ship', [\App\Http\Controllers\Api\PreSales\SampleOrderController::class, 'ship']);

    // Trade Marketing
    Route::apiResource('trademkt/planograms', \App\Http\Controllers\Api\TradeMkt\PlanogramController::class);
    Route::post('trademkt/planograms/{id}/activate', [\App\Http\Controllers\Api\TradeMkt\PlanogramController::class, 'activate']);
    Route::apiResource('trademkt/executions', \App\Http\Controllers\Api\TradeMkt\ExecutionController::class);
    Route::post('trademkt/executions/{id}/start', [\App\Http\Controllers\Api\TradeMkt\ExecutionController::class, 'start']);
    Route::post('trademkt/executions/{id}/complete', [\App\Http\Controllers\Api\TradeMkt\ExecutionController::class, 'complete']);
    Route::get('trademkt/compliance', [\App\Http\Controllers\Api\TradeMkt\ComplianceController::class, 'index']);

    // CRM
    Route::post('crm/leads/command', [LeadController::class, 'handleCommand']);
    Route::post('crm/leads/{id}/command', [LeadController::class, 'handleCommand']);
    Route::apiResource('crm/leads', LeadController::class);
    Route::post('crm/leads/{id}/convert', [LeadController::class, 'convert']);

    // ERP
    Route::apiResource('erp/products', ProductController::class);
    Route::get('erp/stock/history/{id}', [ProductController::class, 'stockHistory']);

    Route::apiResource('erp/orders', OrderController::class);
    Route::post('erp/orders/{id}/confirm', [OrderController::class, 'confirm']);
    Route::patch('erp/orders/{id}/cancel', [OrderController::class, 'cancel']);

    Route::apiResource('erp/movements', MovementController::class);
    Route::post('erp/movements/{id}/deliver', [MovementController::class, 'deliver']);

    // Accounting & Finance
    Route::apiResource('accounting/journal', JournalController::class);
    Route::post('accounting/journal/{id}/post', [JournalController::class, 'post']);

    Route::apiResource('finance/payments', PaymentController::class);

    // Logistics
    Route::post('logistics/missions', [\App\Http\Controllers\Api\Logistics\MissionController::class, 'store']);
    Route::post('logistics/missions/{id}/load', [\App\Http\Controllers\Api\Logistics\MissionController::class, 'load']);
    Route::post('logistics/missions/{id}/stops/visit', [\App\Http\Controllers\Api\Logistics\MissionController::class, 'visitStop']);
    Route::post('logistics/missions/{id}/complete', [\App\Http\Controllers\Api\Logistics\MissionController::class, 'complete']);

    // Reports
    Route::get('reports/turnover', [ReportController::class, 'turnover']);
    Route::get('reports/top-products', [ReportController::class, 'topProducts']);
    Route::get('reports/top-clients', [ReportController::class, 'topClients']);
    Route::get('reports/sfa-performance', [ReportController::class, 'sfaPerformance']);
    Route::get('reports/accounting-summary', [ReportController::class, 'accountingSummary']);
});

// Monitoring (Prometheus)
Route::get('/metrics', [\App\Http\Controllers\Api\PrometheusController::class, 'metrics']);

// Offline-First Mobile Sync
Route::prefix('sync')->middleware('auth')->group(function () {
    // Main sync endpoints
    Route::post('/ingest', [\App\Http\Controllers\Api\SyncController::class, 'ingest']);
    Route::get('/delta', [\App\Http\Controllers\Api\SyncController::class, 'delta']);
    Route::post('/resolve-conflicts', [\App\Http\Controllers\Api\SyncController::class, 'resolveConflicts']);
    Route::get('/status', [\App\Http\Controllers\Api\SyncController::class, 'status']);
    Route::get('/resume', [\App\Http\Controllers\Api\SyncController::class, 'resume']);

    // Sync planning endpoints
    Route::get('/plan', [\App\Http\Controllers\Api\SyncController::class, 'plan']);
    Route::get('/summary/{entity}', [\App\Http\Controllers\Api\SyncController::class, 'entitySummary']);

    // Legacy endpoint (backward compatibility)
    Route::post('/events', [\App\Http\Controllers\Api\SyncController::class, 'ingestLegacy']);
});
