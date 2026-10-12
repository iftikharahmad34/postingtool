<?php

namespace Tests\Feature\Services\Social;

use App\Services\Social\PublishException;
use App\Services\Social\TwitterPublisher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitterPublisherTest extends TestCase
{
    private function publisher(): TwitterPublisher
    {
        return new TwitterPublisher([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'access_token' => 'token',
            'access_secret' => 'token-secret',
            'username' => 'postpilot',
        ]);
    }

    public function test_publishes_text_and_returns_the_post_url(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.x.com/2/tweets' => Http::response(['data' => ['id' => '123']], 201)]);

        $result = $this->publisher()->publish('Hello X', null);

        $this->assertSame(['id' => '123', 'url' => 'https://x.com/postpilot/status/123'], $result);
        Http::assertSent(fn ($request) => $request['text'] === 'Hello X' && str_starts_with($request->header('Authorization')[0], 'OAuth '));
    }

    public function test_too_long_error_is_explained_without_the_permission_hint(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.x.com/2/tweets' => Http::response(['detail' => 'Your Tweet text is too long.'], 403)]);

        try {
            $this->publisher()->publish('Hello X', null);
            $this->fail('Expected a PublishException.');
        } catch (PublishException $e) {
            $this->assertStringStartsWith('HTTP 403: Your Tweet text is too long.', $e->getMessage());
            $this->assertStringContainsString('over 280 characters', $e->getMessage());
            $this->assertStringNotContainsString('Read and write', $e->getMessage());
        }
    }

    public function test_permission_error_points_to_read_and_write_access(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.x.com/2/tweets' => Http::response(['detail' => 'Your client app is not configured with the appropriate oauth1 app permissions for this endpoint.'], 403)]);

        try {
            $this->publisher()->publish('Hello X', null);
            $this->fail('Expected a PublishException.');
        } catch (PublishException $e) {
            $this->assertStringContainsString('Read and write', $e->getMessage());
        }
    }

    public function test_rate_limit_error_mentions_the_daily_limit(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.x.com/2/tweets' => Http::response(['title' => 'Too Many Requests'], 429)]);

        try {
            $this->publisher()->publish('Hello X', null);
            $this->fail('Expected a PublishException.');
        } catch (PublishException $e) {
            $this->assertStringContainsString('17 posts per 24 hours', $e->getMessage());
        }
    }
}
