<?php

namespace App\Services;

class AccountingService
{
    public function postJournal(JournalEntry $entry, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($entry, $lines) {
            // ... locking and validation ...

            // Save lines, etc.

            // Publish domain event
            $this->outbox->publishDomain(
                'journal_entry',
                $entry->id,
                'journal_entry.posted',
                [
                    'journal_entry_id' => $entry->id,
                    'lines' => collect($lines)->map(function ($line) {
                        return [
                            'customer_id' => $line['customer_id'] ?? null,
                            'debit' => $line['debit'],
                            'credit' => $line['credit'],
                        ];
                    })->all(),
                ]
            );

            return $entry;
        });
    }
}
