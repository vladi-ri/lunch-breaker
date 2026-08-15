<?php

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\RestaurantController as AdminRestaurantController;
use App\Http\Controllers\OfficeSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RsvpController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [RestaurantController::class, 'index'])->name('dashboard');

    Route::get('/settings/office', [OfficeSettingsController::class, 'show'])->name('office.edit');
    Route::put('/settings/office', [OfficeSettingsController::class, 'update'])->name('office.update');

    Route::post('/rsvps', [RsvpController::class, 'store'])->name('rsvps.store');
    Route::delete('/rsvps/{restaurant}', [RsvpController::class, 'destroy'])->name('rsvps.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('menus', MenuController::class)->except('show');
        Route::resource('restaurants', AdminRestaurantController::class)->except('show');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
