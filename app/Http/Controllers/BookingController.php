<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $bookId = (int) $validated['book_id'];
        $quantity = (int) $validated['quantity'];

        DB::transaction(function () use ($user, $bookId, $quantity): void {
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

            Booking::create([
                'user_id' => $user->id,
                'book_id' => $bookId,
                'status' => '1',
            ]);
        });

        return redirect()
            ->back()
            ->with('status', 'Booking created successfully.');
    }
}
