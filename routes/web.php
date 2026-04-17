<?php

use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::post('/booking', [BookingController::class, 'store'])
    ->middleware('auth')
    ->name('booking.store');

Route::middleware(['auth',  ])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/table', [AdminBookingController::class, 'table'])->name('bookings.table');
        Route::patch('/bookings/{booking}', [AdminBookingController::class, 'updateStatus'])->name('bookings.updateStatus');
    });
