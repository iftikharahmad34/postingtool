<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen" x-data="{ sidebar: false }">
    @php
        $nav = [
            ['dashboard', 'Dashboard', 'home'],
            ['compose', 'Create post', 'pencil'],
            ['history', 'Posts', 'clock'],
            ['accounts', 'Accounts', 'link'],
            ['profile', 'Settings', 'cog'],
        ];
        $connected = \App\Models\SocialAccount::all()->filter->isReady()->map(fn ($a) => $a->platform);
    @endphp

    {{-- Mobile backdrop --}}
    <div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200/80 bg-white px-4 py-5 transition-transform duration-300 dark:border-ink-800 dark:bg-ink-900">
        <div class="flex items-center justify-between px-2">
            <x-logo />
            <button class="btn-ghost btn p-2 lg:hidden" @click="sidebar = false"><x-icon name="x" /></button>
        </div>

        <a href="{{ route('compose') }}" wire:navigate class="btn btn-primary mt-7 w-full py-3">
            <x-icon name="plus" class="size-4" /> New post
        </a>

        <nav class="mt-6 space-y-1">
            <p class="px-3 pb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">Menu</p>
            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate @class(['nav-link', 'active' => request()->routeIs($route)])>
                    <x-icon :name="$icon" /> {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="mt-8 px-3">
            <p class="pb-3 text-[11px] font-bold tracking-wider text-slate-400 uppercase">Channels</p>
            <div class="flex gap-2">
                @foreach (\App\Enums\Platform::cases() as $p)
                    <a href="{{ route('accounts') }}" wire:navigate title="{{ $p->label() }} — {{ $connected->contains($p) ? 'connected' : 'not connected' }}" class="relative">
                        <x-platform-icon :platform="$p" tile @class(['size-9 transition', 'opacity-30 grayscale' => ! $connected->contains($p)]) />
                        @if ($connected->contains($p))
                            <span class="absolute -right-0.5 -bottom-0.5 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-ink-900"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-auto">
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-brand-600 to-pink-500 p-4 text-white">
                <div class="absolute -top-6 -right-6 size-24 rounded-full bg-white/10"></div>
                <x-icon name="sparkles" class="size-6" />
                <p class="mt-2 text-sm font-bold">One post, four networks</p>
                <p class="mt-0.5 text-xs text-white/80">{{ $connected->count() }} of 4 channels connected</p>
                <div class="mt-3 h-1.5 rounded-full bg-white/25"><div class="h-full rounded-full bg-white" style="width: {{ $connected->count() * 25 }}%"></div></div>
            </div>

            <div class="mt-4 flex items-center gap-3 rounded-xl px-2 py-2">
                <span class="grid size-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                    {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-ghost btn p-2" title="Log out"><x-icon name="logout" class="size-[18px]" /></button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/75 backdrop-blur-xl dark:border-ink-800 dark:bg-ink-950/75">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-8">
                <button class="btn-ghost btn -ml-2 p-2 lg:hidden" @click="sidebar = true"><x-icon name="menu" /></button>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ $title ?? '' }}</h1>
                </div>
                <button x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                        class="btn-secondary btn p-2.5" title="Toggle theme">
                    <x-icon name="moon" class="size-[18px]" x-show="!dark" />
                    <x-icon name="sun" class="size-[18px]" x-show="dark" x-cloak />
                </button>
                @unless (request()->routeIs('compose'))
                    <a href="{{ route('compose') }}" wire:navigate class="btn btn-primary hidden sm:inline-flex"><x-icon name="plus" class="size-4" /> New post</a>
                @endunless
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-8">
            {{ $slot }}
        </main>
    </div>

    <x-toasts />
</body>
</html>
