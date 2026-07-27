import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const pagePath = new URL(
    '../../resources/js/pages/Profile.vue',
    import.meta.url,
);

test('profile uses a fixed avatar column only when the desktop viewport is wide enough', async () => {
    const source = await readFile(pagePath, 'utf8');

    assert.doesNotMatch(source, /<div\s+v-if="props\.avatar"/);
    assert.match(source, /<AvatarImage\s+v-if="props\.avatar"/);
    assert.match(source, /<AvatarFallback>\s*\{\{\s*avatarInitials/);
    assert.match(source, /xl:grid-cols-\[minmax\(0,1fr\)_18rem\]/);
    assert.match(source, /xl:col-start-2/);
    assert.doesNotMatch(source, /lg:grid-cols-2/);
    assert.match(source, /<Avatar class="size-40">/);
    assert.match(source, /class="flex flex-col gap-4 xl:col-start-2/);
    assert.match(source, /<Button\s+v-if="props\.avatar"[\s\S]*as-child/);
    assert.match(source, /<Trash2 class="size-4" \/>/);
    assert.match(source, /group-hover:opacity-100/);
});
