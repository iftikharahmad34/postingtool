<?php

use App\Livewire\Accounts;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Setup;
use App\Livewire\Compose;
use App\Livewire\Dashboard;
use App\Livewire\History;
use App\Livewire\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/setup', Setup::class)->name('setup');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/compose', Compose::class)->name('compose');
    Route::get('/posts', History::class)->name('history');
    Route::get('/accounts', Accounts::class)->name('accounts');
    Route::get('/settings', Profile::class)->name('profile');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
