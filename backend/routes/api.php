<?php

use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizerEventController;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::post('/login', [AuthController::class, 'login']);
//Route::post('/logout', [AuthController::class,'logout'])->middleware('auth');

// --- Auth ---
// Login/Logout: legyen 'web' middleware, hogy legyen session
//Route::post('/auth/login', [AuthController::class,'login'])->middleware('web');  // <<< FONTOS

//Route::post('/auth/logout', [AuthController::class,'logout'])->middleware(['web','auth:sanctum']);  // <<< FONTOS

// 'me' marad API-n, csak auth kell
//Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me']);
Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

//Route::middleware('auth:sanctum')->controller(AuthController::class)->group(function() {
//    Route::get('/auth/me', 'me')->name('auth.me');
//    Route::post('/auth/logout', 'logout')->name('auth.logout');
//});

// --- Publikus események (user auth nem kötelező) ---
Route::get('/events', [EventController::class, 'events'])->name('events');
Route::get('/events/{event}', [EventController::class, 'event'])->name('events.event');
Route::post('/bookings', [BookingController::class, 'store'])->name('events.booking');

// --- Organizer zóna ---
Route::middleware(['auth:sanctum','role:organizer|admin', EnsureUserIsNotBlocked::class])
    ->prefix('organizer')->name('organizer.')->controller(OrganizerEventController::class)
    ->group(function() {
        // Események lekérése
        //Route::get('/events', 'getEvents')->name('events')->middleware('permission:event.view.any');
        Route::get('/events/{event}', 'show')->name('events.show')->middleware('permission:event.view.any');
        // Új esemény létrehozása
        Route::post('/events', 'store')->name('create')->middleware('permission:event.create');
        // Esemény szerkesztése
        Route::put('/events/{event}', 'update')->name('update')->middleware('permission:event.update.own');
        // Esemény törlése
        Route::delete('/events/{event}', 'destroy')->name('delete')->middleware('permission:event.delete.own');
        // Organizer: publikálás (saját eseményre – policy szűr)
        Route::post('/events/{event}/publish', 'publish')->name('publish')->middleware('permission:event.publish.own');
        // Esemény lemondása
        Route::post('/events/{event}/cancel', 'cancel')->name('cancel')->middleware('permission:event.cancel.own');
    }
);

// --- Foglalás (user) ---
Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])
    ->controller(BookingController::class)->group(function() {
        //
        Route::post('/events/{event}/bookings', 'store')->name('booking.store')
            ->middleware('permission:booking.create');
        //
        Route::get('/my/bookings', 'myBookings')->name('my.bookings')
            ->middleware('permission:booking.view.mine');
        //
        Route::patch('/my/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('my.bookings.cancel')
            ->middleware('permission:booking.cancel.mine');
    }
);

// Admin – foglalások átnézése
Route::middleware(['auth:sanctum','role:admin'])
    ->controller(AdminBookingController::class)->prefix('admin')->name('admin.')
    ->group(function () {
        // Foglalások lekérése
        Route::get('/bookings', 'getBookings')
            ->name('bookings.getBookings')->middleware('permission:booking.view.any');
        // Foglalás rögzítése
        //Route::post('/bookings', '')->name('')->middleware();
        // Foglalás megszakítása
        Route::patch('/bookings/{booking}/cancel', 'cancel')
            ->name('bookings.cancel')->middleware('permission:booking.cancel.any');
    });
    
// --- Admin ---
Route::middleware(['auth:sanctum','role:admin'])
    ->controller(AdminEventController::class)->prefix('admin')->name('admin.')
    ->group(function () {
        //
        Route::get('/events', 'index')->name('events.index')->middleware('permission:event.view.any');
        //
        Route::get('/events/{event}', 'show')->name('events.show')->middleware('permission:event.view.any');
        // 
        Route::patch('/events/{event}/cancel', 'cancel')->name('events.cancel')->middleware('permission:event.cancel.any');
    });
    
// --- Admin ---
Route::middleware(['auth:sanctum','role:admin'])
    ->controller(AdminUserController::class)->prefix('admin')->name('admin.')
    ->group(function() {
        Route::get('/users', 'userList')->name('user.list')->middleware('permission:user.list');
        Route::patch('/users/{user}/block', 'userBlock')->name('users.block')->middleware('permission:user.block');
        Route::patch('/users/{user}/unblock', 'userUnblock')->name('users.unblock')->middleware('permission:user.unblock');
    }
);
