<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\StockAggregate;
use Illuminate\Support\Str;

class BackfillStocksCommand extends Command
{
    protected $signature = 'backfill:stocks {--chunk=500}';
    protected $description = 'Extracts legacy stock_movements records into the StockAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling stock_movements...");

        DB::table('stock_movements')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                StockAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
