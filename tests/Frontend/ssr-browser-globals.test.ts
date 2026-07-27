import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

/**
 * SSR-safety guard contract.
 *
 * Inertia renders every page component on the Node SSR runtime before the
 * browser hydrates it. Any browser-only global (`window`, `document`,
 * `navigator`, `localStorage`, `sessionStorage`, `matchMedia`, `location`)
 * touched during module initialization, `setup()`, a `computed`, or an
 * `immediate` watcher throws a `ReferenceError` on that runtime and forces a
 * silent client-side-render fallback.
 *
 * These cases audit every source file that reaches a browser global from a
 * setup/module scope. Each must guard the access with `typeof x === 'undefined'`
 * (or defer it to `onMounted`/an event handler). The assertions fail the moment
 * a guard is removed, reproducing the original `document is not defined` class
 * of SSR error before it can ship.
 */

const resolve = (relativePath: string): URL =>
    new URL(`../../${relativePath}`, import.meta.url);

const read = (relativePath: string): Promise<string> =>
    readFile(resolve(relativePath), 'utf8');

test('style composable guards document before its immediate watcher', async () => {
    const source = await read('resources/js/composables/useStyleSettings.ts');

    assert.match(
        source,
        /if \(typeof document === 'undefined'\) \{\s*return;\s*\}/,
    );
    assert.match(source, /document\.documentElement\.dataset\.theme/);
    assert.match(source, /document\.documentElement\.dataset\.font/);
});

test('passkey register guards navigator before reading the user agent', async () => {
    const source = await read('resources/js/components/PasskeyRegister.vue');

    // The guard must appear before the first navigator.userAgent read so the
    // default-name helper is safe when it runs during setup.
    const guardIndex = source.search(
        /if \(typeof navigator === 'undefined'\) \{\s*return '';\s*\}/,
    );
    const usageIndex = source.indexOf('navigator.userAgent');

    assert.notEqual(guardIndex, -1, 'expected a navigator availability guard');
    assert.notEqual(usageIndex, -1, 'expected navigator.userAgent usage');
    assert.ok(
        guardIndex < usageIndex,
        'navigator guard must precede navigator.userAgent',
    );
});

test('appearance composable guards every browser global it touches', async () => {
    const source = await read('resources/js/composables/useAppearance.ts');

    // window / localStorage / matchMedia access is only reachable behind a
    // typeof-undefined guard or from within onMounted.
    for (const token of ['window.matchMedia', 'localStorage', 'document.cookie']) {
        assert.ok(source.includes(token), `expected ${token} usage`);
    }

    assert.match(source, /typeof window === 'undefined'/);
    assert.match(source, /typeof document === 'undefined'/);
    assert.match(source, /onMounted\(/);
});

test('current-url composable guards window.location during SSR', async () => {
    const source = await read('resources/js/composables/useCurrentUrl.ts');

    assert.match(source, /typeof window !== 'undefined'/);
    assert.match(source, /window\.location\.origin/);
});

test('uploader defers window listeners and guards document', async () => {
    const source = await read('resources/js/components/uploader/Uploader.vue');

    assert.match(source, /typeof document === 'undefined'/);
    // pagehide listeners are wired inside lifecycle hooks, never at setup top level.
    assert.match(source, /onMounted\(/);
    assert.match(source, /window\.addEventListener\('pagehide'/);
});
