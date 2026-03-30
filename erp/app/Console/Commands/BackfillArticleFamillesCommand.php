<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\ArticleFamilleAggregate;
use Illuminate\Support\Str;

class BackfillArticleFamillesCommand extends Command
{
    protected $signature = 'backfill:articlefamilles {--chunk=500}';
    protected $description = 'Extracts legacy article_famille records into the ArticleFamilleAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling article_famille...");

        DB::table('article_famille')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                ArticleFamilleAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
