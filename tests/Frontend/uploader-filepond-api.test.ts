import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const componentPath = new URL(
    '../../resources/js/components/uploader/Uploader.vue',
    import.meta.url,
);

test('uploader configures FilePond poster dimensions through the direct API', async () => {
    const source = await readFile(componentPath, 'utf8');

    assert.match(source, /import \{ create, registerPlugin \} from 'filepond'/);
    assert.match(source, /create\(pondInput\.value, \{/);
    assert.match(source, /filePosterHeight: imagePreviewHeight\.value/);
    assert.match(source, /filePosterMaxHeight: imagePreviewHeight\.value/);
    assert.match(source, /pond\.value\?\.destroy\(\)/);
});
