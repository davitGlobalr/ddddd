<?php

namespace App\Http\Controllers;

use App\Events\ReservationCreated;
use App\Models\Book;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $bookId = (int) $validated['book_id'];
        $quantity = (int) $validated['quantity'];

        $bookingData = DB::transaction(function () use ($user, $bookId, $quantity): array {
            $book = Book::query()
                ->whereKey($bookId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($book->quntity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Requested quantity exceeds available stock.',
                ]);
            }

            $alreadyBooked = Booking::query()
                ->where('user_id', $user->id)
                ->where('book_id', $bookId)
                ->where('status', '1')
                ->exists();

            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'booking' => 'This book is already booked.',
                ]);
            }

            // Reduce stock and create a booking request.
            $book->quntity -= $quantity;
            $book->save();

            $booking = Booking::create([
                'user_id' => $user->id,
                'book_id' => $bookId,
                'status' => '1',
            ]);

            return [
                'remaining_quantity' => (int) $book->quntity,
                'booking' => $booking,
            ];
        });

        Log::info('booking.created', [
            'booking_id' => $bookingData['booking']->id,
            'user_id' => $user->id,
            'book_id' => $bookId,
            'quantity' => $quantity,
            'remaining_quantity' => $bookingData['remaining_quantity'],
        ]);

        event(new ReservationCreated($bookingData['booking']));

        Log::info('booking.reservation_created_event_dispatched', [
            'booking_id' => $bookingData['booking']->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Booking created successfully.',
                'book_id' => $bookId,
                'remaining_quantity' => $bookingData['remaining_quantity'],
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Booking created successfully.');
    }
}
