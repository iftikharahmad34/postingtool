<div class="mx-auto max-w-3xl space-y-6">
    <form wire:submit="saveProfile" class="card">
        <div class="flex items-center gap-4 border-b border-slate-100 p-6 dark:border-ink-800">
            <span class="grid size-14 place-items-center rounded-2xl bg-linear-to-br from-brand-500 to-pink-500 text-xl font-extrabold text-white shadow-glow">
                {{ str($name)->substr(0, 1)->upper() }}
            </span>
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Profile</h2>
                <p class="text-sm text-slate-500">Your sign-in details.</p>
            </div>
        </div>
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div>
                <label class="label">Name</label>
                <input wire:model="name" class="input">
                @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Email</label>
                <input wire:model="email" type="email" class="input">
                @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4 dark:border-ink-800">
            <button class="btn btn-primary">Save profile</button>
        </div>
    </form>

    <form wire:submit="savePassword" class="card">
        <div class="flex items-center gap-4 border-b border-slate-100 p-6 dark:border-ink-800">
            <span class="grid size-14 place-items-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-ink-800 dark:text-slate-300"><x-icon name="shield" class="size-7" /></span>
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Password</h2>
                <p class="text-sm text-slate-500">Use at least 8 characters.</p>
            </div>
        </div>
        <div class="grid gap-5 p-6 sm:grid-cols-3">
            @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                <div>
                    <label class="label">{{ $label }}</label>
                    <input wire:model="{{ $field }}" type="password" class="input" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}">
                    @error($field) <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4 dark:border-ink-800">
            <button class="btn btn-primary">Change password</button>
        </div>
    </form>
</div>
