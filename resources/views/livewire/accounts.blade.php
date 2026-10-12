<div>
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Connected accounts</h2>
            <p class="mt-1 text-slate-500">Connect each network once — your keys are stored encrypted.</p>
        </div>
        <span class="chip bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
            <x-icon name="shield" class="size-3.5" /> Encrypted at rest
        </span>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($platforms as $p)
            @php($a = $this->accounts->get($p->value))
            @php($ready = $a?->isReady())
            <div class="card group overflow-hidden" wire:key="acc-{{ $p->value }}">
                <div class="relative h-24 overflow-hidden" style="background: {{ $p->background() }}">
                    <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,.18)_1px,transparent_1px)] [background-size:16px_16px]"></div>
                    <x-platform-icon :platform="$p" class="absolute -right-4 -bottom-6 size-32 text-white/15" />
                </div>
                <div class="relative px-6 pb-6">
                    <div class="-mt-8 flex items-end justify-between">
                        <span class="grid size-16 place-items-center rounded-2xl border-4 border-white text-white shadow-lg dark:border-ink-900" style="background: {{ $p->background() }}">
                            <x-platform-icon :platform="$p" class="size-7" />
                        </span>
                        @if ($a)
                            <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-500" title="Include in new posts">
                                {{ $a->enabled ? 'Active' : 'Paused' }}
                                <button type="button" wire:click="toggle('{{ $p->value }}')" role="switch" aria-checked="{{ $a->enabled ? 'true' : 'false' }}"
                                        @class(['relative h-6 w-11 rounded-full transition', 'bg-brand-600' => $a->enabled, 'bg-slate-300 dark:bg-ink-700' => ! $a->enabled])>
                                    <span @class(['absolute top-0.5 size-5 rounded-full bg-white shadow transition-all', 'left-[22px]' => $a->enabled, 'left-0.5' => ! $a->enabled])></span>
                                </button>
                            </label>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $p->label() }}</h3>
                        @if ($ready && $a->verified_at)
                            <span class="chip bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"><x-icon name="check" class="size-3" stroke-width="3" /> Connected</span>
                        @elseif ($ready)
                            <span class="chip bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"><x-icon name="warning" class="size-3" /> Not verified</span>
                        @else
                            <span class="chip bg-slate-100 text-slate-500 dark:bg-ink-800">Not connected</span>
                        @endif
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500">
                        @if ($ready)
                            {{ $a->account_name ?: 'Account saved' }} @if ($a->verified_at) · checked {{ $a->verified_at->diffForHumans() }} @endif
                        @else
                            {{ match ($p) {
                                \App\Enums\Platform::Facebook => 'Post text and pictures to your Facebook Page.',
                                \App\Enums\Platform::Instagram => 'Share picture posts to your business account.',
                                \App\Enums\Platform::Twitter => 'Tweet to your X account (280 characters).',
                                \App\Enums\Platform::LinkedIn => 'Share updates to your profile or company page.',
                            } }}
                        @endif
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @if ($ready)
                            <button wire:click="edit('{{ $p->value }}')" class="btn btn-secondary"><x-icon name="cog" class="size-4" /> Manage</button>
                            <button wire:click="verify('{{ $p->value }}')" wire:loading.attr="disabled" wire:target="verify('{{ $p->value }}')" class="btn btn-ghost">
                                <x-icon name="retry" class="size-4" wire:loading.class="animate-spin" wire:target="verify('{{ $p->value }}')" /> Test connection
                            </button>
                        @else
                            <button wire:click="edit('{{ $p->value }}')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Connect {{ $p->label() }}</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============ Connect panel ============ --}}
    @if ($editing)
        @php($p = \App\Enums\Platform::from($editing))
        @php($a = $this->accounts->get($editing))
        <div class="fixed inset-0 z-50" x-data @keydown.escape.window="$wire.close()">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" wire:click="close"></div>
            <div class="animate-pop absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-ink-900">
                <div class="flex items-center gap-4 border-b border-slate-100 px-6 py-5 dark:border-ink-800">
                    <x-platform-icon :platform="$p" tile class="size-12" />
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $a ? 'Manage' : 'Connect' }} {{ $p->label() }}</h3>
                        <p class="text-sm text-slate-500">Takes about 5 minutes, only once.</p>
                    </div>
                    <button wire:click="close" class="btn btn-ghost p-2"><x-icon name="x" /></button>
                </div>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    {{-- Guide --}}
                    <ol class="prose-guide space-y-3">
                        @foreach ($p->guide() as $i => $step)
                            <li class="flex gap-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ $i + 1 }}</span>
                                <span>{!! $step !!}</span>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Facebook quick connect (also fills Instagram) --}}
                    @if (in_array($p, [\App\Enums\Platform::Facebook, \App\Enums\Platform::Instagram]))
                        <div class="rounded-2xl border border-brand-200 bg-brand-50/50 p-5 dark:border-brand-500/30 dark:bg-brand-500/5">
                            <div class="flex items-center gap-2">
                                <x-icon name="bolt" class="size-5 text-brand-600" />
                                <h4 class="font-bold text-slate-900 dark:text-white">Quick connect</h4>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Connects your Facebook Page and its Instagram account together.</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label">App ID</label>
                                    <input wire:model="fb.app_id" class="input" autocomplete="off">
                                </div>
                                <div>
                                    <label class="label">App Secret</label>
                                    <input wire:model="fb.app_secret" type="password" class="input" autocomplete="off">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="label">User access token</label>
                                <input wire:model="fb.user_token" type="password" class="input" autocomplete="off" placeholder="EAAG…">
                                @error('fb.user_token') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                                <p class="mt-1.5 text-xs text-slate-500">With App ID + Secret your connection never expires. Without them it lasts about an hour.</p>
                            </div>
                            <button wire:click="findPages" class="btn btn-primary mt-4" wire:loading.attr="disabled" wire:target="findPages">
                                <span wire:loading.remove wire:target="findPages">Find my pages</span>
                                <span wire:loading wire:target="findPages">Searching…</span>
                            </button>

                            @if ($pages)
                                <ul class="mt-4 space-y-2">
                                    @foreach ($pages as $i => $page)
                                        <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 dark:border-ink-700 dark:bg-ink-850">
                                            @if ($page['picture'])
                                                <img src="{{ $page['picture'] }}" class="size-10 rounded-full" alt="">
                                            @else
                                                <span class="grid size-10 place-items-center rounded-full bg-[#1877F2] font-bold text-white">{{ mb_substr($page['name'], 0, 1) }}</span>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $page['name'] }}</p>
                                                <p class="truncate text-xs text-slate-500">{{ $page['ig_id'] ? 'Instagram @'.$page['ig_username'] : 'No Instagram linked' }}</p>
                                            </div>
                                            <button wire:click="usePage({{ $i }})" class="btn btn-secondary px-3 py-2 text-xs">Use this page</button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                            <span class="h-px flex-1 bg-slate-200 dark:bg-ink-700"></span> or enter manually <span class="h-px flex-1 bg-slate-200 dark:bg-ink-700"></span>
                        </div>
                    @endif

                    {{-- Fields --}}
                    <div class="space-y-4">
                        @foreach ($p->fields() as $key => $f)
                            <div x-data="{ show: false }">
                                <label class="label">{{ $f['label'] }} @if ($f['required'] ?? false)<span class="text-rose-500">*</span>@endif</label>
                                <div class="relative">
                                    <input wire:model="form.{{ $key }}" class="input {{ $f['secret'] ? 'pr-11 font-mono' : '' }}" autocomplete="off" spellcheck="false"
                                           @if ($f['secret']) :type="show ? 'text' : 'password'" @endif
                                           placeholder="{{ $f['secret'] && ($a?->credentials[$key] ?? false) ? '•••••••• saved — leave empty to keep' : ($f['placeholder'] ?? '') }}">
                                    @if ($f['secret'])
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600"><x-icon name="eye" class="size-[18px]" /></button>
                                    @endif
                                </div>
                                @if ($f['hint'] ?? false) <p class="mt-1.5 text-xs text-slate-500">{{ $f['hint'] }}</p> @endif
                                @error("form.$key") <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach

                        @if ($p === \App\Enums\Platform::LinkedIn)
                            <button wire:click="detectLinkedIn" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="detectLinkedIn">
                                <x-icon name="user" class="size-4" /> Detect my profile
                            </button>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 dark:border-ink-800 dark:bg-ink-850/50">
                    @if ($a)
                        <button wire:click="disconnect('{{ $p->value }}')" wire:confirm="Disconnect {{ $p->label() }}? Saved keys will be removed." class="btn btn-ghost text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                            <x-icon name="trash" class="size-4" /> Disconnect
                        </button>
                    @endif
                    <div class="ml-auto flex gap-2">
                        <button wire:click="close" class="btn btn-secondary">Cancel</button>
                        <button wire:click="save" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Save & test</span>
                            <span wire:loading wire:target="save">Checking…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
