<div>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex gap-1 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-soft dark:border-ink-800 dark:bg-ink-900">
            @foreach ($filters as $key => [$label, $count])
                <button wire:click="$set('status', '{{ $key }}')"
                        @class(['flex shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition',
                                'bg-brand-600 text-white shadow-md' => $status === $key,
                                'text-slate-500 hover:bg-slate-100 dark:hover:bg-ink-800' => $status !== $key])>
                    {{ $label }}
                    <span @class(['rounded-full px-2 text-xs', 'bg-white/20' => $status === $key, 'bg-slate-100 dark:bg-ink-800' => $status !== $key])>{{ $count }}</span>
                </button>
            @endforeach
        </div>
        <div class="relative lg:w-80">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-[18px] -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.400ms="search" type="search" class="input pl-10" placeholder="Search your posts…">
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($posts as $post)
            <article class="card overflow-hidden" wire:key="post-{{ $post->id }}" x-data="{ open: false }">
                <div class="flex gap-5 p-5">
                    @if ($post->imageUrl())
                        <img src="{{ $post->imageUrl() }}" class="hidden size-28 shrink-0 rounded-2xl object-cover sm:block" alt="">
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <x-status-chip :status="$post->status" />
                            <span class="text-xs text-slate-400">{{ $post->created_at->format('d M Y · h:i A') }} · {{ $post->created_at->diffForHumans() }}</span>
                            <button wire:click="delete({{ $post->id }})" wire:confirm="Remove this post from your history? (It stays on the social networks.)"
                                    class="ml-auto rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10" title="Remove from history">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                        <p class="mt-3 text-[15px] leading-relaxed whitespace-pre-line text-slate-700 dark:text-slate-200" :class="open ? '' : 'line-clamp-3'" dir="auto">{{ $post->body ?: '📷 Picture post' }}</p>
                        @if (mb_strlen((string) $post->body) > 220)
                            <button @click="open = !open" class="mt-1 text-sm font-semibold text-brand-600 dark:text-brand-400" x-text="open ? 'Show less' : 'Show more'"></button>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($post->targets as $t)
                                @if ($t->status === 'published')
                                    <a href="{{ $t->url }}" target="_blank" class="group flex items-center gap-2 rounded-xl border border-slate-200 py-1.5 pr-3 pl-1.5 text-sm font-medium text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-ink-700 dark:text-slate-200 dark:hover:bg-emerald-500/10">
                                        <x-platform-icon :platform="$t->platform" tile class="size-7 rounded-lg" />
                                        {{ $t->platform->label() }}
                                        <x-icon name="external" class="size-3.5 text-slate-400 group-hover:text-emerald-600" />
                                    </a>
                                @else
                                    <div x-data="{ tip: false }" class="relative">
                                        <button @click="tip = !tip" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50/60 py-1.5 pr-3 pl-1.5 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                                            <x-platform-icon :platform="$t->platform" tile class="size-7 rounded-lg" />
                                            {{ $t->platform->label() }} <x-icon name="warning" class="size-4" />
                                        </button>
                                        <div x-show="tip" x-cloak @click.outside="tip = false" class="animate-pop absolute left-0 z-20 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl dark:border-ink-700 dark:bg-ink-850">
                                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $t->error ?: 'Not published yet.' }}</p>
                                            <button wire:click="retry({{ $t->id }})" wire:loading.attr="disabled" wire:target="retry({{ $t->id }})" class="btn btn-primary mt-3 px-3 py-2 text-xs">
                                                <x-icon name="retry" class="size-3.5" wire:loading.class="animate-spin" wire:target="retry({{ $t->id }})" /> Try again
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="card px-6 py-20 text-center">
                <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300"><x-icon name="clock" class="size-7" /></span>
                <p class="mt-4 text-lg font-bold text-slate-800 dark:text-slate-100">{{ $search || $status ? 'Nothing matches' : 'No posts yet' }}</p>
                <p class="mt-1 text-slate-500">{{ $search || $status ? 'Try a different filter.' : 'Everything you publish will be listed here.' }}</p>
                @unless ($search || $status)
                    <a href="{{ route('compose') }}" wire:navigate class="btn btn-primary mt-6">Create your first post</a>
                @endunless
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $posts->links() }}</div>
</div>
