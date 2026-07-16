export type GeneralSettings = {
    site_name: string;
    site_description: string;
    site_locale: string;
};

export type StyleSettings = {
    site_logo_style: 'icon' | 'logo';
    site_auth_layout: 'simple' | 'split' | 'card';
    site_layout: 'sidebar' | 'header';
    site_theme:
        | 'zinc'
        | 'slate'
        | 'emerald'
        | 'rose'
        | 'indigo'
        | 'violet'
        | 'cyan'
        | 'orange'
        | 'teal'
        | 'fuchsia';
    site_font:
        | 'inter'
        | 'sora-inter'
        | 'plus-jakarta-dm-sans'
        | 'space-grotesk-inter'
        | 'nunito-plus-jakarta';
};

export type BrandingAssets = {
    icon: string;
    icon_dark: string;
    logo: string;
    logo_dark: string;
    favicon: string;
    auth_split_background: string | null;
};

export type SelectOption = {
    value: string;
    label: string;
};
