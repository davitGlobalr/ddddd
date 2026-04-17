@extends('layouts.app')

@section('content')
<div class="container">
    <div id="booking-ajax-alert">
        @if (session('status'))
            <div class="alert alert-success mb-4" role="alert">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        @foreach ($books as $book)
            @php
                $alreadyBooked = $bookingsByBookId->has($book->id);
                $inStock = (int) $book->quntity > 0;
            @endphp

            <div class="col">
                <div class="card h-100">
                    @if (!empty($book->img))
                        <img
                            src="{{ $book->img }}"
                            alt="{{ $book->name }}"
                            class="card-img-top"
                            style="height: 220px; object-fit: cover;"
                        >
                    @endif

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $book->name }}</h5>
                        <div class="text-muted mb-2">{{ $book->author }}</div>

                        <p class="card-text">
                            {{ \Illuminate\Support\Str::limit($book->description ?? '', 140) }}
                        </p>

                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-secondary js-stock-badge">
                                    В наличии: {{ (int) $book->quntity }}
                                </span>
                                @if (! $inStock)
                                    <span class="text-danger small">Нет в наличии</span>
                                @endif
                            </div>

                            @auth
                                <form
                                    method="POST"
                                    action="{{ route('booking.store') }}"
                                    class="booking-form"
                                    data-book-id="{{ $book->id }}"
                                >
                                    @csrf
                                    <input type="hidden" name="book_id" value="{{ $book->id }}">

                                    <div class="d-flex align-items-center gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-qty-minus"
                                            data-qty-target="qty-{{ $book->id }}"
                                            {{ $inStock ? '' : 'disabled' }}
                                        >
                                            -
                                        </button>

                                        <input
                                            id="qty-{{ $book->id }}"
                                            type="number"
                                            class="form-control form-control-sm text-center qty-input"
                                            name="quantity"
                                            value="1"
                                            min="1"
                                            max="{{ (int) $book->quntity }}"
                                            step="1"
                                            data-qty-max="{{ (int) $book->quntity }}"
                                            {{ $inStock ? '' : 'disabled' }}
                                        >

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-qty-plus"
                                            data-qty-target="qty-{{ $book->id }}"
                                            {{ $inStock ? '' : 'disabled' }}
                                        >
                                            +
                                        </button>
                                    </div>

                                    <button
                                        type="submit"
                                        class="btn btn-primary w-100 mt-3"
                                        {{ ($alreadyBooked || ! $inStock) ? 'disabled' : '' }}
                                    >
                                        Booking
                                    </button>
                                </form>
                            @else
                                <div class="text-muted small">
                                    Войдите, чтобы сделать booking.
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $books->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
