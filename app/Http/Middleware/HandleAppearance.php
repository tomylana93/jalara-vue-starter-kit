<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use App\Settings\StyleSettings;
use App\Support\Branding\BrandingResolver;
use App\Support\Branding\SiteBrandingStore;
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
        private readonly SiteBrandingStore $siteBrandingStore,
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
        View::share('favicon', $this->brandingResolver->resolve($this->siteBrandingStore->get())['favicon']);

        return $next($request);
    }
}
