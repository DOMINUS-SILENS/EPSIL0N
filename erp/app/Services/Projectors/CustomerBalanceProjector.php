<?php

namespace App\Services\Projectors;

use App\Models\DomainOutbox;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;

class CustomerBalanceProjector extends Projector
{
    protected string $table = 'customer_balance_projections';

    protected string $idField = 'customer_id';

    protected function getVersionFromDatabase(): int
    {
        return (int) DB::table('projection_versions')
            ->where('projector_name', 'CustomerBalanceProjector')
            ->value('version') ?? 1;
    }

    protected function setVersion(int $version): void
    {
        DB::table('projection_versions')->updateOrInsert(
            ['projector_name' => 'CustomerBalanceProjector'],
            ['version' => $version, 'updated_at' => now()]
        );
    }


    public function handleJournalEntryPosted(array $payload): void
    {
        $customerBalances = [];
        foreach ($payload['lines'] as $line) {
            if (isset($line['customer_id'])) {
                $customerId = $line['customer_id'];
                $debit = $line['debit'] ?? 0;
                $credit = $line['credit'] ?? 0;
                $change = $debit - $credit;
                $customerBalances[$customerId] = ($customerBalances[$customerId] ?? 0) + $change;
            }
        }

        foreach ($customerBalances as $customerId => $change) {
            $record = DB::table('customer_balance_projections')
                ->where('customer_id', $customerId)
                ->first();

            if ($record) {
                // Safely extract the balance to satisfy static analysis
                $currentBalance = is_array($record) ? $record['balance'] : ((object) $record)->balance;
                
                DB::table('customer_balance_projections')
                    ->where('customer_id', $customerId)
                    ->update(['balance' => $currentBalance + $change]);
            } else {
                DB::table('customer_balance_projections')->insert([
                    'customer_id' => $customerId,
                    'balance' => $change,
                    'entreprise_id' => 1, // placeholder – should come from event context
                    'last_sequence' => 0,
                    'version' => 1,
                ]);
            }
        }
    }

    protected function getState(int $aggregateId): array
    {
        $record = DB::table('customer_balance_projections')->where('customer_id', $aggregateId)->first();

        return $record ? (array) $record : ['balance' => 0, 'version' => 1];
    }

    protected function restoreState(int $aggregateId, array $state): void
    {
        DB::table('customer_balance_projections')->updateOrInsert(
            ['customer_id' => $aggregateId],
            $state
        );
    }

    protected function setLastProcessedEventId(int $_aggregateId, int $_lastEventId): void
    {
        // Optional: store last processed event ID somewhere
    }

    public function rebuild(int $aggregateId): void
    {
        DB::table('customer_balance_projections')->where('customer_id', $aggregateId)->delete();
        
        $events = \App\Models\DomainEvent::where('aggregate_type', 'journal_entry')
            ->where('aggregate_id', $aggregateId)
            ->orderBy('sequence')
            ->get();
            
        foreach ($events as $event) {
            $this->handle($event);
        }
    }

    public function takeSnapshot(int $aggregateId): void
    {
        $record = DB::table('customer_balance_projections')->where('customer_id', $aggregateId)->first();
        if ($record) {
            \App\Models\ProjectionSnapshot::updateOrCreate(
                [
                    'projector_name' => self::class,
                    'aggregate_id' => (string) $aggregateId,
                ],
                [
                    'snapshot' => ['balance' => ((object) $record)->balance],
                    'last_event_id' => 0,
                ]
            );
        }
    }

    public function rebuildFromSnapshot(int $aggregateId): void
    {
        $snapshot = \App\Models\ProjectionSnapshot::where('projector_name', self::class)
            ->where('aggregate_id', (string) $aggregateId)
            ->first();

        if ($snapshot) {
            $state = is_array($snapshot->snapshot) ? $snapshot->snapshot : json_decode($snapshot->snapshot, true);
            DB::table('customer_balance_projections')->updateOrInsert(
                ['customer_id' => $aggregateId],
                ['balance' => $state['balance']]
            );
            
            $events = \App\Models\DomainEvent::where('aggregate_type', 'journal_entry')
                ->where('aggregate_id', (string) $aggregateId)
                ->where('recorded_at', '>', $snapshot->updated_at ?? now()->subCenturies(1))
                ->orderBy('sequence')
                ->get();
                
            foreach ($events as $event) {
                $this->handle($event);
            }
        } else {
            $this->rebuild($aggregateId);
        }
    }
}
