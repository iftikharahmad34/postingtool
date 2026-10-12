<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="min-h-screen">
    <div class="mx-auto max-w-3xl p-6 sm:p-12">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-xl bg-linear-to-br from-brand-500 via-brand-600 to-pink-500 text-white shadow-glow">
                <x-icon name="send" class="size-[18px] -rotate-12" />
            </span>
            <span class="leading-tight">
                <span class="block text-[15px] font-extrabold tracking-tight text-slate-900 dark:text-white">{{ config('app.name') }}</span>
                <span class="block text-[11px] font-medium text-slate-400">Publish everywhere</span>
            </span>
        </a>

        <div class="card mt-8 p-6 sm:p-12">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-slate-500">Last updated {{ $updated }}</p>

            <div class="mt-8 space-y-6 text-[15px] leading-relaxed text-slate-700 dark:text-slate-200">
                @yield('content')
            </div>
        </div>

        <p class="mt-6 text-sm text-slate-500">
            <a href="{{ route('privacy') }}" class="font-semibold text-brand-600 dark:text-brand-300">Privacy Policy</a> ·
            <a href="{{ route('terms') }}" class="font-semibold text-brand-600 dark:text-brand-300">Terms of Service</a> ·
            © {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
