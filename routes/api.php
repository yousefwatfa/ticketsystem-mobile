<?php

use App\Http\Controllers\Api\V1\AdminTicketController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

/*
 * Mobile app API (React Native / Expo). Bearer tokens via Sanctum.
 * The server is internal, so the API is only reachable from the hospital network.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('categories', [TicketController::class, 'categories'])->name('categories');

        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/messages', [TicketController::class, 'reply'])->name('tickets.reply');
        Route::post('tickets/{ticket}/attachments', [TicketController::class, 'attach'])->name('tickets.attach');
        Route::post('tickets/{ticket}/confirm', [TicketController::class, 'confirm'])->name('tickets.confirm');
        Route::post('tickets/{ticket}/not-fixed', [TicketController::class, 'notFixed'])->name('tickets.not-fixed');

        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
            Route::post('tickets/{ticket}/take', [AdminTicketController::class, 'take'])->name('tickets.take');
            Route::post('tickets/{ticket}/finish', [AdminTicketController::class, 'finish'])->name('tickets.finish');
        });

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
        Route::post('devices', [NotificationController::class, 'registerDevice'])->name('devices.register');
        Route::delete('devices', [NotificationController::class, 'unregisterDevice'])->name('devices.unregister');
    });
});
