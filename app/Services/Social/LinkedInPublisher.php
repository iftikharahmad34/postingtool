<?php

namespace App\Services\Social;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** LinkedIn Posts API — personal profile or company page. */
class LinkedInPublisher extends Publisher
{
    private function api(): PendingRequest
    {
        return Http::timeout(120)
            ->withToken($this->cred('access_token'))
            ->withHeaders([
                // Versions are YYYYMM and retire after about a year, so use a recent one.
                'LinkedIn-Version' => now()->subMonths(3)->format('Ym'),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]);
    }

    private function check(Response $r): Response
    {
        if ($r->failed()) {
            $msg = $r->json('message') ?? str($r->body())->limit(200);
            if ($r->status() === 401) {
                $msg .= ' — the token is invalid or expired (LinkedIn tokens last 60 days).';
            }
            throw new PublishException("HTTP {$r->status()}: $msg");
        }

        return $r;
    }

    private function uploadImage(array $image): string
    {
        $init = $this->check($this->api()->post('https://api.linkedin.com/rest/images?action=initializeUpload', [
            'initializeUploadRequest' => ['owner' => $this->cred('author_urn')],
        ]))->json('value');

        $this->check(Http::timeout(120)->withToken($this->cred('access_token'))
            ->withBody(file_get_contents($image['path']), 'application/octet-stream')
            ->put($init['uploadUrl']));

        // Give LinkedIn a moment to process the picture.
        for ($i = 0; $i < 10; $i++) {
            $status = rescue(fn () => $this->api()->get('https://api.linkedin.com/rest/images/'.rawurlencode($init['image']))->json('status'), report: false);
            if ($status === 'AVAILABLE' || $status === null) {
                break;
            }
            sleep(1);
        }

        return $init['image'];
    }

    public function publish(string $text, ?array $image): array
    {
        $post = [
            'author' => $this->cred('author_urn'),
            'commentary' => self::littleText(mb_substr($text, 0, 3000)),
            'visibility' => 'PUBLIC',
            'distribution' => ['feedDistribution' => 'MAIN_FEED', 'targetEntities' => [], 'thirdPartyDistributionChannels' => []],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];
        if ($image) {
            $post['content'] = ['media' => ['id' => $this->uploadImage($image)]];
        }

        $r = $this->check($this->api()->post('https://api.linkedin.com/rest/posts', $post));
        $urn = $r->header('x-restli-id') ?: $r->header('x-linkedin-id');

        return ['id' => $urn, 'url' => $urn ? "https://www.linkedin.com/feed/update/$urn/" : 'https://www.linkedin.com/feed/'];
    }

    /** @return array{urn:string, name:string} via OpenID userinfo (needs openid + profile scopes). */
    public function me(): array
    {
        $r = Http::withToken($this->cred('access_token'))->get('https://api.linkedin.com/v2/userinfo');
        if ($r->failed() || ! $r->json('sub')) {
            throw new PublishException("Could not read your LinkedIn profile (HTTP {$r->status()}). The token may be expired or missing the “openid profile” scopes.");
        }

        return ['urn' => 'urn:li:person:'.$r->json('sub'), 'name' => (string) $r->json('name')];
    }

    public function verify(): string
    {
        $urn = $this->cred('author_urn');
        if (str_starts_with($urn, 'urn:li:person:')) {
            $me = $this->me();
            if ($me['urn'] !== $urn) {
                throw new PublishException("This token belongs to {$me['name']} ({$me['urn']}).");
            }

            return $me['name'];
        }

        $id = str($urn)->afterLast(':');

        return $this->check($this->api()->get("https://api.linkedin.com/rest/organizations/$id"))->json('localizedName') ?? (string) $id;
    }

    /**
     * LinkedIn "little text": reserved characters must be escaped or the post is cut off,
     * and #hashtags become {hashtag|\#|tag} so they stay clickable.
     */
    public static function littleText(string $t): string
    {
        $out = '';
        $last = 0;
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $t, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[0] as $i => [$full, $off]) {
            $out .= self::escape(substr($t, $last, $off - $last));
            $out .= '{hashtag|\\#|'.self::escape($m[1][$i][0]).'}';
            $last = $off + strlen($full);
        }

        return $out.self::escape(substr($t, $last));
    }

    private static function escape(string $s): string
    {
        return preg_replace('/([\\\\|{}@\[\]()<>#*_~])/u', '\\\\$1', $s);
    }
}
