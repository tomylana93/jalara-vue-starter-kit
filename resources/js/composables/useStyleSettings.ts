import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

export function useStyleSettings(): void {
    if (typeof document === 'undefined') {
        return;
    }

    const page = usePage();

    watch(
        () => page.props.style,
        (style) => {
            document.documentElement.dataset.theme = style.site_theme;
            document.documentElement.dataset.font = style.site_font;
        },
        { deep: true, immediate: true },
    );
}
