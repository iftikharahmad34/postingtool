<?php

namespace App\Services\Social;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** X (Twitter) API v2 with OAuth 1.0a user-context keys. */
class TwitterPublisher extends Publisher
{
    public const LIMIT = 280;

    /** Every link is replaced by a t.co link of this length. */
    private const LINK_WEIGHT = 23;

    /** Links with a scheme or www., plus bare domains on common TLDs (not e-mail addresses). */
    private const LINK_PATTERN = '~(?:https?://|www\\.)\\S+|(?<![\\w@.-])[a-z0-9-]+(?:\\.[a-z0-9-]+)*\\.(?:com|net|org|io|co|info|biz|me|tv|app|dev|ai|ly|gov|edu|news|blog|site|online|shop|store|xyz|uk|us|pk|in|de|fr|es|it|nl|ca|au)(?:/\\S*)?(?![\\w-])~iu';

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
            $msg = (string) ($j['detail'] ?? $j['errors'][0]['message'] ?? $j['title'] ?? str($r->body())->limit(200));

            throw new PublishException("HTTP {$r->status()}: $msg".self::hint($r->status(), $msg));
        }

        return $r->json() ?? [];
    }

    /** A short pointer to the usual fix, based on X's error. */
    private static function hint(int $status, string $message): string
    {
        $m = strtolower($message);

        return match (true) {
            $status === 401 => ' — check the 4 keys.',
            str_contains($m, 'too long') => ' — the text is over 280 characters as X counts them (links count 23, emoji 2).',
            str_contains($m, 'duplicate') => ' — X refuses a post identical to one already published; change the text.',
            str_contains($m, 'permission') => ' — the app needs “Read and write” permission; regenerate the Access Token after changing it.',
            str_contains($m, 'project') => ' — in the X developer portal the app must be attached to a Project.',
            $status === 429 => ' — X rate limit reached (the Free plan allows 17 posts per 24 hours); try again later.',
            $status === 403 => ' — the app needs “Read and write” permission; regenerate the Access Token after changing it.',
            default => '',
        };
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

    /**
     * X's weighted length: links count 23, Latin/Arabic/Urdu 1, CJK and emoji 2.
     * Text is NFC-normalised first, exactly like X does before counting.
     */
    public static function weightedLength(string $text): int
    {
        return array_sum(array_column(self::tokens($text), 1));
    }

    /** Shorten to the 280 limit, ending in an ellipsis (which X counts as 2) and never cutting a link in half. */
    public static function fit(string $text): string
    {
        $tokens = self::tokens($text);
        if (array_sum(array_column($tokens, 1)) <= self::LIMIT) {
            return $text;
        }

        $ellipsis = '…';
        $budget = self::LIMIT - self::weight($ellipsis);
        $out = '';
        $n = 0;
        foreach ($tokens as [$piece, $weight]) {
            if ($n + $weight > $budget) {
                break;
            }
            $out .= $piece;
            $n += $weight;
        }

        return rtrim($out).$ellipsis;
    }

    /**
     * Split text into the pieces X counts: a link (23 whatever its length) or a single character.
     * Bare domains such as example.com are links too — X wraps them in t.co.
     *
     * @return list<array{0:string, 1:int}>
     */
    private static function tokens(string $text): array
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        $tokens = [];
        $cursor = 0;
        preg_match_all(self::LINK_PATTERN, $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$link, $offset]) {
            foreach (mb_str_split(substr($text, $cursor, $offset - $cursor)) as $ch) {
                $tokens[] = [$ch, self::weight($ch)];
            }
            $tokens[] = [$link, self::LINK_WEIGHT];
            $cursor = $offset + strlen($link);
        }
        foreach (mb_str_split(substr($text, $cursor)) as $ch) {
            $tokens[] = [$ch, self::weight($ch)];
        }

        return $tokens;
    }

    private static function weight(string $ch): int
    {
        $cp = mb_ord($ch);

        return ($cp <= 4351 || ($cp >= 8192 && $cp <= 8205) || ($cp >= 8208 && $cp <= 8223) || ($cp >= 8242 && $cp <= 8247)) ? 1 : 2;
    }
}
