<?php

namespace App\Enums;

use App\Services\Social\FacebookPublisher;
use App\Services\Social\InstagramPublisher;
use App\Services\Social\LinkedInPublisher;
use App\Services\Social\Publisher;
use App\Services\Social\TwitterPublisher;

enum Platform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Twitter = 'twitter';
    case LinkedIn = 'linkedin';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Twitter => 'X (Twitter)',
            self::LinkedIn => 'LinkedIn',
        };
    }

    /** Brand colour used for icons, chips and accents. */
    public function color(): string
    {
        return match ($this) {
            self::Facebook => '#1877F2',
            self::Instagram => '#E1306C',
            self::Twitter => '#0F1419',
            self::LinkedIn => '#0A66C2',
        };
    }

    /** CSS background for the brand tile. */
    public function background(): string
    {
        return match ($this) {
            self::Instagram => 'linear-gradient(45deg,#feda75 0%,#fa7e1e 25%,#d62976 50%,#962fbf 75%,#4f5bd5 100%)',
            default => $this->color(),
        };
    }

    public function limit(): int
    {
        return match ($this) {
            self::Facebook => 63206,
            self::Instagram => 2200,
            self::Twitter => 280,
            self::LinkedIn => 3000,
        };
    }

    public function requiresImage(): bool
    {
        return $this === self::Instagram;
    }

    public function publisher(array $credentials): Publisher
    {
        $class = match ($this) {
            self::Facebook => FacebookPublisher::class,
            self::Instagram => InstagramPublisher::class,
            self::Twitter => TwitterPublisher::class,
            self::LinkedIn => LinkedInPublisher::class,
        };

        return new $class($credentials);
    }

    /**
     * Credential fields shown on the Accounts page.
     *
     * @return array<string, array{label:string, secret:bool, placeholder?:string, hint?:string, required?:bool}>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Facebook => [
                'page_name' => ['label' => 'Page name', 'secret' => false],
                'page_id' => ['label' => 'Page ID', 'secret' => false, 'required' => true],
                'page_token' => ['label' => 'Page access token', 'secret' => true, 'required' => true],
            ],
            self::Instagram => [
                'username' => ['label' => 'Username', 'secret' => false, 'placeholder' => 'yourbrand'],
                'ig_user_id' => ['label' => 'Instagram business account ID', 'secret' => false, 'required' => true],
                'access_token' => ['label' => 'Access token', 'secret' => true, 'hint' => 'Leave empty to reuse the Facebook Page token.'],
            ],
            self::Twitter => [
                'api_key' => ['label' => 'API Key', 'secret' => true, 'required' => true],
                'api_secret' => ['label' => 'API Key Secret', 'secret' => true, 'required' => true],
                'access_token' => ['label' => 'Access Token', 'secret' => true, 'required' => true],
                'access_secret' => ['label' => 'Access Token Secret', 'secret' => true, 'required' => true],
            ],
            self::LinkedIn => [
                'access_token' => ['label' => 'Access token', 'secret' => true, 'required' => true],
                'author_urn' => ['label' => 'Author URN', 'secret' => false, 'required' => true, 'placeholder' => 'urn:li:person:xxxx  or  urn:li:organization:123'],
                'author_name' => ['label' => 'Display name', 'secret' => false],
            ],
        };
    }

    /** Step-by-step connection guide (HTML allowed). */
    public function guide(): array
    {
        return match ($this) {
            self::Facebook => [
                'Open <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a> → <b>Create App</b> → <b>Other → Business</b>. It can stay in Development mode for pages you manage.',
                'From <b>App settings → Basic</b> copy the <b>App ID</b> and <b>App Secret</b> into Quick connect below.',
                'In the <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a> pick your app → <b>User Token</b> → add <code>pages_show_list</code> <code>pages_read_engagement</code> <code>pages_manage_posts</code> <code>business_management</code> <code>instagram_basic</code> <code>instagram_content_publish</code> → <b>Generate Access Token</b>.',
                'Paste the token, press <b>Find my pages</b> and choose your page. Instagram is connected automatically if it is linked to that page.',
            ],
            self::Instagram => [
                'Switch Instagram to a <b>Business</b> or <b>Creator</b> account and link it to your Facebook Page.',
                'Connect Facebook first using <b>Quick connect</b> — Instagram is filled in for you.',
                'Instagram posts always need a picture. It is passed to Instagram through your Facebook Page, so it works from your own PC.',
            ],
            self::Twitter => [
                'Sign in at <a href="https://developer.x.com/en/portal/dashboard" target="_blank">developer.x.com</a> (the Free plan can post).',
                'App → <b>User authentication settings</b>: permissions <b>Read and write</b>, type <b>Web App</b>, callback <code>'.e(url('/').'/').'</code> and website URL <code>'.e(url('/')).'</code>.',
                'In <b>Keys and tokens</b> copy the API Key + Secret, then <b>generate</b> the Access Token + Secret — after setting Read and write, or posting fails with 403.',
            ],
            self::LinkedIn => [
                'Create an app at <a href="https://www.linkedin.com/developers/apps" target="_blank">linkedin.com/developers/apps</a>.',
                'In <b>Products</b> add <b>Share on LinkedIn</b> and <b>Sign In with LinkedIn using OpenID Connect</b>.',
                'Open the <a href="https://www.linkedin.com/developers/tools/oauth/token-generator" target="_blank">Token Generator</a>, choose <code>openid</code> <code>profile</code> <code>w_member_social</code> and copy the token. Then press <b>Detect my profile</b>.',
                'Tokens last 60 days. For a company page you need the Community Management API and author <code>urn:li:organization:ID</code>.',
            ],
        };
    }
}
