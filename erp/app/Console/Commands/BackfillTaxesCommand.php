<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\TaxeAggregate;
use Illuminate\Support\Str;

class BackfillTaxesCommand extends Command
{
    protected $signature = 'backfill:taxes {--chunk=500}';
    protected $description = 'Extracts legacy taxes records into the TaxeAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling taxes...");

        DB::table('taxes')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                TaxeAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
