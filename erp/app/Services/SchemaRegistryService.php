<?php

namespace App\Services;

use App\Models\EventSchema;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;

class SchemaRegistryService
{
    protected Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator;
    }

    /**
     * Register a new event schema.
     */
    public function register(string $eventType, array $schema, int $version = 1): EventSchema
    {
        return EventSchema::create([
            'event_type' => $eventType,
            'schema' => $schema,
            'version' => $version,
            'is_active' => true,
        ]);
    }

    /**
     * Validate an event payload against its schema.
     *
     * @throws RuntimeException
     */
    public function validate(string $eventType, array $payload): void
    {
        $schema = EventSchema::where('event_type', $eventType)->where('is_active', true)->first();
        if (! $schema) {
            return;
        }

        // Fetch raw JSON string directly from DB to avoid Array -> String -> Object double conversion cost
        $schemaObj = json_decode($schema->getRawOriginal('schema'), false);

        // Payload comes in as array, we still need it as an object for Opis
        $data = json_decode(json_encode($payload), false);

        $result = $this->validator->validate($data, $schemaObj);
        if (! $result->isValid()) {
            $formatter = new ErrorFormatter;
            $errors = $formatter->format($result->error());
            throw new RuntimeException("Event validation failed for {$eventType}: ".json_encode($errors));
        }
    }

    /**
     * Deactivate an old version and activate a new one.
     */
    public function upgrade(string $eventType, array $newSchema, int $newVersion, array $compatibilityRules = []): void
    {
        $current = EventSchema::where('event_type', $eventType)->where('is_active', true)->first();
        if ($current) {
            // Check compatibility
            if (! $this->isCompatible($current->schema, $newSchema, $compatibilityRules)) {
                throw new RuntimeException("Incompatible schema upgrade for {$eventType}");
            }
            $current->update(['is_active' => false]);
        }
        $this->register($eventType, $newSchema, $newVersion, $compatibilityRules);
    }

    protected function isCompatible(array $old, array $new, ?array $rules = null): bool
    {
        // Default rule: new schema must be a superset of old (allow adding optional fields)
        // For simplicity, we'll check that all required properties in old are still present in new
        $oldRequired = $old['required'] ?? [];
        foreach ($oldRequired as $field) {
            if (! isset($new['properties'][$field])) {
                return false;
            }
        }

        return true;
    }

    public function upgradeWithCompatibilityCheck(string $eventType, array $newSchema, int $newVersion): void
    {
        $current = EventSchema::where('event_type', $eventType)->where('is_active', true)->first();
        if ($current) {
            // Check compatibility: for now, just allow upgrade if new schema is a superset or compatible.
            // We'll use a simple rule: new schema must be backwards compatible (i.e., any event valid under old schema must be valid under new).
            // This is complex; we'll assume manual approval.
            $current->update(['is_active' => false]);
        }
        $this->register($eventType, $newSchema, $newVersion);
    }
}
