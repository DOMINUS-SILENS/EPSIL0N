<?php

namespace Tests\Unit\Models;

use App\Models\EventSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EventSchema Model Test Suite
 * 
 * Tests the EventSchema model including:
 * - Schema registration
 * - JSON schema storage
 * - Version management
 * - Active/Inactive states
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\EventSchema
 */
class EventSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_register_event_schema(): void
    {
        $schema = EventSchema::create([
            'event_type' => 'stock.entered',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'article_id' => ['type' => 'integer'],
                    'quantity' => ['type' => 'number'],
                ],
                'required' => ['article_id', 'quantity'],
            ],
            'version' => 1,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('event_schemas', [
            'event_type' => 'stock.entered',
            'version' => 1,
        ]);
    }

    #[Test]
    public function it_stores_schema_as_json(): void
    {
        $schemaData = [
            'type' => 'object',
            'properties' => [
                'product_id' => ['type' => 'integer'],
            ],
        ];

        $schema = EventSchema::create([
            'event_type' => 'order.created',
            'schema' => $schemaData,
            'version' => 1,
            'is_active' => true,
        ]);

        $this->assertIsArray($schema->schema);
        $this->assertEquals('object', $schema->schema['type']);
    }

    #[Test]
    public function it_manages_schema_versions(): void
    {
        // Version 1
        $schema = EventSchema::create([
            'event_type' => 'payment.processed',
            'schema' => ['version' => 1],
            'version' => 1,
            'is_active' => false,
        ]);

        // Version 2 (active)
        $schema->update([
            'schema' => ['version' => 2],
            'version' => 2,
            'is_active' => true,
        ]);

        $this->assertTrue($schema->is_active);
        $this->assertEquals(2, $schema->version);
    }

    #[Test]
    public function it_uses_event_type_as_primary_key(): void
    {
        $schema = new EventSchema();
        $this->assertEquals('event_type', $schema->getKeyName());
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $schema = new EventSchema();
        $this->assertEquals('event_schemas', $schema->getTable());
    }

    #[Test]
    public function it_does_not_increment_primary_key(): void
    {
        $schema = new EventSchema();
        $this->assertFalse($schema->incrementing);
    }
}
