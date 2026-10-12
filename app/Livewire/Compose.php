<?php

namespace App\Livewire;

use App\Enums\Platform;
use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Create post')]
class Compose extends Component
{
    use WithFileUploads;

    public string $body = '';

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    /** @var list<string> platform values to publish to */
    public array $selected = [];

    /** Optional per-platform text that replaces the main text. */
    public array $custom = [];

    /** The post currently being published (drives the progress screen). */
    public ?int $postId = null;

    public function mount(): void
    {
        $this->selected = $this->accounts->filter->isReady()->keys()->values()->all();
        $this->custom = collect(Platform::cases())->mapWithKeys(fn ($p) => [$p->value => ''])->all();
    }

    /** @return Collection<string, SocialAccount> */
    #[Computed]
    public function accounts(): Collection
    {
        return SocialAccount::all()->keyBy(fn ($a) => $a->platform->value);
    }

    #[Computed]
    public function post(): ?Post
    {
        return $this->postId ? Post::with('targets')->find($this->postId) : null;
    }

    public function updatedImage(): void
    {
        $this->validateOnly('image', ['image' => 'nullable|image|mimes:jpg,jpeg,png|max:8192'], [
            'image.max' => 'The picture must be smaller than 8 MB.',
            'image.mimes' => 'Only JPG and PNG pictures are supported.',
        ]);
    }

    public function removeImage(): void
    {
        $this->image = null;
        $this->resetErrorBag('image');
    }

    public function publish(): void
    {
        $ready = $this->accounts->filter->isReady()->keys()->all();
        $this->selected = array_values(array_intersect($this->selected, $ready));

        $this->validate([
            'selected' => 'required|array|min:1',
            'body' => 'nullable|string|max:63206',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:8192',
        ], ['selected.required' => 'Choose at least one channel to publish to.']);

        if (blank($this->body) && ! $this->image) {
            $this->addError('body', 'Write something or add a picture.');

            return;
        }
        if (in_array(Platform::Instagram->value, $this->selected) && ! $this->image) {
            $this->addError('image', 'Instagram needs a picture — add one or switch Instagram off.');

            return;
        }

        $post = Post::create([
            'user_id' => auth()->id(),
            'body' => trim($this->body),
            'image_path' => $this->image?->store('posts', 'public'),
            'status' => 'publishing',
        ]);

        foreach (Platform::cases() as $p) {
            if (in_array($p->value, $this->selected)) {
                $post->targets()->create([
                    'platform' => $p,
                    'body' => trim($this->custom[$p->value] ?? '') ?: trim($this->body),
                ]);
            }
        }

        $this->postId = $post->id;
        $this->dispatch('publish-start', ids: $post->targets()->pluck('id')->all());
    }

    /** Called from the browser once per channel so progress shows up live. */
    public function publishTarget(int $id): void
    {
        set_time_limit(300);
        $target = PostTarget::where('post_id', $this->postId)->findOrFail($id);
        if ($target->status === 'pending') {
            $target->publish();
        }
    }

    public function finished(): void
    {
        $post = $this->post;
        match ($post?->status) {
            'published' => $this->dispatch('toast', type: 'success', message: 'Your post is live on every channel! 🎉'),
            'partial' => $this->dispatch('toast', type: 'error', message: 'Published, but some channels failed — see details.'),
            default => $this->dispatch('toast', type: 'error', message: 'Publishing failed — see details below.'),
        };
    }

    public function retry(int $id): void
    {
        set_time_limit(300);
        $target = PostTarget::where('post_id', $this->postId)->findOrFail($id);
        $target->publish();
        $this->finished();
    }

    public function startOver(): void
    {
        $this->reset('body', 'image', 'postId');
        $this->custom = array_map(fn () => '', $this->custom);
    }

    public function render()
    {
        return view('livewire.compose', [
            'platforms' => Platform::cases(),
        ]);
    }
}
