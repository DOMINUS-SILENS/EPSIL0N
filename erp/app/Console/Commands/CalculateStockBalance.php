<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateStockBalance extends Command
{
    protected $signature = 'stock:balance';

    protected $description = 'Calculate daily stock balance';

    public function handle()
    {
        $this->info('Calculating stock balance...');
        DB::statement('CALL calcule_balance_stock()');
        $this->info('Stock balance calculated.');

        return Command::SUCCESS;
    }
}
