<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\RouteAggregate;
use Illuminate\Support\Str;

class BackfillRoutesCommand extends Command
{
    protected $signature = 'backfill:routes {--chunk=500}';
    protected $description = 'Extracts legacy routes records into the RouteAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling routes...");

        DB::table('routes')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                RouteAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
