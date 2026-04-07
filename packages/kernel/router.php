<?php
/**
 * Simple PHP development server router for testing
 */

require __DIR__ . '/vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;

$psr17Factory = new Psr17Factory();

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Route: /api/windsurf-plan
if ($path === '/api/windsurf-plan' && $method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

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

    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($response);
    exit;
}

// Health check
if ($path === '/health') {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// 404 Not Found
header('Content-Type: application/json');
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
