<?php

namespace App\Models;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $fillable = ['platform', 'credentials', 'account_name', 'enabled', 'verified_at'];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'credentials' => 'encrypted:array',
            'enabled' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public static function for(Platform $platform): ?self
    {
        return static::where('platform', $platform->value)->first();
    }

    /** Credentials with fallbacks resolved (Instagram reuses the Facebook Page token). */
    public function resolvedCredentials(): array
    {
        $c = $this->credentials ?? [];

        if ($this->platform === Platform::Instagram && empty($c['access_token'])) {
            $c['access_token'] = static::for(Platform::Facebook)?->credentials['page_token'] ?? '';
        }
        if ($this->platform === Platform::Instagram) {
            $c['facebook'] = static::for(Platform::Facebook)?->credentials ?? [];
        }

        return $c;
    }

    public function isReady(): bool
    {
        if (! $this->enabled) {
            return false;
        }
        $c = $this->resolvedCredentials();
        foreach ($this->platform->fields() as $key => $field) {
            if (($field['required'] ?? false) && blank($c[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
