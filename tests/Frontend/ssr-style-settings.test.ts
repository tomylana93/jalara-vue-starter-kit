import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const composablePath = new URL(
    '../../resources/js/composables/useStyleSettings.ts',
    import.meta.url,
);

test('style settings avoids DOM access during SSR', async () => {
    const source = await readFile(composablePath, 'utf8');

    assert.match(
        source,
        /if \(typeof document === 'undefined'\) \{\s*return;\s*\}/,
    );
    assert.match(source, /document\.documentElement\.dataset\.theme/);
    assert.match(source, /document\.documentElement\.dataset\.font/);
});
