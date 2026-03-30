<?php

namespace Tests\Unit;

use App\Models\EventStore;
use App\Services\EventStoreService;
use App\Services\SchemaRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventStoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EventStoreService $eventStore;

    protected SchemaRegistryService $schemaRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaRegistry = app(SchemaRegistryService::class);
        $this->eventStore = app(EventStoreService::class);
    }

    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    #[Test]
    public function it_appends_event_with_schema_validation()
    {
        // Register a schema
        $schema = [
            'type' => 'object',
            'properties' => [
                'amount' => ['type' => 'number', 'minimum' => 0],
            ],
            'required' => ['amount'],
        ];
        $this->schemaRegistry->register('order.created', $schema);

        // Valid event
        $event = $this->eventStore->append('order', 1, 'order.created', ['amount' => 100]);
        $this->assertInstanceOf(EventStore::class, $event);
        $this->assertEquals('order.created', $event->event_type);
        $this->assertNotEmpty($event->merkle_root);

        // Invalid event
        $this->expectException(\RuntimeException::class);
        $this->eventStore->append('order', 1, 'order.created', ['amount' => -10]);
    }

    #[Test]
    public function it_assigns_deterministic_shard()
    {
        $shard = $this->invokeMethod($this->eventStore, 'getShardForAggregate', ['order', 1]);
        $shard2 = $this->invokeMethod($this->eventStore, 'getShardForAggregate', ['order', 1]);
        $this->assertEquals($shard, $shard2);
    }

    #[Test]
    public function it_maintains_hash_chain_per_shard()
    {
        $event1 = $this->eventStore->append('order', 1, 'order.created', ['status' => 'draft']);
        $event2 = $this->eventStore->append('order', 1, 'order.created', ['status' => 'confirmed']);

        $this->assertNotEquals($event1->merkle_root, $event2->merkle_root);
        $this->assertEquals($event1->merkle_root, $event2->previous_hash);
    }
}
