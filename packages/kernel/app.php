<?php
/**
 * RoadRunner worker entry point for EPSILON Kernel
 */

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\HttpWorker;
use Nyholm\Psr7\Factory\Psr17Factory;

require __DIR__ . '/vendor/autoload.php';

// Debug: log startup
error_log("[WORKER] Starting up...");

// Create worker
$psr17Factory = new Psr17Factory();
$worker = Worker::create();
error_log("[WORKER] Worker created");
$httpWorker = new HttpWorker($worker, $psr17Factory, $psr17Factory);
error_log("[WORKER] HttpWorker created, entering loop");

// Simple router
while ($request = $httpWorker->waitRequest()) {
    try {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Route: /api/windsurf-plan
        if ($path === '/api/windsurf-plan' && $method === 'POST') {
            $body = json_decode((string) $request->getBody(), true) ?: [];

            $level = $body['level'] ?? 1;
            $wind = $body['wind'] ?? 10;
            $location = $body['location'] ?? 'open water';

            $response = [
                'status' => 'success',
                'plan' => [
                    'level' => $level,
                    'wind_knots' => $wind,
                    'location' => $location,
                    'recommendations' => [
                        'board_type' => $wind > 20 ? 'small_board' : 'allround',
                        'sail_size' => $wind > 15 ? '4.5-5.5m²' : '6.0-7.5m²',
                        'technique_focus' => $level >= 3 ? 'carving_jibe' : 'beach_start',
                    ],
                    'timestamp' => date('c'),
                ]
            ];

            $httpWorker->respond(
                $psr17Factory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($psr17Factory->createStream(json_encode($response)))
            );
            continue;
        }

        // Health check
        if ($path === '/health') {
            $httpWorker->respond(
                $psr17Factory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($psr17Factory->createStream(json_encode(['status' => 'ok'])))
            );
            continue;
        }

        // 404 Not Found
        $httpWorker->respond(
            $psr17Factory->createResponse(404)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($psr17Factory->createStream(json_encode(['error' => 'Not Found'])))
        );

    } catch (Throwable $e) {
        $httpWorker->respond(
            $psr17Factory->createResponse(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($psr17Factory->createStream(json_encode([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ])))
        );
    }
}
