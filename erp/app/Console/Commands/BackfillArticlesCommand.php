<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\Articleggregate;
use Illuminate\Support\Str;

class BackfillArticlesCommand extends Command
{
    protected $signature = 'backfill:articles {--chunk=500}';
    protected $description = 'Extracts and packages legacy articles alongside their nested units/taxes into isolated aggregate matrices.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Initializing Article Subsystem Backfill with Chunk Size: {$chunkSize}...");

        $count = DB::table('articles')->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::table('articles')->orderBy('article_id')->chunk($chunkSize, function ($articles) use ($bar) {
            foreach ($articles as $oldArticle) {
                // Eager Load Units
                $units = DB::table('article_unite')
                    ->where('article_id', $oldArticle->article_id)
                    ->get()
                    ->toArray();

                // Abstract object mappings to pure arrays
                $unitsArray = array_map(function ($val) {
                            return (array)$val;
                        }
                            , $units);

                        // Eager Load Taxes
                        $taxes = DB::table('article_taxe')
                            ->where('article_id', $oldArticle->article_id)
                            ->pluck('taxe_id')
                            ->toArray();

                        // Ensure pure baseline array for parent
                        $articleBaseData = (array)$oldArticle;
                        $articleBaseData['entreprise_id'] = $articleBaseData['entreprise_id'];
                        if (empty($articleBaseData['entreprise_id']))
                            throw new \RuntimeException('Strict Enforce: Missing entreprise_id on legacy article');

                        // Hydrate Aggregate
                        $aggregate = Articleggregate::retrieve((string)Str::uuid());

                        $aggregate->create(
                            $articleBaseData,
                            $unitsArray,
                            $taxes
                        )->persist();

                        $bar->advance();
                    }
                });

        $bar->finish();
        $this->newLine(2);

        $this->info("Backfill terminated successfully. Executing projector synchronizations...");

        // Rebuild the projections immediately locally to complete the PoC chain
        $projector = app(\App\Services\Projectors\ArticleProjector::class);
        $projector->rebuildFromSnapshot();

        $this->info("Article matrices rebuilt and actively event-sourced.");
        return Command::SUCCESS;
    }
}
