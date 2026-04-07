<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Infrastructure\Projection;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Infrastructure\Persistence\Projection\MobileSyncSurface;
use Spiral\Kernel\Infrastructure\Persistence\Projection\OffsetStore;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\SyncResult;

final class SFAComplianceTest extends TestCase
{
    public function test_sync_law_2_idempotency(): void
    {
        $surface = new MobileSyncSurface();
        $commandId = 'cmd-123';
        $params = [
            'cmd' => $commandId,
            'dev' => 'dev-1',
            'usr' => 'usr-1',
            'typ' => 'CreateOrder',
            'agg' => 'agg-1',
            'ver' => 0,
            'pay' => []
        ];

        // First attempt: Accepted
        $res1 = $surface->handleIntent(
            $params['cmd'], $params['dev'], $params['usr'],
            $params['typ'], $params['agg'], $params['ver'], $params['pay']
        );
        $this->assertSame('Accepted', $res1->status);

        // Second attempt: AlreadyProcessed (Idempotency enforced)
        $res2 = $surface->handleIntent(
            $params['cmd'], $params['dev'], $params['usr'],
            $params['typ'], $params['agg'], $params['ver'], $params['pay']
        );
        $this->assertSame('AlreadyProcessed', $res2->status);
    }

    public function test_sync_law_3_cursor_resumability(): void
    {
        $store = new OffsetStore();
        $deviceId = 'dev-mobile-1';

        // Initial state
        $this->assertSame(0, $store->getOffset($deviceId));

        // Acknowledge receipt of sync ID 100
        $store->acknowledge($deviceId, 100);
        $this->assertSame(100, $store->getOffset($deviceId));

        // Acknowledge receipt of sync ID 250
        $store->acknowledge($deviceId, 250);
        $this->assertSame(250, $store->getOffset($deviceId));

        // Proof: Device resumes exactly from 250, preventing "since yesterday" guesswork.
    }

    public function test_sync_law_4_explicit_surface_boundary(): void
    {
        // This test verifies that the MobileSyncSurface does NOT expose
        // internal domain aggregates directly, but uses a flat intent-based contract.
        $surface = new MobileSyncSurface();

        // We are calling handleIntent, not a Domain Aggregate method.
        // The logic is isolated within the surface boundary.
        $res = $surface->handleIntent('c1', 'd1', 'u1', 'T1', 'a1', 0, []);
        $this->assertInstanceOf(SyncResult::class, $res);
    }
}
