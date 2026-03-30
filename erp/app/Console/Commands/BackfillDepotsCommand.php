<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\DepotAggregate;
use Illuminate\Support\Str;

class BackfillDepotsCommand extends Command
{
    protected $signature = 'backfill:depots {--chunk=500}';
    protected $description = 'Extracts legacy depots records into the DepotAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling depots...");

        DB::table('depots')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                DepotAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
