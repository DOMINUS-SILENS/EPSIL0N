<?php

namespace App\Console\Commands;

use App\Services\IdempotencyService;
use Illuminate\Console\Command;

/**
 * Cleanup old idempotency keys to prevent table bloat
 */
class CleanupIdempotencyKeys extends Command
{
    protected $signature = 'sync:cleanup-idempotency
                            {--days=7 : Delete keys older than this many days}
                            {--dry-run : Preview what would be deleted}';

    protected $description = 'Clean up old idempotency keys from sync operations';

    public function handle(IdempotencyService $idempotency): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up idempotency keys older than {$days} days...");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletions will occur');
            // Get stats for preview
            $stats = $idempotency->getStats();
            $this->table([
                ['Metric', 'Value'],
                ['Total Keys', $stats['total_keys']],
                ['Last 24h', $stats['last_24h']],
                ['Older than 7d', $stats['older_than_7d']],
                ['Est. Size (MB)', $stats['estimated_size_mb']],
            ]);

            return Command::SUCCESS;
        }

        // Perform cleanup
        $deleted = $idempotency->cleanup($days);

        $this->info("Deleted {$deleted} old idempotency keys.");

        // Show updated stats
        $stats = $idempotency->getStats();
        $this->info("Current key count: {$stats['total_keys']}");

        return Command::SUCCESS;
    }
}
