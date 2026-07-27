import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const componentPath = new URL(
    '../../resources/js/components/AppBrand.vue',
    import.meta.url,
);

test('brand uses a larger constrained image class for logo style', async () => {
    const source = await readFile(componentPath, 'utf8');

    assert.match(source, /const resolvedImageClass = computed\(/);
    assert.match(source, /usesLogo\.value \? 'h-10 max-w-40 w-auto' : props\.imageClass/);
    assert.match(source, /:class="resolvedImageClass"/);
});
