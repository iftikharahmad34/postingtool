<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** First-run screen: creates the single owner account. */
#[Layout('layouts.guest')]
#[Title('Welcome')]
class Setup extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount()
    {
        if (User::count() > 0) {
            return $this->redirectRoute('login');
        }
    }

    public function create()
    {
        abort_if(User::count() > 0, 403);

        $data = $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        Auth::login($user, true);
        session()->regenerate();
        session()->flash('toast', ['type' => 'success', 'message' => 'Welcome! Connect your first account to get started.']);

        return $this->redirectRoute('accounts');
    }

    public function render()
    {
        return view('livewire.auth.setup');
    }
}
