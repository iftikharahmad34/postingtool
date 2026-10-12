<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Sign in')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = true;

    public function mount()
    {
        if (User::count() === 0) {
            return $this->redirectRoute('setup');
        }
    }

    public function login()
    {
        $this->validate();

        $key = Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['email' => 'These details do not match our records.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
