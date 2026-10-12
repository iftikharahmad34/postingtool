<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    protected $fillable = ['user_id', 'body', 'image_path', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PostTarget::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Recompute the overall status from the per-platform results. */
    public function refreshStatus(): void
    {
        $statuses = $this->targets()->pluck('status');

        $this->status = match (true) {
            $statuses->contains(fn ($s) => in_array($s, ['pending', 'publishing'])) => 'publishing',
            $statuses->every(fn ($s) => $s === 'published') => 'published',
            $statuses->every(fn ($s) => $s === 'failed') => 'failed',
            default => 'partial',
        };
        if ($this->status !== 'publishing' && ! $this->published_at && $statuses->contains('published')) {
            $this->published_at = now();
        }
        $this->save();
    }
}
