<?php

namespace App\Support\Branding;

use App\Models\SiteBranding;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class BrandingResolver
{
    /** @return array{icon: string, icon_dark: string, logo: string, logo_dark: string, favicon: string, auth_split_background: string} */
    public function resolve(SiteBranding $branding): array
    {
        return [
            'icon' => $this->url($branding, SiteBranding::Icon, 'icon_web')
                ?? '/assets/images/branding/icon.png',
            'icon_dark' => $this->url($branding, SiteBranding::IconDark, 'icon_web')
                ?? '/assets/images/branding/icon-dark.png',
            'logo' => $this->url($branding, SiteBranding::Logo, 'logo_web')
                ?? '/assets/images/branding/logo.png',
            'logo_dark' => $this->url($branding, SiteBranding::LogoDark, 'logo_web')
                ?? '/assets/images/branding/logo-dark.png',
            'favicon' => $this->url($branding, SiteBranding::Favicon)
                ?? '/assets/images/branding/favicon.ico',
            'auth_split_background' => $this->url(
                $branding,
                SiteBranding::AuthSplitBackground,
                'auth_background_web',
            ) ?? '/assets/images/auth-bg.jpg',
        ];
    }

    private function url(SiteBranding $branding, string $collection, ?string $conversion = null): ?string
    {
        $media = $branding->getFirstMedia($collection);

        if (! $media instanceof Media) {
            return null;
        }

        if ($conversion !== null && $media->hasGeneratedConversion($conversion)) {
            return $media->getUrl($conversion);
        }

        return $media->getUrl();
    }
}
