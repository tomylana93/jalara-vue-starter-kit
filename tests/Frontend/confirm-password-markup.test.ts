import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const componentPath = new URL(
    '../../resources/js/pages/auth/ConfirmPassword.vue',
    import.meta.url,
);

test('password confirmation label uses the Vue for attribute', async () => {
    const source = await readFile(componentPath, 'utf8');

    assert.match(source, /<Label for="password">/);
    assert.doesNotMatch(source, /\bhtmlFor=/);
});
