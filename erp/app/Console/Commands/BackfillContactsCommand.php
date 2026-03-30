<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Aggregates\ContactAggregate;
use Illuminate\Support\Str;

class BackfillContactsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:contacts {--chunk=500 : Number of records per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts legacy MySQL "contact" records and streams them securely through the ContactAggregate Event pipeline.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = $this->option('chunk');
        
        $this->info("Initializing God-Level Legacy Contact Extraction (Chunk Size: {$chunkSize})...");

        // Check if legacy table exists
        if (!Schema::hasTable('contact')) {
            $this->error("Legacy table 'contact' not found. Ensure the original data is present.");
            return Command::FAILURE;
        }

        $count = DB::table('contact')->count();
        $this->info("Found {$count} legacy contacts to backfill.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::table('contact')->orderBy('contact_id')->chunk($chunkSize, function ($contacts) use ($bar) {
            foreach ($contacts as $legacyContact) {
                // Map the legacy DB row to the Aggregate's expectation array
                $data = [
                    'entreprise_id' => $legacyContact->entreprise_id ?: throw new \RuntimeException('Strict Enforce: Missing entreprise_id on legacy contact'),
                    'contact_nom' => $legacyContact->contact_nom,
                    'contact_prenom' => $legacyContact->contact_prenom,
                    'entreprise_id' => $legacyContact->entreprise_id,
                    'contact_raison_sociale' => $legacyContact->contact_raison_sociale
                ];

                // Hydrate the aggregate through the UUID and Command Handler
                // We use UUID v4 to satisfy the God-Level distributed systems constraint natively
                $aggregateUuid = Str::uuid()->toString();

                ContactAggregate::retrieve($aggregateUuid)
                    ->createContact($legacyContact->contact_id, $data)
                    ->persist();

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Legacy Contacts successfully packaged as Domain Events and persisted to the God-Level Event Store!");
        
        // Rebuild the projections immediately locally to complete the PoC chain
        $this->info("Triggering Local Projection Rebuild Sequence...");
        
        // Emulating a projector reboot pipeline:
        $projector = app(\App\Services\Projectors\ContactProjector::class);
        $projector->rebuildFromSnapshot(); // Or standard rebuild method to iterate fresh outbox stream

        $this->info("Contact projection topology rebuilt autonomously!");
        return Command::SUCCESS;
    }
}
