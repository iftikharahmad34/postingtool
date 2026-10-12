<?php

namespace App\Livewire;

use App\Enums\Platform;
use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $finished = PostTarget::whereIn('status', ['published', 'failed']);
        $finishedCount = (clone $finished)->count();
        $publishedCount = PostTarget::where('status', 'published')->count();

        // Posts per day for the last 14 days.
        $since = now()->startOfDay()->subDays(13);
        $perDay = Post::where('created_at', '>=', $since)->get(['created_at'])
            ->countBy(fn ($p) => $p->created_at->toDateString());
        $activity = collect(range(0, 13))->map(function ($i) use ($since, $perDay) {
            $day = $since->copy()->addDays($i);

            return ['label' => $day->format('D j'), 'short' => $day->format('j'), 'count' => $perDay[$day->toDateString()] ?? 0];
        });

        $accounts = SocialAccount::all()->keyBy(fn ($a) => $a->platform->value);
        $perPlatform = PostTarget::where('status', 'published')
            ->selectRaw('platform, count(*) as total')->groupBy('platform')->pluck('total', 'platform');

        return view('livewire.dashboard', [
            'stats' => [
                ['Total posts', Post::count(), 'pencil', 'from-brand-500 to-brand-700'],
                ['Published this month', PostTarget::where('status', 'published')->where('published_at', '>=', now()->startOfMonth())->count(), 'send', 'from-sky-500 to-blue-600'],
                ['Success rate', $finishedCount ? round($publishedCount / $finishedCount * 100).'%' : '—', 'chart', 'from-emerald-500 to-teal-600'],
                ['Connected channels', $accounts->filter->isReady()->count().' / 4', 'link', 'from-pink-500 to-rose-600'],
            ],
            'activity' => $activity,
            'maxActivity' => max(1, $activity->max('count')),
            'accounts' => $accounts,
            'perPlatform' => $perPlatform,
            'recent' => Post::with('targets')->latest()->take(5)->get(),
            'platforms' => Platform::cases(),
        ]);
    }
}
