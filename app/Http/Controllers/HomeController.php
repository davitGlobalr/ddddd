<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Booking;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index(Request $request)
    {
        $books = Book::query()
            ->orderByDesc('id')
            ->paginate(9);

        $bookingsByBookId = collect();

        if ($request->user()) {
            $bookingsByBookId = Booking::query()
                ->where('user_id', $request->user()->id)
                ->where('status', '1')
                ->pluck('book_id')
                ->flip();
        }

        return view('home', [
            'books' => $books,
            'bookingsByBookId' => $bookingsByBookId,
        ]);
    }
}
