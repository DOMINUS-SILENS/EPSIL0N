<?php

namespace Tests\Unit;

use App\Models\DomainEvent;
use App\Models\DomainOutbox;
use App\Models\IntegrationOutbox;
use App\Services\OutboxService;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class OutboxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OutboxService $outbox;

    protected SequenceService|MockObject $sequence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sequence = $this->createMock(SequenceService::class);
        $this->outbox = new OutboxService($this->sequence);
    }

    #[Test]
    public function it_creates_domain_event_with_sequence()
    {
        $this->sequence->expects($this->once())
            ->method('next')
            ->willReturn(1);

        $this->outbox->publishDomain('order', '1', 'order.created', ['amount' => 100], 1);

        $domainEvent = DomainEvent::first();
        $this->assertNotNull($domainEvent);
        $this->assertEquals('order', $domainEvent->aggregate_type);
        $this->assertEquals('1', $domainEvent->aggregate_id);
        $this->assertEquals(1, $domainEvent->sequence);
        $this->assertEquals('order.created', $domainEvent->event_type);
        $this->assertStringContainsString('amount', $domainEvent->payload);

        $outbox = DomainOutbox::where('event_id', $domainEvent->id)->first();
        $this->assertNotNull($outbox);
        $this->assertEquals('pending', $outbox->status);
    }

    #[Test]
    public function it_creates_integration_event_with_idempotency_key()
    {
        $this->sequence->method('next')->willReturn(1);

        $this->outbox->publishDomain('order', '1', 'order.created', [], 1);
        $domainEvent = DomainEvent::first();

        $integration = $this->outbox->publishIntegration(
            $domainEvent->id,
            'email',
            'customer@example.com',
            ['subject' => 'Welcome'],
            'order-1-welcome-email'
        );

        $this->assertInstanceOf(IntegrationOutbox::class, $integration);
        $this->assertEquals($domainEvent->id, $integration->domain_event_id);
        $this->assertEquals('email', $integration->integration_type);
        $this->assertEquals('order-1-welcome-email', $integration->idempotency_key);
        $this->assertEquals('pending', $integration->status);
    }

    #[Test]
    public function it_rolls_back_outbox_on_transaction_failure()
    {
        $this->sequence->method('next')->willThrowException(new \Exception('Simulated failure'));

        try {
            $this->outbox->publishDomain('order', '1', 'order.created', [], 1);
            $this->fail('Expected exception was not thrown');
        } catch (\Throwable $e) {
            $this->assertEquals('Simulated failure', $e->getMessage());
        }

        $this->assertEquals(0, DomainEvent::count());
        $this->assertEquals(0, DomainOutbox::count());
        $this->assertEquals(0, IntegrationOutbox::count());
    }
}
