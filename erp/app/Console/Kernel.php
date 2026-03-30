<?php

namespace App\Console;

use App\Console\Commands\CalculateStockBalance;
use App\Console\Commands\ExpireReservations;
use App\Console\Commands\MakeServiceCommand;
use App\Console\Commands\ProcessOutbox;
use App\Console\Commands\RebuildProjection;
use App\Http\Middleware\CostTracker;
use App\Http\Middleware\TraceMiddleware;
use App\Services\DynamicEquilibrium;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
            // Register the custom service generator
        MakeServiceCommand::class,
        ExpireReservations::class,
        ProcessOutbox::class,
        RebuildProjection::class,
        CalculateStockBalance::class,
        SeedCertificationDataset::class,
    ];

    protected function commands(): void
    {
        // Automatically load commands in the Commands folder
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reservations:expire')->everyTenMinutes();
        $schedule->command('outbox:process')->everyMinute();
        $schedule->command('determinism:verify')->daily();
        $schedule->command('verify:system')->hourly();
        $schedule->command('verify:continuous')->daily();
        $schedule->command('stock:balance')->daily();

        $schedule->call(function () {
            app(HealthMonitor::class)->evaluate();
        })->everyMinute();

        $schedule->call(function () {
            app(AdversarialMonitor::class)->checkGovernanceAnomalies();
        })->everyMinute();

        $schedule->call(function () {
            app(DynamicEquilibrium::class)->adjust();
        })->everyMinute();

        $schedule->call(function () {
            app(CostTracker::class)->track();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected $middlewareGroups = [
        'api' => [
            TraceMiddleware::class,
            // ...
        ],
    ];
}
