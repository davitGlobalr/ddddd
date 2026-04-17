<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BookingTableRowResource;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBookingController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.bookings.index', [
            'tableUrl' => route('admin.bookings.table'),
        ]);
    }

    /**
     * JSON for bootstrap-table (server-side pagination, search).
     */
    public function table(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 5);
        $offset = (int) $request->query('offset', 0);
        $search = (string) $request->query('search', '');

        $limit = min(max($limit, 1), 100);
        $offset = max($offset, 0);

        $baseQuery = Booking::query()->with(['user:id,name', 'book:id,name']);

        if ($search !== '') {
            $s = '%'.$search.'%';
            $baseQuery->where(function (Builder $q) use ($s): void {
                $q->where('booking.id', 'like', $s)
                    ->orWhere('booking.user_id', 'like', $s)
                    ->orWhere('booking.book_id', 'like', $s)
                    ->orWhere('booking.status', 'like', $s)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $s))
                    ->orWhereHas('book', fn (Builder $b) => $b->where('name', 'like', $s));
            });
        }

        $page = intdiv($offset, $limit) + 1;

        $paginator = (clone $baseQuery)
            ->orderByDesc('booking.id')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'total' => $paginator->total(),
            'rows' => BookingTableRowResource::collection($paginator->getCollection())->resolve(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['2', '3'])],
        ]);

        if ($booking->status !== '1') {
            return redirect()->back()->withErrors([
                'status' => 'Only pending bookings can be changed.',
            ]);
        }

        $booking->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('status', 'Booking status updated.');
    }
}
