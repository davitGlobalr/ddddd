<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('booking:expire-pending')]
#[Description('Cancel pending bookings older than 30 minutes')]
class ExpirePendingBookings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = Booking::query()
            ->where('status', '1')
            ->where('created_at', '<=', now()->subMinutes(1))
            ->update([
                'status' => '3',
            ]);

        $this->info("Expired pending bookings: {$expiredCount}");

        return self::SUCCESS;
    }
}
