import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const pagePath = new URL(
    '../../resources/js/pages/settings/style/Edit.vue',
    import.meta.url,
);

test('style settings follows the shared settings page contract', async () => {
    const source = await readFile(pagePath, 'utf8');

    assert.match(source, /index as settingsIndex/);
    assert.match(source, /edit as styleEdit/);
    assert.match(source, /title: 'settings\.index\.title'/);
    assert.match(source, /title: 'style\.title'/);
    assert.match(source, /<Card>/);
    assert.match(source, /<Select(?:\s|>)/);
    assert.match(source, /<SelectTrigger/);
    assert.match(source, /:aria-invalid="invalid\(field\[0\]\)"/);
    assert.match(source, /@blur="validate\(field\[0\]\)"/);
    assert.doesNotMatch(source, /<select(?:\s|>)/);
    assert.match(source, /image\/vnd\.microsoft\.icon/);
    assert.match(source, /preview-size="compact"/);
    assert.match(source, /md:grid-cols-2/);
    assert.match(source, /xl:grid-cols-3/);
    assert.match(
        source,
        /const uploadErrors = ref<Record<AssetField, string>>/,
    );
    assert.match(
        source,
        /@upload-error="[\s\S]*uploadErrors\[asset\.key\] = message[\s\S]*"/,
    );
    assert.match(
        source,
        /uploadErrors\[asset\.key\]\s*\|\|\s*errors\[`\$\{asset\.key\}_upload_id`\]/,
    );
    assert.match(source, /head-key="favicon"[\s\S]*:href="branding\.favicon"/);

    for (const field of [
        'site_logo_style',
        'site_auth_layout',
        'site_layout',
        'site_theme',
        'site_font',
    ]) {
        assert.match(source, new RegExp(`'${field}'`));
    }
});
