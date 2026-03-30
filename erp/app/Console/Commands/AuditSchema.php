<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class AuditSchema extends Command
{
    protected $signature = 'gate:schema-audit {--output=docs/certification/gate-b/00-schema-reality-check.md}';
    protected $description = 'Automatically audits physical schema and outputs the truth matrix for Phase B0.';

    public function handle()
    {
        $this->info("Initializing Schema Reality Audit...");

        $outputFile = base_path($this->option('output'));
        $content = "# Phase B0: Schema Reality Check (Automated Audit)\n\n";
        $content .= "## Objective\nEstablish absolute truth of physical DB schema compared against application logic.\n\n";

        // 1. Check migrations
        $this->info("Checking migration status...");
        Artisan::call('migrate:status');
        $migrateStatus = Artisan::output();
        
        $content .= "## Migration Status (Summary)\n```text\n$migrateStatus\n```\n\n";

        // 2. Audit Tables
        $tables = [
            'event_store',
            'domain_outbox',
            'orders',
            'commandes', // Legacy fallback check
            'article',
            'balance_stock',
            'device_sync_state',
            'projector_processed_events'
        ];

        $content .= "## Table Reality Inventory\n\n";

        foreach ($tables as $table) {
            $this->info("Auditing table: $table");
            
            $content .= "### Table: `$table`\n";
            
            if (!Schema::hasTable($table)) {
                $content .= "- **Status:** ❌ TABLE MISSING\n\n";
                continue;
            }

            $count = DB::table($table)->count();
            $content .= "- **Status:** ✅ EXISTS\n";
            $content .= "- **Row Count:** $count\n";

            // Check for entreprise_id
            if (Schema::hasColumn($table, 'entreprise_id')) {
                $content .= "- **Entreprise Alignment:** ✅ `entreprise_id` present.\n";
            } else {
                $content .= "- **Entreprise Alignment:** ❌ MISSING `entreprise_id` (Gate B Violation)\n";
            }

            try {
                $schemaDefRows = DB::select("SHOW CREATE TABLE `$table`");
                $schemaDef = $schemaDefRows[0]->{'Create Table'} ?? 'N/A';
                
                // Check if entreprise_id is indexed or in PK
                if (str_contains($schemaDef, 'PRIMARY KEY') && str_contains($schemaDef, 'entreprise_id')) {
                    $content .= "- **Indexing:** ✅ `entreprise_id` is part of PRIMARY KEY.\n";
                } elseif (str_contains($schemaDef, 'KEY') && str_contains($schemaDef, 'entreprise_id')) {
                    $content .= "- **Indexing:** ⚠️ `entreprise_id` is indexed but NOT part of PRIMARY KEY.\n";
                } else {
                    $content .= "- **Indexing:** ❌ `entreprise_id` NOT INDEXED (Performance Risk)\n";
                }

                $content .= "\n#### Physical Schema:\n```sql\n$schemaDef\n```\n\n";
            } catch (\Exception $e) {
                $content .= "\n#### Physical Schema:\n```text\nError fetching schema: " . $e->getMessage() . "\n```\n\n";
            }
        }

        file_put_contents($outputFile, $content);

        $this->info("Schema Reality Audit saved to " . $this->option('output'));
    }
}
