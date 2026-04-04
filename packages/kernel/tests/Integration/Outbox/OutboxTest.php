<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\Outbox;

use Spiral\Kernel\Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for Outbox pattern.
 *
 * These tests verify:
 * - Event outbox storage
 * - Event dispatch to subscribers
 * - At-least-once delivery guarantee
 * - Dead letter queue handling
 *
 * Requires: PostgreSQL with outbox tables (Phase 5+)
 *
 * @package Spiral\Kernel\Tests\Integration\Outbox
 */
final class OutboxTest extends IntegrationTestCase
{
    public function testStoreEventInOutbox(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Implement when IOutboxStore is available
    }

    public function testDispatchEventsToSubscribers(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Implement when event dispatcher is available
    }

    public function testMarkEventAsProcessed(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test event processing acknowledgment
    }

    public function testRetryFailedDispatch(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test retry mechanism for failed dispatches
    }

    public function testDeadLetterQueueForPermanentFailures(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test dead letter queue handling
    }
}
