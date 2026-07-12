<?php

use App\Livewire\Employee\FaceEnrollment;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', fn () => view('attendance.index'))->middleware('can:view_attendances')->name('index');
    Route::get('/clock-in', fn () => view('attendance.clock-in'))->middleware('can:view_attendances')->name('clock-in');
    Route::get('/face-registration', FaceEnrollment::class)->middleware('can:view_attendances')->name('face-registration');
});
