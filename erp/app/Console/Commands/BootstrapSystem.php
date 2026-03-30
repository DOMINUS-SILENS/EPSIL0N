<?php

namespace App\Console\Commands;

use App\Services\BootstrapService;
use Illuminate\Console\Command;

class BootstrapSystem extends Command
{
    protected $signature = 'app:bootstrap';

    protected $description = 'Run system bootstrap (verify invariants, warm caches, set mode)';

    public function handle(BootstrapService $bootstrap)
    {
        $bootstrap->bootstrap();
        $this->info('System bootstrapped.');
    }
}
