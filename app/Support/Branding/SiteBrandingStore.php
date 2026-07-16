<?php

namespace App\Support\Branding;

use App\Models\SiteBranding;

final class SiteBrandingStore
{
    private ?SiteBranding $branding = null;

    public function get(): SiteBranding
    {
        return $this->branding ??= SiteBranding::query()->firstOrCreate(['key' => 'site']);
    }
}
