import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const pagePath = new URL(
    '../../resources/js/pages/Profile.vue',
    import.meta.url,
);

test('profile renders initials fallback and a responsive avatar column', async () => {
    const source = await readFile(pagePath, 'utf8');

    assert.doesNotMatch(source, /<div\s+v-if="props\.avatar"/);
    assert.match(source, /<AvatarImage\s+v-if="props\.avatar"/);
    assert.match(source, /<AvatarFallback>\s*\{\{\s*avatarInitials/);
    assert.match(source, /lg:grid-cols-\[minmax\(0,1fr\)_12rem\]/);
    assert.match(source, /lg:col-start-2/);
});
