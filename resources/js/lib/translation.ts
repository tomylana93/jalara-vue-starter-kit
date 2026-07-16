export type ReplacementValue = number | string;
export type Replacements = Record<string, ReplacementValue>;

export type TranslationMessages = {
    [key: string]: TranslationMessages | string;
};

export type TranslationCatalog = Record<string, TranslationMessages>;

export interface Translator<Key extends string = string> {
    trans(key: Key, replacements?: Replacements, locale?: string): string;
    transChoice(
        key: Key,
        count: number,
        replacements?: Replacements,
        locale?: string,
    ): string;
}

export type TranslationPart =
    { text: string; slot?: undefined } | { slot: string; text?: undefined };

const FALLBACK_LOCALE = 'en';

/**
 * Create a locale-agnostic translator over an injected message catalog.
 *
 * Lookup, English fallback, Laravel-compatible replacement, and pluralization
 * live here as pure functions so they can be tested without Vite glob state or
 * Inertia. Callers bind an explicit locale.
 */
export function createTranslator<Key extends string = string>(
    messages: TranslationCatalog,
): Translator<Key> {
    return {
        trans: (key, replacements = {}, locale = FALLBACK_LOCALE) =>
            replacePlaceholders(
                getMessage(messages, locale, key) ?? key,
                replacements,
            ),
        transChoice: (
            key,
            count,
            replacements = {},
            locale = FALLBACK_LOCALE,
        ) =>
            replacePlaceholders(
                choose(getMessage(messages, locale, key) ?? key, count, locale),
                { ...replacements, count },
            ),
    };
}

/**
 * Split a resolved message into text and named-slot parts, tokenizing only the
 * explicitly declared `:slot` names. Everything else — including any markup —
 * stays literal text and is never interpreted as HTML.
 */
export function translationParts(
    message: string,
    slots: readonly string[],
): TranslationPart[] {
    if (slots.length === 0) {
        return [{ text: message }];
    }

    const names = [...slots].sort((a, b) => b.length - a.length);
    const pattern = new RegExp(
        `:(${names.map(escapeRegExp).join('|')})\\b`,
        'g',
    );

    const parts: TranslationPart[] = [];
    let lastIndex = 0;

    for (
        let match = pattern.exec(message);
        match;
        match = pattern.exec(message)
    ) {
        if (match.index > lastIndex) {
            parts.push({ text: message.slice(lastIndex, match.index) });
        }

        parts.push({ slot: match[1] });
        lastIndex = match.index + match[0].length;
    }

    if (lastIndex < message.length) {
        parts.push({ text: message.slice(lastIndex) });
    }

    return parts;
}

function getMessage(
    messages: TranslationCatalog,
    locale: string,
    key: string,
): string | null {
    const root = messages[locale] ?? messages[FALLBACK_LOCALE] ?? null;

    const message = key
        .split('.')
        .reduce<TranslationMessages | string | null>((current, segment) => {
            if (
                current === null ||
                typeof current === 'string' ||
                !Object.hasOwn(current, segment)
            ) {
                return null;
            }

            return current[segment];
        }, root);

    if (typeof message === 'string') {
        return message;
    }

    // Fall back to English for a locale that lacks the key entirely.
    if (locale !== FALLBACK_LOCALE && messages[FALLBACK_LOCALE]) {
        return getMessage(messages, FALLBACK_LOCALE, key);
    }

    return null;
}

function replacePlaceholders(
    message: string,
    replacements: Replacements,
): string {
    return Object.entries(replacements)
        .sort((a, b) => b[0].length - a[0].length)
        .reduce((current, [key, value]) => {
            const text = String(value);

            return current
                .replaceAll(`:${key.toUpperCase()}`, text.toUpperCase())
                .replaceAll(
                    `:${key.charAt(0).toUpperCase()}${key.slice(1)}`,
                    text.charAt(0).toUpperCase() + text.slice(1),
                )
                .replaceAll(`:${key}`, text);
        }, message);
}

/**
 * Select a Laravel-compatible plural branch for the given count and locale.
 */
function choose(message: string, count: number, locale: string): string {
    const segments = message.split('|');

    const explicit = extractExplicit(segments, count);

    if (explicit !== null) {
        return explicit;
    }

    const stripped = segments.map(stripCondition);
    const index = pluralIndex(locale, count);

    if (stripped.length === 1 || stripped[index] === undefined) {
        return stripped[0];
    }

    return stripped[index];
}

function extractExplicit(segments: string[], count: number): string | null {
    for (const segment of segments) {
        const exact = /^\s*\{\s*(\*|-?\d+)\s*\}(.*)$/s.exec(segment);

        if (exact) {
            if (exact[1] === String(count)) {
                return exact[2].trim();
            }

            continue;
        }

        const interval = /^\s*\[\s*(\*|-?\d+)\s*,\s*(\*|-?\d+)\s*\](.*)$/s.exec(
            segment,
        );

        if (interval) {
            const low = interval[1] === '*' ? -Infinity : Number(interval[1]);
            const high = interval[2] === '*' ? Infinity : Number(interval[2]);

            if (count >= low && count <= high) {
                return interval[3].trim();
            }
        }
    }

    return null;
}

function stripCondition(segment: string): string {
    return segment
        .replace(/^\s*\{\s*(?:\*|-?\d+)\s*\}/, '')
        .replace(/^\s*\[\s*(?:\*|-?\d+)\s*,\s*(?:\*|-?\d+)\s*\]/, '')
        .trim();
}

/**
 * Plural index for the supported global locales. English distinguishes one
 * from many; Indonesian has a single form.
 */
function pluralIndex(locale: string, count: number): number {
    if (locale === 'id') {
        return 0;
    }

    return count === 1 ? 0 : 1;
}

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
