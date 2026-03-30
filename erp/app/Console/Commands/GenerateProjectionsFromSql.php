<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateProjectionsFromSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:generate-from-sql {file : The path to the SQL dump file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Laravel projection migrations from a legacy SQL dump, appending entreprise_id partitioning and last_event_id idempotency flags.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("SQL dump file not found: {$file}");
            return Command::FAILURE;
        }

        $sql = file_get_contents($file);
        
        // Extract CREATE TABLE statements using regex
        preg_match_all('/CREATE TABLE `(.*?)` \((.*?)\)(.*?);/s', $sql, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->info("No CREATE TABLE statements found.");
            return Command::SUCCESS;
        }

        foreach ($matches as $match) {
            $tableName = $match[1];
            $columnsSql = $match[2];
            
            $this->generateMigrationFile($tableName, $columnsSql);
        }

        $this->info("Projection migrations generated successfully!");
        return Command::SUCCESS;
    }

    protected function generateMigrationFile(string $tableName, string $columnsSql): void
    {
        $className = 'Create' . Str::studly($tableName) . 'Table';
        $fileName = date('Y_m_d_His') . '_create_' . $tableName . '_table.php';
        
        $migrationContent = "<?php\n\n";
        $migrationContent .= "use Illuminate\Database\Migrations\Migration;\n";
        $migrationContent .= "use Illuminate\Database\Schema\Blueprint;\n";
        $migrationContent .= "use Illuminate\Support\Facades\Schema;\n";
        $migrationContent .= "use Illuminate\Support\Facades\DB;\n\n";
        
        $migrationContent .= "return new class extends Migration\n{\n";
        $migrationContent .= "    public function up(): void\n    {\n";
        $migrationContent .= "        Schema::create('{$tableName}', function (Blueprint \$table) {\n";
        
        // 1. Partition Key
        $migrationContent .= "            \$table->unsignedBigInteger('entreprise_id'); // partition key\n";

        // Regex parse columns and primary keys to convert to Blueprints
        // Very simplified generation for pseudo-logic (A real parser would be highly intricate)
        $lines = explode("\n", trim($columnsSql));
        $originalPrimaryKey = 'id'; // default fallback
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/PRIMARY KEY \(`(.*?)`\)/', $line, $pkMatch)) {
                $originalPrimaryKey = $pkMatch[1];
                continue;
            }
            
            if (preg_match('/`([a-zA-Z0-9_]+)` ([a-zA-Z0-9_]+)(\((.*?)\))?(.*?),?/', $line, $colMatch)) {
                $colName = $colMatch[1];
                $colType = strtolower($colMatch[2]);
                
                // Exclude manual primary key definitions here if we composite it later
                $blueprintType = $this->mapSqlToBlueprint($colType);
                $migrationContent .= "            \$table->{$blueprintType}('{$colName}')->nullable();\n";
            }
        }
        
        // Setup Composite PK
        $migrationContent .= "            \$table->primary(['entreprise_id', '{$originalPrimaryKey}']);\n\n";

        // Idempotency and Traceability
        $migrationContent .= "            \$table->unsignedBigInteger('last_event_id')->nullable();\n";
        $migrationContent .= "            \$table->timestamps();\n";
        
        $migrationContent .= "        });\n\n";
        
        // God-Level Partitioning
        $migrationContent .= "        try {\n";
        $migrationContent .= "            DB::statement('ALTER TABLE {$tableName} PARTITION BY HASH(entreprise_id) PARTITIONS 16');\n";
        $migrationContent .= "        } catch (\\Exception \$e) {\n";
        $migrationContent .= "            // Ignore if OS/DB lacks partition privileges entirely\n";
        $migrationContent .= "        }\n";
        
        $migrationContent .= "    }\n\n";
        $migrationContent .= "    public function down(): void\n    {\n";
        $migrationContent .= "        Schema::dropIfExists('{$tableName}');\n";
        $migrationContent .= "    }\n";
        $migrationContent .= "};\n";

        file_put_contents(database_path("migrations/{$fileName}"), $migrationContent);
        
        // Sleep to avoid timestamp collusions in file names
        sleep(1);
    }

    protected function mapSqlToBlueprint(string $sqlType): string
    {
        return match($sqlType) {
            'int', 'bigint', 'tinyint' => 'bigInteger',
            'varchar', 'text', 'longtext' => 'string',
            'datetime', 'timestamp' => 'timestamp',
            'decimal', 'double', 'float' => 'decimal',
            default => 'string',
        };
    }
}
