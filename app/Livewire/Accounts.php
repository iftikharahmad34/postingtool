<?php

namespace App\Livewire;

use App\Enums\Platform;
use App\Models\SocialAccount;
use App\Services\Social\FacebookPublisher;
use App\Services\Social\LinkedInPublisher;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Accounts')]
class Accounts extends Component
{
    /** Platform whose connect panel is open. */
    public ?string $editing = null;

    /** Field values in the panel. Secrets are never sent to the browser; blank means "keep". */
    public array $form = [];

    /** Facebook quick-connect helper. */
    public array $fb = ['app_id' => '', 'app_secret' => '', 'user_token' => ''];

    #[Locked]
    public array $pages = [];

    #[Computed]
    public function accounts()
    {
        return SocialAccount::all()->keyBy(fn ($a) => $a->platform->value);
    }

    public function edit(string $platform): void
    {
        $p = Platform::from($platform);
        $saved = SocialAccount::for($p)?->credentials ?? [];

        $this->form = collect($p->fields())
            ->mapWithKeys(fn ($f, $key) => [$key => $f['secret'] ? '' : ($saved[$key] ?? '')])
            ->all();
        $this->editing = $platform;
        $this->pages = [];
        $this->resetErrorBag();
    }

    public function close(): void
    {
        $this->reset('editing', 'form', 'pages');
    }

    public function save(): void
    {
        $p = Platform::from($this->editing);
        $existing = SocialAccount::for($p);
        $saved = $existing?->credentials ?? [];

        // Keep stored secrets when their field was left blank.
        $credentials = collect($p->fields())->mapWithKeys(function ($f, $key) use ($saved) {
            $value = trim((string) ($this->form[$key] ?? ''));

            return [$key => $f['secret'] && $value === '' ? ($saved[$key] ?? '') : $value];
        })->all();

        $missing = collect($p->fields())->filter(fn ($f, $key) => ($f['required'] ?? false) && $credentials[$key] === '');
        foreach ($missing as $key => $f) {
            $this->addError("form.$key", "{$f['label']} is required.");
        }
        if ($missing->isNotEmpty()) {
            return;
        }

        $account = $this->store($p, $credentials);
        $this->close();
        $this->check($account);
    }

    public function verify(string $platform): void
    {
        $account = SocialAccount::for(Platform::from($platform));
        if ($account) {
            $this->check($account);
        }
    }

    public function toggle(string $platform): void
    {
        $account = SocialAccount::for(Platform::from($platform));
        $account?->update(['enabled' => ! $account->enabled]);
        unset($this->accounts);
    }

    public function disconnect(string $platform): void
    {
        SocialAccount::for(Platform::from($platform))?->delete();
        unset($this->accounts);
        $this->close();
        $this->dispatch('toast', type: 'success', message: Platform::from($platform)->label().' disconnected.');
    }

    public function findPages(): void
    {
        $this->resetErrorBag('fb.user_token');
        if (blank($this->fb['user_token'])) {
            $this->addError('fb.user_token', 'Paste a User access token first.');

            return;
        }
        try {
            $pages = FacebookPublisher::pagesFor(trim($this->fb['user_token']), trim($this->fb['app_id']), trim($this->fb['app_secret']));
            // Page tokens stay on the server; the browser only gets names and ids.
            session()->put('fb_page_tokens', array_column($pages, 'token', 'id'));
            $this->pages = array_map(fn ($p) => array_diff_key($p, ['token' => 1]), $pages);
            if (! $this->pages) {
                $this->addError('fb.user_token', 'No pages found. Make sure you allowed your Page when generating the token.');
            }
        } catch (Throwable $e) {
            $this->addError('fb.user_token', $e->getMessage());
        }
    }

    /** Connect the chosen page — and its linked Instagram account — in one go. */
    public function usePage(int $index): void
    {
        $page = $this->pages[$index] ?? null;
        $token = session('fb_page_tokens')[$page['id'] ?? ''] ?? null;
        if (! $page || ! $token) {
            $this->addError('fb.user_token', 'Please search for your pages again.');

            return;
        }

        $fb = $this->store(Platform::Facebook, [
            'page_name' => $page['name'],
            'page_id' => $page['id'],
            'page_token' => $token,
        ]);
        session()->forget('fb_page_tokens');

        if ($page['ig_id']) {
            $ig = $this->store(Platform::Instagram, [
                'username' => $page['ig_username'],
                'ig_user_id' => $page['ig_id'],
                'access_token' => '',
            ]);
            $ig->update(['verified_at' => now()]);
        }

        $fb->update(['verified_at' => now()]);
        $this->reset('fb', 'pages');
        $this->close();
        $this->dispatch('toast', type: 'success', message: $page['ig_id']
            ? "Connected {$page['name']} and Instagram @{$page['ig_username']} 🎉"
            : "Connected {$page['name']}. No Instagram account is linked to this page.");
    }

    public function detectLinkedIn(): void
    {
        $this->resetErrorBag('form.access_token');
        $token = trim($this->form['access_token'] ?? '') ?: (SocialAccount::for(Platform::LinkedIn)?->credentials['access_token'] ?? '');
        try {
            $me = (new LinkedInPublisher(['access_token' => $token]))->me();
            $this->form['author_urn'] = $me['urn'];
            $this->form['author_name'] = $me['name'];
        } catch (Throwable $e) {
            $this->addError('form.access_token', $e->getMessage());
        }
    }

    private function store(Platform $p, array $credentials): SocialAccount
    {
        $name = match ($p) {
            Platform::Facebook => $credentials['page_name'] ?: null,
            Platform::Instagram => $credentials['username'] ? '@'.ltrim($credentials['username'], '@') : null,
            Platform::LinkedIn => $credentials['author_name'] ?: null,
            Platform::Twitter => SocialAccount::for($p)?->account_name,
        };

        unset($this->accounts);

        return SocialAccount::updateOrCreate(
            ['platform' => $p->value],
            ['credentials' => $credentials, 'account_name' => $name, 'enabled' => true, 'verified_at' => null],
        );
    }

    private function check(SocialAccount $account): void
    {
        try {
            $name = $account->platform->publisher($account->resolvedCredentials())->verify();
            $account->update([
                'verified_at' => now(),
                'account_name' => $account->platform === Platform::Facebook ? ($account->account_name ?: $name) : $name,
            ]);
            $this->dispatch('toast', type: 'success', message: "{$account->platform->label()} connected as {$name}.");
        } catch (Throwable $e) {
            $account->update(['verified_at' => null]);
            $this->dispatch('toast', type: 'error', message: "{$account->platform->label()}: {$e->getMessage()}");
        }
        unset($this->accounts);
    }

    public function render()
    {
        return view('livewire.accounts', ['platforms' => Platform::cases()]);
    }
}
