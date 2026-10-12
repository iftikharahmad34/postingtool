<div>
    <div class="mb-8 lg:hidden"><x-logo /></div>
    <span class="chip bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300"><x-icon name="sparkles" class="size-3.5" /> First-time setup</span>
    <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Create your account</h1>
    <p class="mt-2 text-slate-500">This account protects your connected social profiles.</p>

    <form wire:submit="create" class="mt-8 space-y-5">
        @foreach ([['name', 'Your name', 'text', 'name'], ['email', 'Email', 'email', 'email'], ['password', 'Password', 'password', 'new-password'], ['password_confirmation', 'Confirm password', 'password', 'new-password']] as [$field, $label, $type, $ac])
            <div>
                <label class="label" for="{{ $field }}">{{ $label }}</label>
                <input wire:model="{{ $field }}" id="{{ $field }}" type="{{ $type }}" class="input" autocomplete="{{ $ac }}">
                @error($field) <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <button class="btn btn-primary w-full py-3" wire:loading.attr="disabled">Create account & continue</button>
    </form>
</div>
