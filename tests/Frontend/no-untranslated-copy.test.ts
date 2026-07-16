import assert from 'node:assert/strict';
import test from 'node:test';

import typescriptParser from '@typescript-eslint/parser';
import { Linter } from 'eslint';
import parser from 'vue-eslint-parser';

import noUntranslatedCopy from '../../eslint-local-rules/no-untranslated-copy.js';

const linter = new Linter();

function lint(source: string, filename = 'resources/js/pages/Example.vue') {
    const isVue = filename.endsWith('.vue');

    return linter.verify(
        source,
        {
            files: ['**/*.{ts,vue}'],
            languageOptions: {
                parser: isVue ? parser : typescriptParser,
                parserOptions: {
                    ecmaVersion: 'latest',
                    ...(isVue ? { parser: typescriptParser } : {}),
                    sourceType: 'module',
                },
            },
            plugins: {
                local: {
                    rules: {
                        'no-untranslated-copy': noUntranslatedCopy,
                    },
                },
            },
            rules: {
                'local/no-untranslated-copy': 'error',
            },
        },
        { filename },
    );
}

test('reports literal Vue text and presentation attributes', () => {
    const messages = lint(`
        <template>
            <div>
                <button title="Save">Save</button>
                <input placeholder="Full name" aria-label="Full name" />
            </div>
        </template>
    `);

    assert.deepEqual(
        messages.map((message) => message.messageId),
        ['attribute', 'text', 'attribute', 'attribute'],
    );
});

test('accepts translated bindings and interpolations', () => {
    const messages = lint(`
        <template>
            <button :title="trans('general.action.save')">
                {{ trans('general.action.save') }}
            </button>
        </template>
    `);

    assert.equal(messages.length, 0);
});

test('reports script presentation properties unless they contain semantic keys', () => {
    const messages = lint(`
        <script setup>
        defineOptions({
            layout: {
                breadcrumbs: [
                    { title: 'General settings', href: '/settings/general' },
                    { title: 'settings.general.title', href: '/settings/general' },
                ],
            },
        });
        </script>
        <template><div /></template>
    `);

    assert.deepEqual(
        messages.map((message) => message.messageId),
        ['property'],
    );
});

test('reports user-visible error literals in TypeScript composables', () => {
    const messages = lint(
        `errors.value.push('Failed to fetch recovery codes');`,
        'resources/js/composables/useTwoFactorAuth.ts',
    );

    assert.deepEqual(
        messages.map((message) => message.messageId),
        ['call'],
    );
});

test('honors a narrow documented localization suppression', () => {
    const messages = lint(`
        <template>
            <div>
                <!-- localization-ignore: immutable product name -->
                <span>Jalara</span>
            </div>
        </template>
    `);

    assert.equal(messages.length, 0);
});
