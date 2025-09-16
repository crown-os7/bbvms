<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BookingMeetingController;
use App\Http\Controllers\MinuteOfMeetingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VisitorController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        // 'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome'); // ✅ kasih nama

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');



Route::middleware(['auth', 'verified'])->group(function () {

    Route::post('/bookingmeeting/{id}/cancel', [BookingMeetingController::class, 'cancel'])
    ->name('bookingmeeting.cancel');
    
    Route::resource('bookingmeeting', BookingMeetingController::class);
});


Route::get('/check-room-status', [BookingMeetingController::class, 'checkRoomStatus'])
    ->name('check.room.status');


//Tanpa Login
Route::get('/visitor-form/create', [VisitorController::class, 'create'])->name('visitors.create');
Route::post('/visitor-form', [VisitorController::class, 'store'])->name('visitors.store');

Route::get('/visitors/by-referral', [VisitorController::class, 'getByReferral']);

// ✅ Halaman tanpa login
Route::get('/check-in/index', function () {
    return Inertia::render('VisitorForm/IndexCheckIn');
})->name('check-in.index');

// ✅ Halaman tanpa login
Route::get('/check-out/index', function () {
    return Inertia::render('VisitorForm/IndexCheckOut');
})->name('check-out.index');

Route::get('/check-in', [VisitorController::class, 'indexCheckIn'])->name('visitors.checkin.form');
Route::get('/check-out', [VisitorController::class, 'indexCheckOut'])->name('visitors.checkout.form');
Route::get('/visitor/print', [VisitorController::class, 'print'])
    ->name('visitor.print');

Route::get('/bookingmeeting/{id}/minute', [BookingMeetingController::class, 'minute'])
->name('minuteofmeeting.show');

Route::post('/bookingmeeting/{id}/minute', [BookingMeetingController::class, 'storeMinute'])
->name('minuteofmeeting.store');


Route::get('/visitor/detail/{booking}/{visitor}', [VisitorController::class, 'detail'])
     ->name('visitor.detail');


// ✅ Action POST
Route::post('/visitors/check-in', [VisitorController::class, 'checkIn'])->name('visitors.checkin');
Route::post('/visitors/check-out', [VisitorController::class, 'checkout'])->name('visitors.checkout');

Route::get('/minuteofmeeting/{id}/export', [MinuteOfMeetingController::class, 'export'])
    ->name('minuteofmeeting.export');

Route::get('/minuteofmeeting/{id}/view', [MinuteOfMeetingController::class, 'view'])
    ->name('minuteofmeeting.view');

Route::get('minuteofmeeting/{id}/export-pdf', [MinuteOfMeetingController::class, 'exportPdf'])->name('minuteofmeeting.export.pdf');

Route::get('minuteofmeeting/{id}/export-pdf', [MinuteOfMeetingController::class, 'viewPdf'])->name('minuteofmeeting.export.pdf');

Route::post('/bookingmeeting/export-pdf', [BookingMeetingController::class, 'exportSelectedPdf'])
    ->name('bookingmeeting.exportPdf');




    
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
