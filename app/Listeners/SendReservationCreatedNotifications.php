<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Models\User;
use App\Notifications\ReservationCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendReservationCreatedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationCreated $event): void
    {
        $booking = $event->booking->loadMissing([
            'user:id,name,email',
            'book:id,name,author',
        ]);

        Log::info('booking.reservation_created_listener_started', [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'book_id' => $booking->book_id,
        ]);

        if ($booking->user) {
            $booking->user->notify(new ReservationCreatedNotification($booking, 'user'));
        }

        $adminUsers = User::query()
            ->role(['superadmin', 'manager'])
            ->when($booking->user_id, fn ($query) => $query->where('id', '!=', $booking->user_id))
            ->get();

        if ($adminUsers->isNotEmpty()) {
            Notification::send($adminUsers, new ReservationCreatedNotification($booking, 'admin'));
        }

        Log::info('booking.reservation_created_listener_finished', [
            'booking_id' => $booking->id,
            'notified_user' => (bool) $booking->user,
            'notified_admins_count' => $adminUsers->count(),
        ]);
    }

    public function __invoke(ReservationCreated $event): void
    {
        $this->handle($event);
    }
}
