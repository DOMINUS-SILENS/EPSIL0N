<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbCreate extends Command
{
    protected $signature = 'db:create {name?}';

    protected $description = 'Create a new database';

    public function handle()
    {
        $dbName = $this->argument('name') ?? env('DB_DATABASE');

        try {
            DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `$dbName`");
            $this->info("MySQL database '$dbName' created successfully.");
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
