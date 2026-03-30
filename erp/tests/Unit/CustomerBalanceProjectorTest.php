<?php

namespace Tests\Unit;

use App\Models\DomainOutbox;
use App\Services\Projectors\CustomerBalanceProjector;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerBalanceProjectorTest extends TestCase
{
    use RefreshDatabase;

    // Add a property to track journal entry IDs
    protected int $journalEntryCounter = 0;

    protected function createJournalEvents(int $customerId, int $count): void
    {
        $seqService = app(SequenceService::class);
        $seqService->ensureExists('journal_entry', $customerId);
        for ($i = 0; $i < $count; $i++) {
            $payload = [
                'journal_entry_id' => $i + 1,
                'lines' => [
                    ['customer_id' => $customerId, 'debit' => 10, 'credit' => 0],
                ],
            ];
            \App\Models\DomainEvent::create([
                'tenant_id' => 1,
                'aggregate_type' => 'journal_entry',
                'aggregate_id' => $customerId,
                'sequence' => $seqService->next(1, 'journal_entry', $customerId),
                'event_type' => 'journal_entry.posted',
                'payload' => $payload,
            ]);
        }
    }

    protected function getBalance(int $customerId): float
    {
        $balance = DB::table('customer_balance_projections')->where('customer_id', $customerId)->value('balance');

        return (float) ($balance ?? 0);
    }

    #[Test]
    public function it_updates_balance_on_journal_entry_posted()
    {
        // Create a domain outbox event
        $payload = [
            'journal_entry_id' => 1,
            'lines' => [
                ['customer_id' => 1, 'debit' => 100, 'credit' => 0],
                ['customer_id' => 1, 'debit' => 0, 'credit' => 20],
            ],
        ];
        $event = \App\Models\DomainEvent::create([
            'tenant_id' => 1,
            'aggregate_type' => 'journal_entry',
            'aggregate_id' => 1,
            'sequence' => 1,
            'event_type' => 'journal_entry.posted',
            'payload' => $payload,
        ]);

        $projector = app(CustomerBalanceProjector::class);
        $projector->handle($event);

        // Check projection
        $balance = DB::table('customer_balance_projections')->where('customer_id', 1)->first();
        $this->assertEquals(80, $balance->balance);
        $this->assertEquals(1, $balance->version);
    }

    #[Test]
    public function it_rebuilds_balance_from_outbox()
    {
        // Create multiple events with same aggregate_id
        $payload1 = ['lines' => [['customer_id' => 1, 'debit' => 100, 'credit' => 0]]];
        \App\Models\DomainEvent::create([
            'tenant_id' => 1,
            'aggregate_type' => 'journal_entry',
            'aggregate_id' => 1,
            'sequence' => 1,
            'event_type' => 'journal_entry.posted',
            'payload' => $payload1,
        ]);
        $payload2 = ['lines' => [['customer_id' => 1, 'debit' => 0, 'credit' => 30]]];
        \App\Models\DomainEvent::create([
            'tenant_id' => 1,
            'aggregate_type' => 'journal_entry',
            'aggregate_id' => 1,
            'sequence' => 2,
            'event_type' => 'journal_entry.posted',
            'payload' => $payload2,
        ]);

        $projector = app(CustomerBalanceProjector::class);
        $projector->rebuild(1); // aggregate ID = 1

        $balance = DB::table('customer_balance_projections')->where('customer_id', 1)->first();
        $this->assertEquals(70, $balance->balance);
    }

    #[Test]
    public function snapshot_plus_tail_equals_full_replay(): void
    {
        $projector = app(CustomerBalanceProjector::class);
        $customerId = 1;

        // Create 100 events
        $this->createJournalEvents($customerId, 100);

        // Full rebuild
        $projector->rebuild($customerId);
        $fullBalance = $this->getBalance($customerId);

        // Take snapshot
        $projector->takeSnapshot($customerId);

        // Create 50 more events
        $this->createJournalEvents($customerId, 50);

        // Rebuild from snapshot
        $projector->rebuildFromSnapshot($customerId);
        $snapshotBalance = $this->getBalance($customerId);

        $this->assertEquals($fullBalance + 50 * 10, $snapshotBalance);
    }
}
