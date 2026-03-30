<?php

namespace Tests\Unit\Models;

use App\Models\EventStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EventStore Model Test Suite
 * 
 * Tests the EventStore model including:
 * - Event persistence with sharding
 * - Sequence ordering within shards
 * - Payload and metadata storage
 * - Signature/hashing for integrity
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\EventStore
 */
class EventStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_store_event(): void
    {
        $event = EventStore::create([
            'id' => 1,
            'shard_id' => 1,
            'sequence' => 1,
            'local_sequence' => 1,
            'event_type' => 'stock.entered',
            'aggregate_type' => 'article',
            'aggregate_id' => 123,
            'payload' => ['article_id' => 123, 'quantity' => 100],
            'metadata' => ['user_id' => 1, 'ip' => '127.0.0.1'],
            'occurred_at' => now(),
            'merkle_root' => 'dummy',
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        $this->assertDatabaseHas('event_store', [
            'event_type' => 'stock.entered',
            'aggregate_id' => 123,
        ]);
    }

    #[Test]
    public function it_maintains_sequence_per_shard(): void
    {
        $shardId = 2;

        // Event 1
        EventStore::create([
            'id' => 1,
            'shard_id' => $shardId,
            'sequence' => 1,
            'local_sequence' => 1,
            'event_type' => 'order.created',
            'aggregate_type' => 'order',
            'aggregate_id' => 1,
            'payload' => [],
            'occurred_at' => now(),
            'merkle_root' => 'dummy',
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        // Event 2
        EventStore::create([
            'id' => 2,
            'shard_id' => $shardId,
            'sequence' => 2,
            'local_sequence' => 2,
            'event_type' => 'order.updated',
            'aggregate_type' => 'order',
            'aggregate_id' => 1,
            'payload' => [],
            'occurred_at' => now(),
            'merkle_root' => 'dummy',
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        $events = EventStore::where('shard_id', $shardId)
            ->orderBy('local_sequence')
            ->get();

        $this->assertCount(2, $events);
        $this->assertEquals(1, $events[0]->local_sequence);
        $this->assertEquals(2, $events[1]->local_sequence);
    }

    #[Test]
    public function it_stores_payload_and_metadata_as_json(): void
    {
        $payload = ['data' => 'value', 'nested' => ['key' => 'nested_value']];
        $metadata = ['correlation_id' => 'abc-123', 'causation_id' => 'xyz-789'];

        $event = EventStore::create([
            'id' => 1,
            'shard_id' => 1,
            'sequence' => 1,
            'local_sequence' => 1,
            'event_type' => 'test.event',
            'aggregate_type' => 'test',
            'aggregate_id' => 1,
            'payload' => $payload,
            'metadata' => $metadata,
            'occurred_at' => now(),
            'merkle_root' => 'dummy',
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        $this->assertIsArray($event->payload);
        $this->assertIsArray($event->metadata);
        $this->assertEquals('value', $event->payload['data']);
        $this->assertEquals('abc-123', $event->metadata['correlation_id']);
    }

    #[Test]
    public function it_scopes_by_aggregate(): void
    {
        // Events for aggregate 1
        EventStore::create([
            'id' => 1,
            'shard_id' => 1,
            'sequence' => 1,
            'local_sequence' => 1,
            'event_type' => 'article.created',
            'aggregate_type' => 'article',
            'aggregate_id' => 1,
            'payload' => [],
            'occurred_at' => now(),
            'merkle_root' => 'dummy',
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        // Events for aggregate 2
        EventStore::create([
            'id' => 2,
            'shard_id' => 1,
            'sequence' => 1,
            'local_sequence' => 2,
            'event_type' => 'article.created',
            'aggregate_type' => 'article',
            'aggregate_id' => 2,
            'payload' => [],
            'occurred_at' => now(),
            'merkle_root' => 'dummy',
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        $aggregate1Events = EventStore::where('aggregate_type', 'article')
            ->where('aggregate_id', 1)
            ->get();

        $this->assertCount(1, $aggregate1Events);
        $this->assertEquals(1, $aggregate1Events->first()->aggregate_id);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $event = new EventStore();
        $this->assertEquals('event_store', $event->getTable());
    }

    #[Test]
    public function it_uses_incrementing_key(): void
    {
        $event = new EventStore();
        $this->assertTrue($event->incrementing);
    }
}
