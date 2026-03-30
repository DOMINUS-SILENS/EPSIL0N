<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\MovementAggregate;
use Illuminate\Support\Str;

class BackfillMovementsCommand extends Command
{
    protected $signature = 'backfill:movements {--chunk=500}';
    protected $description = 'Extracts legacy mouvements and mouvement_lignes into nested graph Events within the Event Store.';

    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Initializing God-Level legacy Movement Extraction (Lines Hierarchy included)...");

        $count = DB::table('mouvements')->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::table('mouvements')->orderBy('mouvement_id')->chunk($chunkSize, function ($records) use ($bar) {
            foreach ($records as $record) {
                $uuid = Str::uuid()->toString();
                
                // Construct standard generic data representation
                $data = (array) $record;
                $data['entreprise_id'] = $data['entreprise_id'];
                if (empty($data['entreprise_id'])) throw new \RuntimeException('Strict Enforce: Missing entreprise_id on legacy movement');

                // Eager Load Lines recursively into payload
                $lines = DB::table('mouvement_lignes')
                    ->where('mouvement_id', $record->mouvement_id)
                    ->get()
                    ->toArray();
                
                // Map cleanly
                $mappedLines = array_map(fn($line) => (array) $line, $lines);

                // Initialize FSM creation state natively buffering
                $aggregate = MovementAggregate::retrieve($uuid)
                    ->create($data, $mappedLines);

                // Perform legacy status translation backfill simulated transitions based on legacy data context
                // If legacy table 'status' field mapped to "validated" we simulate the transition
                $legacyStatus = strtolower($record->status ?? 'none');

                if (in_array($legacyStatus, ['validated', 'livre', 'facture', 'delivered'])) {
                    $aggregate->validate($data['entreprise_id']);
                }

                if (in_array($legacyStatus, ['livre', 'facture', 'delivered'])) {
                    $aggregate->deliver($data['entreprise_id']);
                }

                if (in_array($legacyStatus, ['annule', 'cancelled'])) {
                    // Depends on previous state, assuming validating before cancelling to simulate standard rollback 
                    $aggregate->validate($data['entreprise_id'])->cancel($data['entreprise_id']);
                }
                
                $aggregate->persist();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Execution complete. Triggering projector replay...");

        $projector = app(\App\Services\Projectors\MovementProjector::class);
        $projector->rebuildFromSnapshot(); // or replay strategy
        
        // Also we trigger StockBalanceProjector update to materialize reserved quantities natively
        $stockProjector = app(\App\Services\Projectors\StockBalanceProjector::class);
        $stockProjector->rebuildFromSnapshot();

        $this->info("Stock Balances and Movement projections successfully regenerated.");
        return Command::SUCCESS;
    }
}
