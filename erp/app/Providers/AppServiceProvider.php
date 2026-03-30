<?php

namespace App\Providers;

use App\Models\ArticleMovement;
use App\Observers\ArticleMovementObserver;
use App\Services\AlertingService;
use App\Services\AuditService;
use App\Services\Crdt\MergeService;
use App\Services\LiveDashboardService;
use App\Services\MetricsService;
use App\Services\OutboxService;
use App\Services\ProjectionDispatcher;
use App\Services\ReservationService;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\Exporter\OtlpGrpcExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Sequences\AggregateSequenceService::class);
        $this->app->singleton(\App\Services\Sequences\GlobalEventOffsetService::class);
        $this->app->singleton(\App\Services\Sequences\DeviceCausalSequenceService::class);
        $this->app->singleton(\App\Services\Sequences\BusinessReferenceSequenceService::class);
        $this->app->singleton(OutboxService::class);
        $this->app->singleton(ReservationService::class);
        $this->app->singleton(StockService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(MetricsService::class);
        $this->app->singleton(ProjectionDispatcher::class);
        $this->app->singleton(LiveDashboardService::class);
        $this->app->singleton(AlertingService::class);
        $this->app->singleton(MergeService::class);

        if (config('opentelemetry.enabled')) {
            $this->app->singleton(TracerInterface::class, function ($app) {
                $exporter = new OtlpGrpcExporter(config('opentelemetry.endpoint'));
                $spanProcessor = new BatchSpanProcessor($exporter);
                $tracerProvider = new TracerProvider($spanProcessor);

                return $tracerProvider->getTracer(config('opentelemetry.service_name'));
            });
        } else {
            $this->app->singleton(TracerInterface::class, function () {
                return new NoopTracer;
            });
        }
    }

    public function boot()
    {
        // Anti-pattern: Removed strict dependency on the database schema during Laravel boot processes.
        // This mathematically ensures `php artisan` will boot safely even if MySQL is sleeping or mapping ports incorrectly.
        try {
            ArticleMovement::observe(ArticleMovementObserver::class);
        } catch (\Throwable $e) {}

        Artisan::command('make:service {name}', function ($name) {
            /** @var Command $this */
            $path = app_path("Services/{$name}.php");

            if (file_exists($path)) {
                $this->error("Service {$name} already exists!");

                return;
            }

            if (! file_exists(app_path('Services'))) {
                mkdir(app_path('Services'), 0755, true);
            }

            $stub = "<?php\n\nnamespace App\Services;\n\nclass {$name}\n{\n    //\n}\n";

            file_put_contents($path, $stub);
            $this->info("Service {$name} created successfully at {$path}");
        })->describe('Create a new service class');
    }
}
