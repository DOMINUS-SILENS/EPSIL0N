<?php

namespace App\Traits;

trait HasCanonicalRouting
{
    /**
     * Get the table name dynamically based on canonical routing flags.
     * 
     * @return string
     */
    public function getTable()
    {
        if (config('epsilon.canonical_reads')) {
            return "canonical_{$this->canonicalTable}";
        }
        
        return $this->legacyTable;
    }
}
