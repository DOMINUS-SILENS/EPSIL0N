<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use App\Models\ProjectionVersion;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class JournalProjector extends Projector
{
    protected string $table = 'journal_entries';
    protected string $idField = 'id';

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(['projector_name' => self::class], ['version' => $version]);
    }

    protected function getState(int $aggregateId): array { return []; }
    protected function restoreState(int $aggregateId, array $state): void {}
    protected function setLastProcessedEventId(int $aggregateId, int $lastEventId): void {}

    public function handleJournalEntryPosted(array $payload, DomainOutbox $event): void
    {
        $entrepriseId = $payload['entreprise_id'];
        if (!$entrepriseId) {
            throw new \RuntimeException("Strict Enforce: Missing entreprise_id on Journal Entry.");
        }

        $journalId = DB::table('journal_entries')->insertGetId([
            'entreprise_id' => $entrepriseId,
            'description' => $payload['description'] ?? '',
            'date' => $payload['entry_date'] ?? $payload['eventTime'] ?? now(),
            'state' => 'posted',
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lines = [];
        foreach ($payload['lines'] as $line) {
            $lines[] = [
                'journal_entry_id' => $journalId,
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? '',
                'debit' => $line['debit'] ?? 0.0,
                'credit' => $line['credit'] ?? 0.0,
                'customer_id' => $line['customer_id'] ?? null,
                'last_event_id' => $event->id,
            ];
        }

        DB::table('journal_lines')->insert($lines);
    }
}
