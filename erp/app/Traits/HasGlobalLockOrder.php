<?php

namespace App\Traits;

trait HasGlobalLockOrder
{
    protected function lockTables(array $tableNames): void
    {
        sort($tableNames);
        foreach ($tableNames as $table) {
            DB::statement("SELECT 1 FROM {$table} FOR UPDATE");
        }
    }
}
