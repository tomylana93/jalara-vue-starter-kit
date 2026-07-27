import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { ComputedRef } from 'vue';
import { createTranslator } from '@/lib/translation';

import type {
    Replacements,
    TranslationCatalog,
    TranslationMessages,
} from '@/lib/translation';
import type { TranslationKey } from '@/types/translation.generated';

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

const catalog = Object.fromEntries(
    Object.entries(localeFiles).flatMap(([path, contents]) => {
        const locale = path.match(/([^/]+)\.json$/)?.[1];

        return locale ? [[locale, contents]] : [];
    }),
) as TranslationCatalog;

const translator = createTranslator<TranslationKey>(catalog);

export function trans(
    key: TranslationKey,
    replacements: Replacements = {},
    locale?: string,
): string {
    return translator.trans(key, replacements, locale);
}

export function transChoice(
    key: TranslationKey,
    count: number,
    replacements: Replacements = {},
    locale?: string,
): string {
    return translator.transChoice(key, count, replacements, locale);
}

export function useTrans(): {
    locale: ComputedRef<string>;
    trans: (
        key: TranslationKey,
        replacements?: Replacements,
        locale?: string,
    ) => string;
    transChoice: (
        key: TranslationKey,
        count: number,
        replacements?: Replacements,
        locale?: string,
    ) => string;
} {
    const page = usePage();
    const locale = computed(() => page.props.locale);

    return {
        locale,
        trans: (key, replacements = {}, target = locale.value) =>
            translator.trans(key, replacements, target),
        transChoice: (key, count, replacements = {}, target = locale.value) =>
            translator.transChoice(key, count, replacements, target),
    };
}
