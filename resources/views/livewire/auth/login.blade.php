<div>
    <div class="mb-8 lg:hidden"><x-logo /></div>
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Welcome back 👋</h1>
    <p class="mt-2 text-slate-500">Sign in to publish your next post.</p>

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <label class="label" for="email">Email</label>
            <input wire:model="email" id="email" type="email" class="input" autocomplete="email" autofocus>
            @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div x-data="{ show: false }">
            <label class="label" for="password">Password</label>
            <div class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" class="input pr-11" autocomplete="current-password">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600"><x-icon name="eye" class="size-[18px]" /></button>
            </div>
            @error('password') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input wire:model="remember" type="checkbox" class="size-4 rounded accent-brand-600"> Keep me signed in
        </label>
        <button class="btn btn-primary w-full py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>
</div>
