<div class="space-y-8">
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl bg-ink-950 p-8 text-white sm:p-10">
        <div class="absolute -top-24 -right-10 size-80 rounded-full bg-brand-600/50 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 size-80 rounded-full bg-pink-500/30 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,.06)_1px,transparent_1px)] [background-size:20px_20px]"></div>
        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-medium text-brand-200">{{ now()->format('l, j F') }}</p>
                <h2 class="mt-1 text-3xl font-extrabold tracking-tight">
                    {{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ str(auth()->user()->name)->before(' ') }}!
                </h2>
                <p class="mt-2 max-w-lg text-slate-300">What would you like to share today? Write it once — we'll publish it to every channel you pick.</p>
            </div>
            <a href="{{ route('compose') }}" wire:navigate class="btn shrink-0 bg-white px-6 py-3.5 text-slate-900 shadow-xl hover:bg-slate-100">
                <x-icon name="pencil" class="size-[18px]" /> Create a post
            </a>
        </div>
    </section>

    {{-- Stats --}}
    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as [$label, $value, $icon, $gradient])
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                    <span class="grid size-10 place-items-center rounded-xl bg-linear-to-br {{ $gradient }} text-white shadow-md">
                        <x-icon :name="$icon" class="size-5" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-8 xl:grid-cols-3">
        {{-- Activity --}}
        <section class="card p-6 xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">Activity</h3>
                    <p class="text-sm text-slate-500">Posts created in the last 14 days</p>
                </div>
                <span class="chip bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ $activity->sum('count') }} {{ str('post')->plural($activity->sum('count')) }}</span>
            </div>
            <div class="mt-8 flex h-48 items-end gap-2 sm:gap-3">
                @foreach ($activity as $day)
                    <div class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-xs font-semibold text-slate-700 opacity-0 transition group-hover:opacity-100 dark:text-slate-200">{{ $day['count'] }}</span>
                        <div class="w-full rounded-lg bg-linear-to-t from-brand-600 to-brand-400 transition group-hover:from-pink-500 group-hover:to-brand-400 {{ $day['count'] ? '' : 'opacity-15' }}"
                             style="height: {{ max(4, $day['count'] / $maxActivity * 100) }}%" title="{{ $day['label'] }}: {{ $day['count'] }}"></div>
                        <span class="text-[11px] text-slate-400">{{ $day['short'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Channels --}}
        <section class="card p-6">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-900 dark:text-white">Channels</h3>
                <a href="{{ route('accounts') }}" wire:navigate class="text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Manage</a>
            </div>
            <ul class="mt-5 space-y-3">
                @foreach ($platforms as $p)
                    @php($acc = $accounts[$p->value] ?? null)
                    <li class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3 dark:border-ink-800">
                        <x-platform-icon :platform="$p" tile />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $p->label() }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $acc?->isReady() ? ($acc->account_name ?: 'Connected') : 'Not connected' }}</p>
                        </div>
                        @if ($acc?->isReady())
                            <span class="text-right">
                                <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ $perPlatform[$p->value] ?? 0 }}</span>
                                <span class="block text-[11px] text-slate-400">posts</span>
                            </span>
                        @else
                            <a href="{{ route('accounts') }}" wire:navigate class="btn btn-secondary px-3 py-1.5 text-xs">Connect</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Recent --}}
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-ink-800">
            <h3 class="font-bold text-slate-900 dark:text-white">Recent posts</h3>
            <a href="{{ route('history') }}" wire:navigate class="text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">View all</a>
        </div>
        @forelse ($recent as $post)
            <div class="flex items-center gap-4 border-b border-slate-100 px-6 py-4 last:border-0 dark:border-ink-800">
                @if ($post->imageUrl())
                    <img src="{{ $post->imageUrl() }}" class="size-12 shrink-0 rounded-xl object-cover" alt="">
                @else
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-400 dark:bg-ink-800"><x-icon name="pencil" /></span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100" dir="auto">{{ $post->body ?: 'Picture post' }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ $post->created_at->diffForHumans() }}</p>
                </div>
                <div class="hidden items-center -space-x-1.5 sm:flex">
                    @foreach ($post->targets as $t)
                        <span class="relative">
                            <x-platform-icon :platform="$t->platform" tile class="size-7 rounded-lg ring-2 ring-white dark:ring-ink-900" />
                            @if ($t->status === 'failed')
                                <span class="absolute -top-1 -right-1 size-2.5 rounded-full bg-rose-500 ring-2 ring-white dark:ring-ink-900"></span>
                            @endif
                        </span>
                    @endforeach
                </div>
                <x-status-chip :status="$post->status" />
            </div>
        @empty
            <div class="px-6 py-14 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300"><x-icon name="send" class="size-6" /></span>
                <p class="mt-4 font-semibold text-slate-800 dark:text-slate-100">No posts yet</p>
                <p class="mt-1 text-sm text-slate-500">Your published posts will show up here.</p>
                <a href="{{ route('compose') }}" wire:navigate class="btn btn-primary mt-5">Create your first post</a>
            </div>
        @endforelse
    </section>
</div>
