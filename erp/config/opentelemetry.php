<?php

return [
    'service_name' => env('OTEL_SERVICE_NAME', 'god-sfa-crm'),
    'endpoint' => env('OTEL_ENDPOINT', 'http://localhost:4317'),
    'enabled' => env('OTEL_ENABLED', true),
];
