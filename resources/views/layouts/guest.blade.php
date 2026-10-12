<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Brand panel --}}
        <div class="relative hidden overflow-hidden bg-ink-950 p-12 text-white lg:flex lg:flex-col">
            <div class="absolute -top-32 -left-32 size-[28rem] rounded-full bg-brand-600/40 blur-3xl"></div>
            <div class="absolute -right-24 -bottom-40 size-[30rem] rounded-full bg-pink-500/30 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,.07)_1px,transparent_1px)] [background-size:22px_22px]"></div>

            <div class="relative flex items-center gap-2.5">
                <span class="grid size-10 place-items-center rounded-xl bg-linear-to-br from-brand-500 to-pink-500 shadow-glow"><x-icon name="send" class="size-5 -rotate-12" /></span>
                <span class="text-lg font-extrabold tracking-tight">PostPilot</span>
            </div>

            <div class="relative my-auto max-w-md">
                <h2 class="text-4xl leading-tight font-extrabold tracking-tight">Write once.<br><span class="bg-linear-to-r from-brand-300 to-pink-300 bg-clip-text text-transparent">Publish everywhere.</span></h2>
                <p class="mt-4 text-lg text-slate-300">Share one post to your Facebook Page, Instagram, X and LinkedIn with a single click.</p>

                <div class="mt-10 space-y-3">
                    @foreach (\App\Enums\Platform::cases() as $i => $p)
                        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur" style="margin-left: {{ $i * 18 }}px">
                            <x-platform-icon :platform="$p" tile class="size-9" />
                            <div class="flex-1">
                                <p class="text-sm font-semibold">{{ $p->label() }}</p>
                                <div class="mt-1.5 h-1.5 rounded-full bg-white/15" style="width: {{ [8, 6, 7, 5][$i] }}rem"></div>
                            </div>
                            <span class="grid size-6 place-items-center rounded-full bg-emerald-500"><x-icon name="check" class="size-3.5" /></span>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="relative text-sm text-slate-500">© {{ date('Y') }} PostPilot</p>
        </div>

        {{-- Form --}}
        <div class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
        </div>
    </div>
    <x-toasts />
</body>
</html>
