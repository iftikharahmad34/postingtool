@props(['status'])
@php
    [$classes, $label] = match ($status) {
        'published' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-500/10 dark:text-emerald-400', 'Published'],
        'partial' => ['bg-amber-50 text-amber-700 ring-amber-600/15 dark:bg-amber-500/10 dark:text-amber-400', 'Partly published'],
        'failed' => ['bg-rose-50 text-rose-700 ring-rose-600/15 dark:bg-rose-500/10 dark:text-rose-400', 'Failed'],
        'pending' => ['bg-slate-100 text-slate-600 ring-slate-500/15 dark:bg-ink-800 dark:text-slate-400', 'Waiting'],
        default => ['bg-brand-50 text-brand-700 ring-brand-600/15 dark:bg-brand-500/10 dark:text-brand-300', 'Publishing'],
    };
@endphp
<span {{ $attributes->merge(['class' => "chip ring-1 ring-inset $classes"]) }}>
    @if ($status === 'publishing')
        <span class="size-1.5 animate-pulse rounded-full bg-current"></span>
    @else
        <span class="size-1.5 rounded-full bg-current"></span>
    @endif
    {{ $label }}
</span>
