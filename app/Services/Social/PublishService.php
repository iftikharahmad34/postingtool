<?php

namespace App\Services\Social;

use App\Models\PostTarget;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PublishService
{
    public function publish(PostTarget $target): void
    {
        $target->update(['status' => 'publishing', 'error' => null]);
        $post = $target->post;

        try {
            $account = SocialAccount::for($target->platform);
            if (! $account?->isReady()) {
                throw new PublishException('This account is not connected — open Accounts.');
            }

            $credentials = $account->resolvedCredentials();
            if ($target->platform->value === 'twitter') {
                $credentials['username'] = ltrim((string) $account->account_name, '@');
            }

            $result = $target->platform
                ->publisher($credentials)
                ->publish((string) $target->body, $this->image($post->image_path));

            $target->update([
                'status' => 'published',
                'external_id' => $result['id'],
                'url' => $result['url'],
                'note' => $result['note'] ?? null,
                'published_at' => now(),
            ]);
        } catch (Throwable $e) {
            report_if(! $e instanceof PublishException, $e);
            $target->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        $post->refreshStatus();
    }

    /** Local picture info, plus a public URL when the app is reachable from the internet. */
    private function image(?string $path): ?array
    {
        if (! $path) {
            return null;
        }
        $disk = Storage::disk('public');
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $isPublic = ! preg_match('/(^localhost$|^127\.|\.test$|\.localhost$|\.local$)/', $host);

        return [
            'path' => $disk->path($path),
            'mime' => $disk->mimeType($path),
            'name' => basename($path),
            'url' => $isPublic ? $disk->url($path) : null,
        ];
    }
}
