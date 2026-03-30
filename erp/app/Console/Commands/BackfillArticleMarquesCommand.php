<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\ArticleMarqueAggregate;
use Illuminate\Support\Str;

class BackfillArticleMarquesCommand extends Command
{
    protected $signature = 'backfill:articlemarques {--chunk=500}';
    protected $description = 'Extracts legacy article_marque records into the ArticleMarqueAggregate Event pipeline.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Backfilling article_marque...");

        DB::table('article_marque')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $record) {
                $aggregateUuid = Str::uuid()->toString();
                ArticleMarqueAggregate::retrieve($aggregateUuid)
                    ->create((array)$record)
                    ->persist();
            }
        });

        $this->info("Finished. Trigger projector rebuilds.");
        return Command::SUCCESS;
    }
}
