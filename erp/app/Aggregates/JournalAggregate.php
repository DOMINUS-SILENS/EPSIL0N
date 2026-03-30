<?php

namespace App\Aggregates;

use App\Events\JournalEntryPosted;
use DomainException;

class JournalAggregate extends AggregateRoot
{
    /**
     * @param array $data MUST encapsulate values uniformly as integer Cents scaling.
     */
    public function postEntry(array $data, string $eventTime): self
    {
        $totalDebit = 0; // Explicitly initialized to strict integer
        $totalCredit = 0;

        $lines = $data['lines'] ?? [];
        if (empty($lines) || count($lines) < 2) {
            throw new DomainException("A journal entry must contain at least two lines.");
        }

        foreach ($lines as $line) {
            // Absolute truncation of Float logic; casts explicitly to int boundary (Cents)
            // Implicit string coersion prohibited. Data payload validation enforces integers via Request
            $totalDebit += (int) $line['debit'];
            $totalCredit += (int) $line['credit'];
        }

        if ($totalDebit !== $totalCredit) {
            throw new DomainException("Journal entry unbalanced: Debits ({$totalDebit} cents) do not equal Credits ({$totalCredit} cents).");
        }

        if ($totalDebit === 0 && $totalCredit === 0) {
            throw new DomainException("Journal entry cannot be zero-value.");
        }

        $this->recordThat(new JournalEntryPosted(
            $this->uuid(),
            $data['entreprise_id'] ?? 1,
            $data,
            $eventTime
        ));
        return $this;
    }

    protected function applyJournalEntryPosted(JournalEntryPosted $event): void
    {
        $this->tenantId = $event->entrepriseId;
    }
}
