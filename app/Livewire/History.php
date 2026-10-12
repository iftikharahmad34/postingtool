<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\PostTarget;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Posts')]
class History extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search'])) {
            $this->resetPage();
        }
    }

    public function retry(int $targetId): void
    {
        set_time_limit(300);
        $target = PostTarget::findOrFail($targetId);
        $target->publish();
        $target->refresh();

        $this->dispatch('toast', ...($target->status === 'published'
            ? ['type' => 'success', 'message' => "Published to {$target->platform->label()}."]
            : ['type' => 'error', 'message' => "{$target->platform->label()}: {$target->error}"]));
    }

    public function delete(int $postId): void
    {
        $post = Post::findOrFail($postId);
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
        $post->delete();
        $this->dispatch('toast', type: 'success', message: 'Removed from your history. Posts on the networks are not deleted.');
    }

    public function render()
    {
        $counts = Post::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('livewire.history', [
            'posts' => Post::with('targets')
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->search, fn ($q) => $q->where('body', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(10),
            'filters' => [
                '' => ['All', $counts->sum()],
                'published' => ['Published', $counts['published'] ?? 0],
                'partial' => ['Partly', $counts['partial'] ?? 0],
                'failed' => ['Failed', $counts['failed'] ?? 0],
            ],
        ]);
    }
}
