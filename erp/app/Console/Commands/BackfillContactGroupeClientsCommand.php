<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\ContactGroupeClientAggregate;
use Illuminate\Support\Str;

class BackfillContactGroupeClientsCommand extends Command
{
    protected $signature = 'backfill:contactgroupeclients {--chunk=500}';
    protected $description = 'Extracts legacy contact_groupe_client records into the ContactGroupeClientAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling contact_groupe_client...");

        DB::table('contact_groupe_client')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                ContactGroupeClientAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
