<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class OutboxRetryCommand extends Command
{
    protected $signature = 'outbox:retry {--event_id= : Specific event ID to retry} {--all : Retry all failed events}';
    protected $description = 'Move events from dead_letters back to domain_outbox for reprocessing';

    public function handle(): void
    {
        $eventId = $this->option('event_id');
        $all = $this->option('all');

        if (!$eventId && !$all) {
            $this->error('Please specify --event_id or --all');
            return;
        }

        $query = DB::table('dead_letters');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->warn('No dead letters found.');
            return;
        }

        foreach ($records as $record) {
            DB::transaction(function () use ($record) {
                // 1. Re-insert into outbox
                DomainOutbox::updateOrInsert(
                    ['event_id' => $record->event_id],
                    [
                        'status' => 'pending',
                        'attempts' => 0,
                        'next_retry_at' => null,
                        'last_error' => null,
                    ]
                );

                // 2. Remove from dead letters
                DB::table('dead_letters')->where('id', $record->id)->delete();
                
                $this->info("Event {$record->event_id} moved back to active outbox.");
            });
        }

        $this->info('Retry process completed.');
    }
}
