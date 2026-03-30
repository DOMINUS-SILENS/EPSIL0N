<?php

namespace App\Console\Commands;

use App\Models\EventStore;
use Illuminate\Console\Command;

class VerifyEventIntegrity extends Command
{
    protected $signature = 'event:verify';

    protected $description = 'Verify event signatures and hash chain';

    public function handle()
    {
        $shards = EventStore::distinct('shard_id')->pluck('shard_id');

        foreach ($shards as $shardId) {
            $this->info("Verifying shard $shardId...");
            $events = EventStore::where('shard_id', $shardId)->orderBy('local_sequence')->get();

            $prevHash = '0';
            $valid = true;

            foreach ($events as $event) {
                // Check previous hash
                if ($event->previous_hash !== $prevHash) {
                    $this->error("Hash chain broken at event {$event->id}");
                    $valid = false;
                }

                // Verify signature
                $expected = $this->signEvent($event);
                if ($expected !== $event->signature) {
                    $this->error("Invalid signature at event {$event->id}");
                    $valid = false;
                }

                $prevHash = $event->merkle_root;
            }

            if ($valid) {
                $this->info("Shard $shardId is valid.");
            }
        }
    }

    private function signEvent(EventStore $event): string
    {
        $data = [
            'shard_id' => $event->shard_id,
            'local_sequence' => $event->local_sequence,
            'event_type' => $event->event_type,
            'aggregate_type' => $event->aggregate_type,
            'aggregate_id' => $event->aggregate_id,
            'payload' => $event->payload,
            'previous_hash' => $event->previous_hash,
            'merkle_root' => $event->merkle_root,
        ];

        return hash_hmac('sha256', json_encode($data), config('app.event_signing_key'));
    }
}
