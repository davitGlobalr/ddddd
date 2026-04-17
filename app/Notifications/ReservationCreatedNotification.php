<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $audience = 'user',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing([
            'user:id,name,email',
            'book:id,name,author',
        ]);

        $recipientLine = $this->audience === 'admin'
            ? 'A new reservation has been created by a user.'
            : 'Your reservation has been created successfully.';

        return (new MailMessage)
            ->subject('Reservation Created')
            ->line($recipientLine)
            ->line('Reservation ID: '.$booking->id)
            ->line('User: '.($booking->user?->name ?? '—').' (ID: '.$booking->user_id.')')
            ->line('Book: '.($booking->book?->name ?? '—').' (ID: '.$booking->book_id.')')
            ->line('Status: '.$booking->status)
            ->line('Created At: '.optional($booking->created_at)?->toDateTimeString());
    }
}
