<div x-data="{
        items: [],
        add(e) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type: e.detail.type ?? 'success', message: e.detail.message });
            setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4500);
        }
     }"
     @toast.window="add($event)"
     class="pointer-events-none fixed right-4 bottom-4 z-[60] flex w-full max-w-sm flex-col gap-2.5">
    @if (session('toast'))
        <div x-init="add({ detail: @js(session('toast')) })"></div>
    @endif
    <template x-for="t in items" :key="t.id">
        <div class="animate-pop pointer-events-auto flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl dark:border-ink-700 dark:bg-ink-850">
            <span class="grid size-8 shrink-0 place-items-center rounded-full"
                  :class="t.type === 'error' ? 'bg-rose-100 text-rose-600 dark:bg-rose-500/15' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15'">
                <x-icon name="check" class="size-4" x-show="t.type !== 'error'" />
                <x-icon name="warning" class="size-4" x-show="t.type === 'error'" />
            </span>
            <p class="flex-1 pt-1 text-sm font-medium text-slate-700 dark:text-slate-200" x-text="t.message"></p>
            <button class="text-slate-400 hover:text-slate-600" @click="items = items.filter(i => i.id !== t.id)"><x-icon name="x" class="size-4" /></button>
        </div>
    </template>
</div>
