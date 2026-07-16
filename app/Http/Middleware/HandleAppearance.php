<?php

namespace App\Http\Middleware;

use App\Models\SiteBranding;
use App\Settings\GeneralSettings;
use App\Settings\StyleSettings;
use App\Support\Branding\BrandingResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly StyleSettings $styleSettings,
        private readonly BrandingResolver $brandingResolver,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'system');
        View::share('siteName', $this->generalSettings->site_name);
        View::share('siteTheme', $this->styleSettings->site_theme);
        View::share('siteFont', $this->styleSettings->site_font);
        View::share('favicon', $this->brandingResolver->resolve(SiteBranding::singleton())['favicon']);

        return $next($request);
    }
}
