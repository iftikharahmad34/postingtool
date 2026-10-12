<?php

namespace App\Services\Social;

class InstagramPublisher extends Publisher
{
    private function call(string $method, string $path, array $params = []): array
    {
        return FacebookPublisher::graph($method, $path, $params + ['access_token' => $this->cred('access_token')]);
    }

    public function publish(string $text, ?array $image): array
    {
        if (! $image) {
            throw new PublishException('Instagram does not allow text-only posts — add a picture.');
        }

        $ig = $this->cred('ig_user_id');
        $facebook = new FacebookPublisher($this->c['facebook'] ?? []);
        $tempPhoto = null;

        if ($image['url']) {
            $imageUrl = $image['url'];
        } elseif (! empty($this->c['facebook']['page_token'])) {
            [$imageUrl, $tempPhoto] = $facebook->hostImage($image);
        } else {
            throw new PublishException('Connect your Facebook Page first — Instagram receives the picture through it.');
        }

        try {
            $container = $this->call('POST', "$ig/media", [
                'image_url' => $imageUrl,
                'caption' => mb_substr($text, 0, 2200),
            ])['id'];

            // Instagram downloads and processes the picture asynchronously.
            for ($i = 0; $i < 30; $i++) {
                $status = $this->call('GET', $container, ['fields' => 'status_code'])['status_code'] ?? '';
                if ($status === 'FINISHED') {
                    break;
                }
                if (in_array($status, ['ERROR', 'EXPIRED'])) {
                    throw new PublishException('Instagram could not process the picture. Use a JPG/PNG with an aspect ratio between 4:5 and 1.91:1.');
                }
                sleep(2);
            }

            $mediaId = $this->call('POST', "$ig/media_publish", ['creation_id' => $container])['id'];
        } finally {
            if ($tempPhoto) {
                $facebook->deletePhoto($tempPhoto);
            }
        }

        $url = rescue(fn () => $this->call('GET', $mediaId, ['fields' => 'permalink'])['permalink'] ?? null, report: false);

        return ['id' => $mediaId, 'url' => $url ?: 'https://www.instagram.com/'.$this->cred('username')];
    }

    public function verify(): string
    {
        return '@'.$this->call('GET', $this->cred('ig_user_id'), ['fields' => 'username'])['username'];
    }
}
