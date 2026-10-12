<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Social\LinkedInPublisher;
use App\Services\Social\TwitterPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_first_run_setup(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertRedirect('/setup');
    }

    public function test_all_pages_render_for_a_signed_in_user(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/', '/compose', '/posts', '/accounts', '/settings'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_x_length_and_shortening(): void
    {
        $this->assertSame(23 + 6, TwitterPublisher::weightedLength('hello https://example.com/a/very/long/path'));
        $this->assertSame(280, mb_strlen(TwitterPublisher::fit(str_repeat('a', 400))));
    }

    public function test_linkedin_text_is_escaped_and_hashtags_kept(): void
    {
        $this->assertSame(
            'Hi \(there\) {hashtag|\#|Lahore}',
            LinkedInPublisher::littleText('Hi (there) #Lahore'),
        );
    }
}
