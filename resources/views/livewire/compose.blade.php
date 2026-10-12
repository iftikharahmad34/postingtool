@php
    $acc = $this->accounts;
    $names = [
        'facebook' => $acc['facebook']->credentials['page_name'] ?? null ?: 'Your Page',
        'instagram' => ltrim($acc['instagram']->credentials['username'] ?? '', '@') ?: 'yourbrand',
        'twitter' => $acc->get('twitter')?->account_name ?: '@yourhandle',
        'linkedin' => $acc['linkedin']->credentials['author_name'] ?? null ?: 'Your Name',
    ];
    $initial = fn ($s) => mb_strtoupper(mb_substr(ltrim($s, '@'), 0, 1));
@endphp

<div x-data="publisher" @publish-start.window="run($event.detail.ids)">
@if ($this->post)
    {{-- ============ Publishing progress ============ --}}
    @php($post = $this->post)
    <div class="mx-auto max-w-3xl">
        <div class="card overflow-hidden">
            <div @class([
                'relative overflow-hidden px-8 py-10 text-center text-white',
                'bg-linear-to-br from-emerald-500 to-teal-600' => $post->status === 'published',
                'bg-linear-to-br from-amber-500 to-orange-600' => $post->status === 'partial',
                'bg-linear-to-br from-rose-500 to-pink-600' => $post->status === 'failed',
                'bg-linear-to-br from-brand-600 to-pink-500' => $post->status === 'publishing',
            ])>
                <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,.12)_1px,transparent_1px)] [background-size:18px_18px]"></div>
                <div class="relative">
                    <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-white/20 backdrop-blur">
                        @if ($post->status === 'publishing')
                            <svg class="size-8 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        @elseif ($post->status === 'published')
                            <x-icon name="check" class="size-8" />
                        @else
                            <x-icon name="warning" class="size-8" />
                        @endif
                    </span>
                    <h2 class="mt-4 text-2xl font-extrabold tracking-tight">
                        {{ match ($post->status) { 'published' => 'Your post is live!', 'partial' => 'Almost there', 'failed' => 'Publishing failed', default => 'Publishing your post…' } }}
                    </h2>
                    <p class="mt-1 text-white/85">
                        {{ $post->targets->where('status', 'published')->count() }} of {{ $post->targets->count() }} channels published
                    </p>
                </div>
            </div>

            <ul class="divide-y divide-slate-100 dark:divide-ink-800">
                @foreach ($post->targets as $t)
                    <li class="flex items-start gap-4 px-6 py-5" wire:key="t-{{ $t->id }}">
                        <x-platform-icon :platform="$t->platform" tile class="size-11" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $t->platform->label() }}</p>
                            @if ($t->status === 'published')
                                <p class="text-sm text-emerald-600 dark:text-emerald-400">Published {{ $t->note ? '· '.$t->note : '' }}</p>
                            @elseif ($t->status === 'failed')
                                <p class="mt-0.5 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $t->error }}</p>
                            @else
                                <p class="text-sm text-slate-500" x-text="current === {{ $t->id }} ? 'Publishing…' : 'Waiting…'"></p>
                            @endif
                        </div>
                        <div class="shrink-0 pt-1">
                            @if ($t->status === 'published')
                                <a href="{{ $t->url }}" target="_blank" class="btn btn-secondary px-3 py-2 text-xs">View <x-icon name="external" class="size-3.5" /></a>
                            @elseif ($t->status === 'failed')
                                <button type="button" @click="retry({{ $t->id }})" :disabled="current" class="btn btn-secondary px-3 py-2 text-xs">
                                    <x-icon name="retry" class="size-3.5" x-bind:class="current === {{ $t->id }} && 'animate-spin'" /> Retry
                                </button>
                            @else
                                <svg x-show="current === {{ $t->id }}" class="size-6 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".2" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                <span x-show="current !== {{ $t->id }}" class="block size-6 rounded-full border-2 border-dashed border-slate-300 dark:border-ink-700"></span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($post->status !== 'publishing')
                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-5 sm:flex-row sm:justify-end dark:border-ink-800 dark:bg-ink-850/50">
                    <a href="{{ route('history') }}" wire:navigate class="btn btn-secondary">View all posts</a>
                    <button type="button" wire:click="startOver" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Create another post</button>
                </div>
            @endif
        </div>
    </div>
@else
    {{-- ============ Composer ============ --}}
    <form wire:submit="publish" x-data="composer" class="grid grid-cols-1 items-start gap-8 xl:grid-cols-[minmax(0,1fr)_440px]">
        <div class="min-w-0 space-y-6">
            {{-- Channels --}}
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Publish to</h2>
                        <p class="text-sm text-slate-500">Pick the channels for this post.</p>
                    </div>
                    <a href="{{ route('accounts') }}" wire:navigate class="text-sm font-semibold text-brand-600 dark:text-brand-400">Manage</a>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                    @foreach ($platforms as $p)
                        @php($ready = (bool) $acc->get($p->value)?->isReady())
                        @if ($ready)
                            <label class="group relative cursor-pointer">
                                <input type="checkbox" wire:model="selected" value="{{ $p->value }}" class="peer sr-only">
                                <div class="flex flex-col items-center gap-2.5 rounded-2xl border-2 border-slate-200 p-4 text-center transition peer-checked:border-brand-500 peer-checked:bg-brand-50/60 hover:border-slate-300 dark:border-ink-700 dark:peer-checked:border-brand-500 dark:peer-checked:bg-brand-500/10">
                                    <x-platform-icon :platform="$p" tile class="size-11" />
                                    <div class="min-w-0 w-full">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $p->label() }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $acc[$p->value]->account_name ?: 'Connected' }}</p>
                                    </div>
                                </div>
                                <span class="absolute top-2.5 right-2.5 grid size-5 place-items-center rounded-full border-2 border-slate-300 bg-white text-white transition peer-checked:border-brand-600 peer-checked:bg-brand-600 dark:border-ink-700 dark:bg-ink-850">
                                    <x-icon name="check" class="size-3" stroke-width="3" />
                                </span>
                            </label>
                        @else
                            <a href="{{ route('accounts') }}" wire:navigate class="flex flex-col items-center gap-2.5 rounded-2xl border-2 border-dashed border-slate-200 p-4 text-center transition hover:border-brand-300 hover:bg-brand-50/40 dark:border-ink-700 dark:hover:bg-brand-500/5">
                                <x-platform-icon :platform="$p" tile class="size-11 opacity-35 grayscale" />
                                <div>
                                    <p class="text-sm font-semibold text-slate-500">{{ $p->label() }}</p>
                                    <p class="text-xs font-semibold text-brand-600 dark:text-brand-400">+ Connect</p>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
                @error('selected') <p class="mt-3 text-sm text-rose-600">{{ $message }}</p> @enderror
            </section>

            {{-- Editor --}}
            <section class="card overflow-hidden">
                <div class="p-6 pb-3">
                    <h2 class="font-bold text-slate-900 dark:text-white">Your post</h2>
                </div>
                <div class="px-6">
                    <textarea wire:model="body" rows="8" dir="auto" placeholder="What would you like to share? Use #hashtags and links freely…"
                              class="w-full resize-none border-0 bg-transparent p-0 text-[15px] leading-relaxed text-slate-800 placeholder:text-slate-400 focus:ring-0 dark:text-slate-100"></textarea>
                    @error('body') <p class="mb-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Picture --}}
                <div class="px-6 pb-5"
                     x-on:livewire-upload-start="progress = 1" x-on:livewire-upload-finish="progress = 0"
                     x-on:livewire-upload-error="progress = 0" x-on:livewire-upload-progress="progress = $event.detail.progress">
                    @if ($image && ! $errors->has('image'))
                        <div class="group relative inline-block">
                            <img src="{{ $image->temporaryUrl() }}" class="max-h-72 rounded-2xl border border-slate-200 object-cover dark:border-ink-700" alt="">
                            <button type="button" wire:click="removeImage" class="absolute top-2 right-2 grid size-8 place-items-center rounded-full bg-slate-900/70 text-white backdrop-blur transition hover:bg-slate-900">
                                <x-icon name="x" class="size-4" />
                            </button>
                        </div>
                    @else
                        <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
                               :class="dragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-slate-200 dark:border-ink-700'"
                               class="flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-dashed p-5 transition hover:border-brand-400">
                            <input type="file" wire:model="image" accept="image/jpeg,image/png" class="sr-only">
                            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300"><x-icon name="photo" class="size-6" /></span>
                            <span class="flex-1">
                                <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Add a picture</span>
                                <span class="block text-xs text-slate-500">Drag & drop or click · JPG / PNG up to 8 MB · required for Instagram</span>
                            </span>
                        </label>
                        <div x-show="progress" x-cloak class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-ink-800">
                            <div class="h-full rounded-full bg-brand-500 transition-all" :style="`width: ${progress}%`"></div>
                        </div>
                    @endif
                    @error('image') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Counters --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-100 bg-slate-50/60 px-6 py-3 dark:border-ink-800 dark:bg-ink-850/40">
                    @foreach ($platforms as $p)
                        <div x-show="$wire.selected.includes('{{ $p->value }}')" class="flex items-center gap-2 text-xs font-medium"
                             :class="over('{{ $p->value }}') ? 'text-rose-600' : 'text-slate-500'">
                            <svg class="size-5 -rotate-90" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-opacity=".15" stroke-width="2.5"/>
                                <circle cx="10" cy="10" r="8" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="50.27"
                                        :stroke="over('{{ $p->value }}') ? '#e11d48' : '{{ $p->color() === '#0F1419' ? '#64748b' : $p->color() }}'"
                                        :stroke-dashoffset="50.27 - 50.27 * ring('{{ $p->value }}') / 100"/>
                            </svg>
                            <span>{{ $p === \App\Enums\Platform::Twitter ? 'X' : $p->label() }} <span x-text="length('{{ $p->value }}')"></span>/<span x-text="limit('{{ $p->value }}').toLocaleString()"></span></span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Per-channel text --}}
            <section class="card" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="flex w-full items-center gap-3 p-6 text-left">
                    <span class="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15"><x-icon name="sparkles" /></span>
                    <span class="flex-1">
                        <span class="block font-bold text-slate-900 dark:text-white">Customize per channel</span>
                        <span class="block text-sm text-slate-500">Optional — e.g. a shorter version for X.</span>
                    </span>
                    <svg class="size-5 text-slate-400 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open" x-collapse x-cloak class="space-y-4 px-6 pb-6">
                    @foreach ($platforms as $p)
                        <div x-show="$wire.selected.includes('{{ $p->value }}')">
                            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <x-platform-icon :platform="$p" tile class="size-6 rounded-md" /> {{ $p->label() }}
                            </label>
                            <textarea wire:model="custom.{{ $p->value }}" rows="3" dir="auto" class="input" placeholder="Leave empty to use the main text"></textarea>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Publish bar --}}
            <div class="sticky bottom-4 z-20">
                <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white/90 p-3 pl-5 shadow-xl backdrop-blur-xl dark:border-ink-700 dark:bg-ink-900/90">
                    <div class="flex -space-x-2">
                        @foreach ($platforms as $p)
                            <x-platform-icon :platform="$p" tile class="size-8 rounded-lg ring-2 ring-white dark:ring-ink-900" x-show="$wire.selected.includes('{{ $p->value }}')" />
                        @endforeach
                    </div>
                    <p class="flex-1 text-sm text-slate-500">
                        <span x-show="$wire.selected.length">Ready for <b class="text-slate-800 dark:text-white" x-text="$wire.selected.length"></b> <span x-text="$wire.selected.length === 1 ? 'channel' : 'channels'"></span></span>
                        <span x-show="!$wire.selected.length">No channel selected</span>
                    </p>
                    <button type="submit" class="btn btn-primary px-6 py-3" wire:loading.attr="disabled" wire:target="publish,image">
                        <span wire:loading.remove wire:target="publish"><x-icon name="send" class="size-[18px]" /></span>
                        <svg wire:loading wire:target="publish" class="size-[18px] animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Publish now
                    </button>
                </div>
            </div>
        </div>

        {{-- ============ Live preview ============ --}}
        <aside class="min-w-0 xl:sticky xl:top-24">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-bold text-slate-900 dark:text-white"><x-icon name="eye" class="size-[18px] text-brand-500" /> Live preview</h2>
                    <div class="flex gap-1 rounded-xl bg-slate-100 p-1 dark:bg-ink-800">
                        @foreach ($platforms as $p)
                            <button type="button" @click="tab = '{{ $p->value }}'" title="{{ $p->label() }}"
                                    :class="tab === '{{ $p->value }}' ? 'bg-white shadow-sm dark:bg-ink-700' : 'opacity-50 hover:opacity-100'"
                                    class="grid size-8 place-items-center rounded-lg transition" style="color: {{ $p === \App\Enums\Platform::Twitter ? 'currentColor' : $p->color() }}">
                                <x-platform-icon :platform="$p" class="size-4" />
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 max-h-[calc(100vh-14rem)] overflow-y-auto rounded-2xl bg-slate-100 p-4 dark:bg-ink-950">
                    {{-- Facebook --}}
                    <div x-show="tab === 'facebook'" class="overflow-hidden rounded-xl bg-white text-[#050505] shadow-sm">
                        <div class="flex items-center gap-2.5 p-3">
                            <span class="grid size-10 place-items-center rounded-full bg-[#1877F2] font-bold text-white">{{ $initial($names['facebook']) }}</span>
                            <div class="leading-tight">
                                <p class="text-[15px] font-semibold">{{ $names['facebook'] }}</p>
                                <p class="flex items-center gap-1 text-xs text-[#65676B]">Just now · <x-icon name="globe" class="size-3" /></p>
                            </div>
                        </div>
                        <p class="px-3 pb-3 text-[15px] leading-snug whitespace-pre-wrap break-words" dir="auto" x-html="html('facebook', '#1877F2')" x-show="text('facebook')"></p>
                        @if ($image && ! $errors->has('image')) <img src="{{ $image->temporaryUrl() }}" class="w-full" alt=""> @endif
                        <div class="mx-3 flex justify-around border-t border-[#ced0d4] py-1.5 text-[13px] font-semibold text-[#65676B]">
                            <span class="flex items-center gap-1.5 py-1"><x-icon name="thumb" class="size-[18px]" /> Like</span>
                            <span class="flex items-center gap-1.5 py-1"><x-icon name="chat" class="size-[18px]" /> Comment</span>
                            <span class="flex items-center gap-1.5 py-1"><x-icon name="share" class="size-[18px]" /> Share</span>
                        </div>
                    </div>

                    {{-- Instagram --}}
                    <div x-show="tab === 'instagram'" x-cloak class="overflow-hidden rounded-xl bg-white text-[#000] shadow-sm">
                        <div class="flex items-center gap-2.5 p-3">
                            <span class="rounded-full p-[2px]" style="background: {{ \App\Enums\Platform::Instagram->background() }}">
                                <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-slate-800 text-xs font-bold text-white">{{ $initial($names['instagram']) }}</span>
                            </span>
                            <p class="flex-1 text-sm font-semibold">{{ $names['instagram'] }}</p>
                            <span class="text-lg leading-none font-bold">···</span>
                        </div>
                        @if ($image && ! $errors->has('image'))
                            <img src="{{ $image->temporaryUrl() }}" class="aspect-square w-full object-cover" alt="">
                        @else
                            <div class="grid aspect-square place-items-center bg-linear-to-br from-slate-100 to-slate-200 text-center text-slate-400">
                                <div><x-icon name="photo" class="mx-auto size-10" /><p class="mt-2 text-sm font-medium">Instagram needs a picture</p></div>
                            </div>
                        @endif
                        <div class="flex items-center gap-4 px-3 pt-3">
                            <x-icon name="heart" class="size-6" /><x-icon name="chat" class="size-6" /><x-icon name="send" class="size-6 -rotate-12" />
                            <x-icon name="bookmark" class="ml-auto size-6" />
                        </div>
                        <p class="line-clamp-4 px-3 pt-2 pb-3 text-sm leading-snug whitespace-pre-wrap break-words" dir="auto">
                            <b>{{ $names['instagram'] }}</b> <span x-html="html('instagram', '#00376b')"></span>
                        </p>
                    </div>

                    {{-- X --}}
                    <div x-show="tab === 'twitter'" x-cloak class="rounded-xl bg-white p-3 text-[#0f1419] shadow-sm">
                        <div class="flex gap-2.5">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-[#0f1419] font-bold text-white">{{ $initial($names['twitter']) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px]"><b>{{ ltrim($names['twitter'], '@') }}</b> <span class="text-[#536471]">{{ '@'.ltrim($names['twitter'], '@') }} · now</span></p>
                                <p class="mt-0.5 text-[15px] leading-snug whitespace-pre-wrap break-words" dir="auto" x-html="html('twitter', '#1d9bf0')"></p>
                                @if ($image && ! $errors->has('image')) <img src="{{ $image->temporaryUrl() }}" class="mt-3 w-full rounded-2xl border border-[#cfd9de]" alt=""> @endif
                                <div class="mt-3 flex justify-between pr-6 text-[#536471]">
                                    <x-icon name="chat" class="size-[18px]" /><x-icon name="repeat" class="size-[18px]" /><x-icon name="heart" class="size-[18px]" /><x-icon name="chart" class="size-[18px]" />
                                </div>
                            </div>
                        </div>
                        <p x-show="over('twitter')" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">Longer than 280 — it will be shortened automatically. Add a custom X text to control it.</p>
                    </div>

                    {{-- LinkedIn --}}
                    <div x-show="tab === 'linkedin'" x-cloak class="overflow-hidden rounded-xl bg-white text-[rgba(0,0,0,.9)] shadow-sm">
                        <div class="flex items-center gap-2.5 p-3">
                            <span class="grid size-12 place-items-center rounded-full bg-[#0A66C2] text-lg font-bold text-white">{{ $initial($names['linkedin']) }}</span>
                            <div class="leading-tight">
                                <p class="text-sm font-semibold">{{ $names['linkedin'] }}</p>
                                <p class="text-xs text-[rgba(0,0,0,.6)]">Posting with PostPilot</p>
                                <p class="flex items-center gap-1 text-xs text-[rgba(0,0,0,.6)]">now · <x-icon name="globe" class="size-3" /></p>
                            </div>
                        </div>
                        <p class="line-clamp-5 px-3 pb-3 text-sm leading-snug whitespace-pre-wrap break-words" dir="auto" x-html="html('linkedin', '#0a66c2')" x-show="text('linkedin')"></p>
                        @if ($image && ! $errors->has('image')) <img src="{{ $image->temporaryUrl() }}" class="w-full" alt=""> @endif
                        <div class="mx-3 flex justify-around border-t border-[#e8e8e8] py-1.5 text-[13px] font-semibold text-[rgba(0,0,0,.6)]">
                            <span class="flex items-center gap-1 py-1"><x-icon name="thumb" class="size-[18px]" /> Like</span>
                            <span class="flex items-center gap-1 py-1"><x-icon name="chat" class="size-[18px]" /> Comment</span>
                            <span class="flex items-center gap-1 py-1"><x-icon name="repeat" class="size-[18px]" /> Repost</span>
                            <span class="flex items-center gap-1 py-1"><x-icon name="send" class="size-[18px]" /> Send</span>
                        </div>
                    </div>

                    <p x-show="!$wire.body && !$wire.image" class="pt-4 text-center text-xs text-slate-400">Start typing to see your post come to life ✨</p>
                </div>
            </div>
        </aside>
    </form>
@endif
</div>
