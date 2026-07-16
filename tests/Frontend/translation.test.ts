import assert from 'node:assert/strict';
import { test } from 'node:test';

import {
    createTranslator,
    translationParts,
} from '../../resources/js/lib/translation.ts';

test('looks up a nested dot-path key', () => {
    const translator = createTranslator({
        en: { greeting: { welcome: 'Hello' } },
        id: { greeting: { welcome: 'Halo' } },
    });

    assert.equal(translator.trans('greeting.welcome', {}, 'id'), 'Halo');
    assert.equal(translator.trans('greeting.welcome', {}, 'en'), 'Hello');
});

test('falls back to English when the locale is missing the key', () => {
    const translator = createTranslator({
        en: { greeting: { welcome: 'Hello' } },
        id: {},
    });

    assert.equal(translator.trans('greeting.welcome', {}, 'id'), 'Hello');
});

test('falls back to the raw key when no message exists', () => {
    const translator = createTranslator({ en: {}, id: {} });

    assert.equal(translator.trans('missing.key', {}, 'en'), 'missing.key');
});

test('applies Laravel capitalization forms', () => {
    const translator = createTranslator({
        en: { greeting: { welcome: 'Hello :name, :Name, :NAME' } },
        id: {},
    });

    assert.equal(
        translator.trans('greeting.welcome', { name: 'ayu' }, 'id'),
        'Hello ayu, Ayu, AYU',
    );
});

test('replaces overlapping replacement names longest-first', () => {
    const translator = createTranslator({
        en: { line: ':name and :name_full' },
        id: {},
    });

    assert.equal(
        translator.trans(
            'line',
            { name: 'Ayu', name_full: 'Ayu Lestari' },
            'en',
        ),
        'Ayu and Ayu Lestari',
    );
});

test('selects explicit and interval plural branches', () => {
    const translator = createTranslator({
        en: { files: '{0} No files|{1} One file|[2,*] :count files' },
        id: {},
    });

    assert.equal(translator.transChoice('files', 0, {}, 'en'), 'No files');
    assert.equal(translator.transChoice('files', 1, {}, 'en'), 'One file');
    assert.equal(translator.transChoice('files', 3, {}, 'en'), '3 files');
});

test('selects standard two-branch singular and plural for English', () => {
    const translator = createTranslator({
        en: { apples: 'There is one apple|There are many apples' },
        id: {},
    });

    assert.equal(
        translator.transChoice('apples', 1, {}, 'en'),
        'There is one apple',
    );
    assert.equal(
        translator.transChoice('apples', 5, {}, 'en'),
        'There are many apples',
    );
});

test('uses the Indonesian plural rule (single form)', () => {
    const translator = createTranslator({
        en: {},
        id: { apples: 'satu apel|banyak apel' },
    });

    // Indonesian has a single plural form, so index 0 is always chosen.
    assert.equal(translator.transChoice('apples', 1, {}, 'id'), 'satu apel');
    assert.equal(translator.transChoice('apples', 9, {}, 'id'), 'satu apel');
});

test('injects the count replacement into plural messages', () => {
    const translator = createTranslator({
        en: {
            passkeys: '{0} No passkeys|{1} One passkey|[2,*] :count passkeys',
        },
        id: {},
    });

    assert.equal(translator.transChoice('passkeys', 4, {}, 'en'), '4 passkeys');
});

test('splits only declared slot tokens and never interprets HTML', () => {
    const parts = translationParts('Read the :terms and :privacy.', [
        'terms',
        'privacy',
    ]);

    assert.deepEqual(parts, [
        { text: 'Read the ' },
        { slot: 'terms' },
        { text: ' and ' },
        { slot: 'privacy' },
        { text: '.' },
    ]);
});

test('leaves undeclared tokens and markup as plain text', () => {
    const parts = translationParts('<b>Bold</b> :unknown text', ['terms']);

    assert.deepEqual(parts, [{ text: '<b>Bold</b> :unknown text' }]);
});
