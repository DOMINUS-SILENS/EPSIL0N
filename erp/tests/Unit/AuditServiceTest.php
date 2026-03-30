<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $audit;

    protected SequenceService $sequence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequence = app(SequenceService::class);
        $this->audit = app(AuditService::class);
    }

    #[Test]
    public function it_logs_audit_entry_with_correct_hash_chain()
    {
        $this->sequence->ensureExists('audit', 1);
        $seq = $this->sequence->next('audit', 1);
        $prevHash = '0';

        $data = [
            'company_id' => 1,
            'action' => 'create',
            'model' => 'order',
            'model_id' => 1,
            'old_values' => null,
            'new_values' => ['status' => 'draft'],
            'reason' => 'Test',
            'trace_id' => 'abc123',
            'event_time' => now(),
        ];

        $this->audit->log($data, $prevHash, $seq);

        $entry = AuditLog::where('company_id', 1)->first();
        $this->assertEquals($seq, $entry->sequence);
        $this->assertEquals($prevHash, $entry->previous_hash);
        $this->assertNotEmpty($entry->row_hash);

        $broken = $this->audit->verifyChain(1);
        $this->assertEmpty($broken);
    }

    #[Test]
    public function it_detects_broken_chain()
    {
        $this->sequence->ensureExists('audit', 1);
        $seq1 = $this->sequence->next('audit', 1);
        $this->audit->log(['company_id' => 1, 'action' => 'create', 'model' => 'order', 'model_id' => 1], '0', $seq1);

        $seq2 = $this->sequence->next('audit', 1);
        $this->audit->log(['company_id' => 1, 'action' => 'update', 'model' => 'order', 'model_id' => 1], 'fakehash', $seq2);

        $broken = $this->audit->verifyChain(1);
        $this->assertCount(1, $broken);
        $this->assertEquals($seq2, $broken[0]->sequence);
    }
}
