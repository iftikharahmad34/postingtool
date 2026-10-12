<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public(): void
    {
        $response = $this->get('/privacy');

        $response->assertOk();
        $response->assertSee('Privacy Policy');
        $response->assertSee('Deleting your data');
    }

    public function test_terms_of_service_are_public(): void
    {
        $response = $this->get('/terms');

        $response->assertOk();
        $response->assertSee('Terms of Service');
    }
}
