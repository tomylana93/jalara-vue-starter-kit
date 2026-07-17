// Real SSR render smoke: drives the built Inertia SSR bundle
// (bootstrap/ssr/app.js) with representations of the auth, settings/style, and
// an authenticated app route. Fails if any page hits a browser-global
// ReferenceError (window/document/navigator/localStorage/...) during SSR.
//
// This is NOT part of the `test:frontend` node-test glob because it needs a
// build artifact. Run it with:
//
//     pnpm run build:ssr && pnpm run test:ssr
//
import { pathToFileURL } from 'node:url';

// Simulate a strict SSR runtime: strip every browser global BEFORE importing
// the bundle. Node 24 exposes `navigator` (as "Node.js/24"), which would mask
// unguarded navigator access that still breaks on Bun/edge/older runtimes, so
// we remove it too. `document`/`window`/`localStorage`/`matchMedia` are already
// absent in Node; deleting is a no-op guard against future Node additions.
for (const key of [
    'navigator',
    'window',
    'document',
    'localStorage',
    'sessionStorage',
    'matchMedia',
]) {
    try {
        delete globalThis[key];
    } catch {
        // non-configurable; leave as-is
    }
}

const bundleUrl = process.env.SSR_BUNDLE
    ? pathToFileURL(process.env.SSR_BUNDLE)
    : new URL('../../bootstrap/ssr/app.js', import.meta.url);

let renderPage;
try {
    ({ default: renderPage } = await import(bundleUrl.href));
} catch (error) {
    console.error(
        `Could not load the SSR bundle at ${bundleUrl.pathname}.\n` +
            'Run `pnpm run build:ssr` first.\n' +
            String(error?.message ?? error),
    );
    process.exit(2);
}

const style = {
    site_logo_style: 'icon',
    site_auth_layout: 'split',
    site_layout: 'sidebar',
    site_theme: 'zinc',
    site_font: 'inter',
};

const branding = {
    icon: '/assets/images/branding/icon.png',
    icon_dark: '/assets/images/branding/icon-dark.png',
    logo: '/assets/images/branding/logo.png',
    logo_dark: '/assets/images/branding/logo-dark.png',
    favicon: '/assets/images/branding/favicon.ico',
    auth_split_background: '/assets/images/auth-bg.jpg',
};

const authedUser = {
    id: 1,
    name: 'SSR Smoke',
    email: 'ssr@example.com',
    avatar: '/assets/images/branding/icon.png',
    email_verified_at: '2026-01-01T00:00:00Z',
    two_factor_confirmed_at: null,
};

const shared = (user) => ({
    name: 'SSR Smoke App',
    locale: 'en',
    style,
    branding,
    auth: {
        user,
        abilities: { manage_settings: true },
    },
    sidebarOpen: true,
    flash: {},
    errors: {},
    ziggy: { location: 'http://localhost', url: 'http://localhost' },
});

const pages = [
    {
        component: 'auth/Login',
        url: '/login',
        props: {
            ...shared(null),
            canResetPassword: true,
            status: null,
        },
    },
    {
        component: 'settings/style/Edit',
        url: '/settings/style',
        props: {
            ...shared(authedUser),
            styleSettings: style,
            logoStyleOptions: [{ value: 'icon', label: 'Icon' }],
            authLayoutOptions: [{ value: 'split', label: 'Split' }],
            layoutOptions: [{ value: 'sidebar', label: 'Sidebar' }],
            themeOptions: [{ value: 'zinc', label: 'Zinc' }],
            fontOptions: [{ value: 'inter', label: 'Inter' }],
            existingFiles: {
                icon: null,
                icon_dark: null,
                logo: null,
                logo_dark: null,
                favicon: null,
                auth_split_background: null,
            },
        },
    },
    {
        component: 'Profile',
        url: '/profile',
        props: {
            ...shared({ ...authedUser, avatar: null }),
            avatar: null,
            mustVerifyEmail: false,
            status: null,
        },
    },
    {
        component: 'Security',
        url: '/security',
        props: {
            ...shared(authedUser),
            // canManagePasskeys:true forces PasskeyRegister to render and run
            // getDefaultPasskeyName() -> navigator.userAgent during SSR setup.
            canManagePasskeys: true,
            canManageTwoFactor: true,
            passkeys: [],
            passwordRules: 'min:8',
            twoFactorEnabled: false,
            requiresConfirmation: true,
        },
    },
];

const BROWSER_GLOBAL =
    /(window|document|navigator|localStorage|sessionStorage|matchMedia|location)[^\n]*is not defined|is not defined[^\n]*(window|document|navigator|localStorage|sessionStorage|matchMedia|location)/;

// Vue SSR does not reject on a component setup error — it reports the error via
// console.error/console.warn and returns partial HTML (the same behavior that
// made the original bug a silent CSR fallback). Capture console output per page
// so a browser-global ReferenceError surfaces as a failure.
const realError = console.error.bind(console);
const realWarn = console.warn.bind(console);
let captured = [];
console.error = (...args) => {
    captured.push(args.map((a) => String(a?.stack ?? a)).join(' '));
};
console.warn = (...args) => {
    captured.push(args.map((a) => String(a?.stack ?? a)).join(' '));
};

let failures = 0;

for (const page of pages) {
    captured = [];
    let thrown = null;
    let body = '';

    try {
        const result = await renderPage({
            component: page.component,
            props: page.props,
            url: page.url,
            version: 'ssr-smoke',
            clearHistory: false,
            encryptHistory: false,
            deferredProps: {},
            mergeProps: [],
        });
        body = typeof result?.body === 'string' ? result.body : '';
    } catch (error) {
        thrown = String(error?.stack ?? error?.message ?? error);
    }

    const haystack = [thrown ?? '', ...captured].join('\n');
    const browserGlobalHit = BROWSER_GLOBAL.test(haystack);

    if (browserGlobalHit) {
        failures += 1;
        const line = haystack.split('\n').find((l) => BROWSER_GLOBAL.test(l));
        realError(
            `FAIL ${page.component.padEnd(20)} ${page.url}  <- ${line?.trim()}`,
        );
    } else if (thrown) {
        // A non-browser-global throw (e.g. a missing optional prop) is not an
        // SSR-safety regression, but report it so it is not silently ignored.
        realError(
            `ERR  ${page.component.padEnd(20)} ${page.url}  (non-browser-global: ${thrown.split('\n')[0]})`,
        );
    } else {
        realError(
            `OK   ${page.component.padEnd(20)} ${page.url}  (body ${body.length} chars, ${captured.length} console msg)`,
        );
    }
}

console.error = realError;
console.warn = realWarn;

realError(
    `\n${failures === 0 ? 'PASS' : 'FAIL'}: ${failures} browser-global SSR failure(s)`,
);
process.exit(failures === 0 ? 0 : 1);
