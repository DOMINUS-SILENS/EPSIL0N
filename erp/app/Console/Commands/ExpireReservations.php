<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Expire pending reservations that have passed expiry date';

    public function handle(ReservationService $reservationService): void
    {
        $reservationService->expireReservations();
        $this->info('Reservations expired.');
    }
}
