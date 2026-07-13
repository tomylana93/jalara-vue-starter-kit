import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { ComputedRef } from 'vue';

type ReplacementValue = number | string;
type Replacements = Record<string, ReplacementValue>;
type TranslationMessages = {
    [key: string]: TranslationMessages | string;
};

type EagerGlob = (
    pattern: string,
    options: { eager: true; import: 'default' },
) => Record<string, TranslationMessages>;

const localeFiles = (import.meta.glob as unknown as EagerGlob)(
    '../../../lang/*.json',
    {
        eager: true,
        import: 'default',
    },
);

const messages = Object.fromEntries(
    Object.entries(localeFiles).flatMap(([path, contents]) => {
        const locale = path.match(/([^/]+)\.json$/)?.[1];

        return locale ? [[locale, contents]] : [];
    }),
) as Record<string, TranslationMessages>;

function getMessage(locale: string, key: string): string | null {
    const message = key.split('.').reduce<TranslationMessages | string | null>(
        (current, segment) => {
            if (
                current === null ||
                typeof current === 'string' ||
                !Object.hasOwn(current, segment)
            ) {
                return null;
            }

            return current[segment];
        },
        messages[locale] ?? messages.en ?? null,
    );

    return typeof message === 'string' ? message : null;
}

function replacePlaceholders(
    message: string,
    replacements: Replacements,
): string {
    return Object.entries(replacements)
        .sort((a, b) => b[0].length - a[0].length)
        .reduce(
            (current, [key, value]) =>
                current
                    .replaceAll(
                        `:${key.toUpperCase()}`,
                        String(value).toUpperCase(),
                    )
                    .replaceAll(
                        `:${key.charAt(0).toUpperCase()}${key.slice(1)}`,
                        String(value).charAt(0).toUpperCase() +
                            String(value).slice(1),
                    )
                    .replaceAll(`:${key}`, String(value)),
            message,
        );
}

export function trans(
    key: string,
    replacements: Replacements = {},
    locale?: string,
): string {
    return replacePlaceholders(
        getMessage(locale ?? 'en', key) ?? key,
        replacements,
    );
}

export function useTrans(): {
    locale: ComputedRef<string>;
    trans: (
        key: string,
        replacements?: Replacements,
        locale?: string,
    ) => string;
} {
    const page = usePage();
    const locale = computed(() => page.props.locale);

    return {
        locale,
        trans: (key, replacements = {}, targetLocale = locale.value) =>
            trans(key, replacements, targetLocale),
    };
}
