<?php

namespace App\Services\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacebookPublisher extends Publisher
{
    public const GRAPH = 'https://graph.facebook.com/v23.0/';

    /** Graph API call that turns Facebook's error payload into a readable exception. */
    public static function graph(string $method, string $path, array $params = [], ?array $file = null): array
    {
        $req = Http::timeout(120);
        if ($file) {
            $req = $req->attach('source', fopen($file['path'], 'r'), $file['name'], ['Content-Type' => $file['mime']]);
        }

        /** @var Response $r */
        $r = match ($method) {
            'GET' => $req->get(self::GRAPH.$path, $params),
            'DELETE' => $req->delete(self::GRAPH.$path.'?'.http_build_query($params)),
            default => $file ? $req->post(self::GRAPH.$path, $params) : $req->asForm()->post(self::GRAPH.$path, $params),
        };

        $j = $r->json();
        if (! is_array($j)) {
            throw new PublishException("Unexpected response from Facebook (HTTP {$r->status()}).");
        }
        if (isset($j['error'])) {
            $e = $j['error'];
            throw new PublishException(($e['error_user_msg'] ?? $e['message'] ?? 'Unknown error').(isset($e['code']) ? " (code {$e['code']})" : ''));
        }

        return $j;
    }

    private function call(string $method, string $path, array $params = [], ?array $file = null): array
    {
        return self::graph($method, $path, $params + ['access_token' => $this->cred('page_token')], $file);
    }

    public function publish(string $text, ?array $image): array
    {
        $page = $this->cred('page_id');

        if ($image) {
            $j = $this->call('POST', "$page/photos", ['message' => $text], $image);
            $id = $j['post_id'] ?? $j['id'];
        } else {
            $id = $this->call('POST', "$page/feed", ['message' => $text])['id'];
        }

        return ['id' => $id, 'url' => 'https://www.facebook.com/'.$id];
    }

    /**
     * Upload a hidden photo to the Page and return its public CDN URL and id. Instagram needs
     * a public image URL, and this works even while the tool runs on localhost.
     *
     * @return array{0:string, 1:string}
     */
    public function hostImage(array $image): array
    {
        $id = $this->call('POST', $this->cred('page_id').'/photos', ['published' => 'false'], $image)['id'];
        $images = collect($this->call('GET', $id, ['fields' => 'images'])['images'] ?? [])->sortByDesc('width');
        $url = $images->first()['source'] ?? null;
        if (! $url) {
            throw new PublishException('Could not get a public link for the picture from Facebook.');
        }

        return [$url, $id];
    }

    public function deletePhoto(string $id): void
    {
        rescue(fn () => $this->call('DELETE', $id), report: false);
    }

    public function verify(): string
    {
        return $this->call('GET', $this->cred('page_id'), ['fields' => 'name'])['name'];
    }

    /**
     * Pages the user manages, each with its own Page token and linked Instagram account.
     * With App ID + Secret the user token is first made long-lived, so Page tokens never expire.
     */
    public static function pagesFor(string $userToken, ?string $appId, ?string $appSecret): array
    {
        if ($appId && $appSecret) {
            $userToken = self::graph('GET', 'oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $userToken,
            ])['access_token'];
        }

        $data = self::graph('GET', 'me/accounts', [
            'fields' => 'id,name,access_token,picture{url},instagram_business_account{id,username}',
            'limit' => 100,
            'access_token' => $userToken,
        ])['data'] ?? [];

        return array_map(fn ($p) => [
            'id' => $p['id'],
            'name' => $p['name'],
            'token' => $p['access_token'] ?? '',
            'picture' => $p['picture']['data']['url'] ?? null,
            'ig_id' => $p['instagram_business_account']['id'] ?? '',
            'ig_username' => $p['instagram_business_account']['username'] ?? '',
        ], $data);
    }
}
