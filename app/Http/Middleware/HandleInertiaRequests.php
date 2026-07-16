<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\SiteBranding;
use App\Settings\GeneralSettings;
use App\Settings\StyleSettings;
use App\Support\Branding\BrandingResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly StyleSettings $styleSettings,
        private readonly BrandingResolver $brandingResolver,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => $this->generalSettings->site_name,
            'locale' => app()->getLocale(),
            'style' => [
                'site_logo_style' => $this->styleSettings->site_logo_style,
                'site_auth_layout' => $this->styleSettings->site_auth_layout,
                'site_layout' => $this->styleSettings->site_layout,
                'site_theme' => $this->styleSettings->site_theme,
                'site_font' => $this->styleSettings->site_font,
            ],
            'branding' => fn (): array => $this->brandingResolver->resolve(SiteBranding::singleton()),
            'auth' => [
                'user' => fn () => $user === null ? null : [
                    ...$user->toArray(),
                    'avatar' => $user->avatarUrl(),
                ],
                'abilities' => [
                    'manage_settings' => $user?->can(Permission::ManageSettings->value) ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
