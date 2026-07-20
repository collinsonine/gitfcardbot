<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null): ?string
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, ?string $value): void
    {
        Setting::set($key, $value);
    }

    public function companyName(): string
    {
        return $this->get('company_name', 'GiftCardBot');
    }

    public function logoUrl(): ?string
    {
        $path = $this->get('logo_path');

        return $path ? asset('storage/'.$path) : null;
    }
}
