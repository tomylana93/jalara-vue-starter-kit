<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateStyleSettings;
use App\Enums\SiteAuthLayout;
use App\Enums\SiteFont;
use App\Enums\SiteLayout;
use App\Enums\SiteLogoStyle;
use App\Enums\SiteTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateStyleSettingsRequest;
use App\Models\SiteBranding;
use App\Settings\StyleSettings;
use App\Support\Branding\BrandingResolver;
use App\Support\Branding\SiteBrandingStore;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StyleSettingsController extends Controller
{
    public function edit(
        StyleSettings $settings,
        BrandingResolver $brandingResolver,
        SiteBrandingStore $siteBrandingStore,
    ): Response {
        $branding = $siteBrandingStore->get();

        return Inertia::render('settings/style/Edit', [
            'styleSettings' => [
                'site_logo_style' => $settings->site_logo_style,
                'site_auth_layout' => $settings->site_auth_layout,
                'site_layout' => $settings->site_layout,
                'site_theme' => $settings->site_theme,
                'site_font' => $settings->site_font,
            ],
            'logoStyleOptions' => SiteLogoStyle::options(),
            'authLayoutOptions' => SiteAuthLayout::options(),
            'layoutOptions' => SiteLayout::options(),
            'themeOptions' => SiteTheme::options(),
            'fontOptions' => SiteFont::options(),
            'branding' => $brandingResolver->resolve($branding),
            'existingFiles' => collect([
                'icon' => SiteBranding::Icon,
                'icon_dark' => SiteBranding::IconDark,
                'logo' => SiteBranding::Logo,
                'logo_dark' => SiteBranding::LogoDark,
                'favicon' => SiteBranding::Favicon,
                'auth_split_background' => SiteBranding::AuthSplitBackground,
            ])->map(fn (string $collection): array => $this->existingFile(
                $branding->getFirstMedia($collection),
            ))->all(),
        ]);
    }

    public function update(
        UpdateStyleSettingsRequest $request,
        StyleSettings $settings,
        UpdateStyleSettings $updateStyleSettings,
    ): RedirectResponse {
        $updateStyleSettings->handle($settings, $request->payload(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('style.toast.updated')]);

        return to_route('settings.style.edit');
    }

    /** @return list<array{id: int, source: string, name: string, size: int, type: ?string, poster: string}> */
    private function existingFile(?Media $media): array
    {
        if (! $media instanceof Media) {
            return [];
        }

        return [[
            'id' => $media->id,
            'source' => (string) $media->id,
            'name' => $media->file_name,
            'size' => $media->size,
            'type' => $media->mime_type,
            'poster' => $media->getUrl(),
        ]];
    }
}
