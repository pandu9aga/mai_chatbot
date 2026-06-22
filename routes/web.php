<?php

use App\Livewire\Chat\ChatDashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/chat', ChatDashboard::class)->name('chat.dashboard');
    Route::get('/dashboard', ChatDashboard::class)->name('dashboard');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
