<?php

namespace App\Services\Social;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** X (Twitter) API v2 with OAuth 1.0a user-context keys. */
class TwitterPublisher extends Publisher
{
    public const LIMIT = 280;

    /** OAuth 1.0a HMAC-SHA1 header. Only query parameters are signed (JSON / multipart bodies are not). */
    private function authHeader(string $method, string $url, array $query = []): string
    {
        $oauth = [
            'oauth_consumer_key' => $this->cred('api_key'),
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $this->cred('access_token'),
            'oauth_version' => '1.0',
        ];

        $pairs = [];
        foreach (array_merge($oauth, $query) as $k => $v) {
            $pairs[] = [rawurlencode((string) $k), rawurlencode((string) $v)];
        }
        sort($pairs);
        $params = implode('&', array_map(fn ($p) => $p[0].'='.$p[1], $pairs));
        $base = strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode($params);
        $key = rawurlencode($this->cred('api_secret')).'&'.rawurlencode($this->cred('access_secret'));
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $base, $key, true));

        return 'OAuth '.collect($oauth)->map(fn ($v, $k) => rawurlencode($k).'="'.rawurlencode($v).'"')->implode(', ');
    }

    private function client(string $method, string $url, array $query = []): PendingRequest
    {
        return Http::timeout(120)->withHeaders(['Authorization' => $this->authHeader($method, $url, $query)]);
    }

    private function check(Response $r): array
    {
        if ($r->failed()) {
            $j = $r->json() ?? [];
            $msg = $j['detail'] ?? $j['errors'][0]['message'] ?? $j['title'] ?? str($r->body())->limit(200);
            $msg .= match ($r->status()) {
                401 => ' — check the 4 keys.',
                403 => ' — the app needs “Read and write” permission; regenerate the Access Token after changing it.',
                default => '',
            };
            throw new PublishException("HTTP {$r->status()}: $msg");
        }

        return $r->json() ?? [];
    }

    private function uploadImage(array $image): string
    {
        $url = 'https://api.x.com/2/media/upload';
        try {
            $j = $this->check($this->client('POST', $url)
                ->attach('media', fopen($image['path'], 'r'), $image['name'], ['Content-Type' => $image['mime']])
                ->post($url, ['media_category' => 'tweet_image', 'media_type' => $image['mime']]));
            $id = $j['data']['id'] ?? null;
        } catch (PublishException $e) {
            // Older developer projects only have the legacy v1.1 upload endpoint.
            $legacy = 'https://upload.twitter.com/1.1/media/upload.json';
            $j = rescue(fn () => $this->check($this->client('POST', $legacy)
                ->attach('media', fopen($image['path'], 'r'), $image['name'])
                ->post($legacy)), report: false);
            $id = $j['media_id_string'] ?? throw $e;
        }

        return $id ?: throw new PublishException('Picture upload to X failed.');
    }

    public function publish(string $text, ?array $image): array
    {
        $fitted = self::fit($text);
        $body = ['text' => $fitted];
        if ($image) {
            $body['media'] = ['media_ids' => [$this->uploadImage($image)]];
        }

        $url = 'https://api.x.com/2/tweets';
        $id = $this->check($this->client('POST', $url)->post($url, $body))['data']['id'];
        $handle = $this->cred('username') ?: 'i/web';

        return array_filter([
            'id' => $id,
            'url' => "https://x.com/$handle/status/$id",
            'note' => $fitted !== $text ? 'Shortened to fit 280 characters' : null,
        ]);
    }

    public function verify(): string
    {
        $url = 'https://api.x.com/2/users/me';

        return '@'.$this->check($this->client('GET', $url)->get($url))['data']['username'];
    }

    /** X's weighted length: links count 23, Latin/Arabic/Urdu 1, CJK and emoji 2. */
    public static function weightedLength(string $text): int
    {
        $n = 0;
        $text = preg_replace_callback('~https?://\S+~u', function () use (&$n) {
            $n += 23;

            return '';
        }, $text);
        foreach (mb_str_split($text) as $ch) {
            $n += self::weight($ch);
        }

        return $n;
    }

    private static function weight(string $ch): int
    {
        $cp = mb_ord($ch);

        return ($cp <= 4351 || ($cp >= 8192 && $cp <= 8205) || ($cp >= 8208 && $cp <= 8223) || ($cp >= 8242 && $cp <= 8247)) ? 1 : 2;
    }

    public static function fit(string $text): string
    {
        if (self::weightedLength($text) <= self::LIMIT) {
            return $text;
        }
        $out = '';
        $n = 0;
        foreach (mb_str_split($text) as $ch) {
            if ($n + self::weight($ch) > self::LIMIT - 1) {
                break;
            }
            $out .= $ch;
            $n += self::weight($ch);
        }

        return rtrim($out).'…';
    }
}
