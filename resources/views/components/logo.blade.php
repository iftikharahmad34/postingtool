<a href="{{ route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <span class="grid size-9 place-items-center rounded-xl bg-linear-to-br from-brand-500 via-brand-600 to-pink-500 text-white shadow-glow">
        <x-icon name="send" class="size-[18px] -rotate-12" />
    </span>
    <span class="leading-tight">
        <span class="block text-[15px] font-extrabold tracking-tight text-slate-900 dark:text-white">PostPilot</span>
        <span class="block text-[11px] font-medium text-slate-400">Publish everywhere</span>
    </span>
</a>
