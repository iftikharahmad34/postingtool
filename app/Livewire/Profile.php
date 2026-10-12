<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->fill(auth()->user()->only('name', 'email'));
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore(auth()->id())],
        ]);
        auth()->user()->update($data);
        $this->dispatch('toast', type: 'success', message: 'Profile updated.');
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);
        auth()->user()->update(['password' => Hash::make($this->password)]);
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('toast', type: 'success', message: 'Password changed.');
    }

    public function render()
    {
        return view('livewire.profile');
    }
}
