<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\PromotionAggregate;
use Illuminate\Support\Str;

class BackfillPromotionsCommand extends Command
{
    protected $signature = 'backfill:promotions {--chunk=500}';
    protected $description = 'Extracts legacy promotions records into the PromotionAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling promotions...");

        DB::table('promotions')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                PromotionAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
