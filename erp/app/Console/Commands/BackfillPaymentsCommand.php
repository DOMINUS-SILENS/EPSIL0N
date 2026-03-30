<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\PaymentAggregate;
use Illuminate\Support\Str;

class BackfillPaymentsCommand extends Command
{
    protected $signature = 'backfill:payments {--chunk=500}';
    protected $description = 'Extracts legacy reglements records into the PaymentAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling reglements...");

        DB::table('reglements')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                PaymentAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
