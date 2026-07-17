import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const layoutPath = new URL(
    '../../resources/js/layouts/settings/Layout.vue',
    import.meta.url,
);

test('settings content uses the available desktop width', async () => {
    const source = await readFile(layoutPath, 'utf8');

    assert.match(source, /md:max-w-3xl/);
    assert.doesNotMatch(source, /max-w-xl/);
});
