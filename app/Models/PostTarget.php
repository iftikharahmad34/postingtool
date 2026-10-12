<?php

namespace App\Models;

use App\Enums\Platform;
use App\Services\Social\PublishService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTarget extends Model
{
    protected $fillable = ['post_id', 'platform', 'body', 'status', 'external_id', 'url', 'error', 'note', 'published_at'];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'published_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function publish(): void
    {
        app(PublishService::class)->publish($this);
    }
}
