<?php

use App\Events\ReservationCreated;
use App\Models\Book;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Artisan::call('migrate', ['--force' => true]);
});

it('creates booking and dispatches reservation created event', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $book = Book::query()->create([
        'name' => 'Test Book',
        'author' => 'Author',
        'description' => 'Description',
        'quntity' => 5,
        'img' => 'https://example.test/book.jpg',
        'price' => 100,
    ]);

    $response = $this->actingAs($user)->postJson(route('booking.store'), [
        'book_id' => $book->id,
        'quantity' => 2,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('book_id', $book->id)
        ->assertJsonPath('remaining_quantity', 3);

    expect(Booking::query()
        ->where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->where('status', '1')
        ->exists())->toBeTrue()
        ->and((int)$book->fresh()->quntity)->toBe(3);

    Event::assertDispatched(ReservationCreated::class);
});

it('returns validation error when requested quantity exceeds stock', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $book = Book::query()->create([
        'name' => 'Small Stock',
        'author' => 'Author',
        'description' => 'Description',
        'quntity' => 1,
        'img' => 'https://example.test/book.jpg',
        'price' => 100,
    ]);

    $response = $this->actingAs($user)->postJson(route('booking.store'), [
        'book_id' => $book->id,
        'quantity' => 2,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);

    expect((int) $book->fresh()->quntity)->toBe(1);

    Event::assertNotDispatched(ReservationCreated::class);
});

it('returns validation error when user tries to book same book twice in pending status', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $book = Book::query()->create([
        'name' => 'Duplicate Booking',
        'author' => 'Author',
        'description' => 'Description',
        'quntity' => 10,
        'img' => 'https://example.test/book.jpg',
        'price' => 100,
    ]);

    Booking::query()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => '1',
    ]);

    $response = $this->actingAs($user)->postJson(route('booking.store'), [
        'book_id' => $book->id,
        'quantity' => 1,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['booking']);

    Event::assertNotDispatched(ReservationCreated::class);
});

it('does not allow guest to create booking', function (): void {
    Event::fake();

    $book = Book::query()->create([
        'name' => 'Guest Book',
        'author' => 'Author',
        'description' => 'Description',
        'quntity' => 10,
        'img' => 'https://example.test/book.jpg',
        'price' => 100,
    ]);

    $this->postJson(route('booking.store'), [
        'book_id' => $book->id,
        'quantity' => 1,
    ])->assertUnauthorized();

    Event::assertNotDispatched(ReservationCreated::class);
});
