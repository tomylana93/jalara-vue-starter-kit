<?php

return [
    'title' => 'Style settings',
    'description' => 'Manage application branding, layouts, colors, and typography.',
    'action' => [
        'save' => 'Save style settings',
    ],
    'label' => [
        'site_logo_style' => 'Logo style',
        'site_auth_layout' => 'Authentication layout',
        'site_layout' => 'Application layout',
        'site_theme' => 'Color theme',
        'site_font' => 'Typography',
    ],
    'asset' => [
        'icon' => 'Primary icon',
        'icon_dark' => 'Dark-mode icon',
        'logo' => 'Primary logo',
        'logo_dark' => 'Dark-mode logo',
        'favicon' => 'Favicon',
        'auth_split_background' => 'Split authentication background',
    ],
    'options' => [
        'logo_style' => ['icon' => 'Icon', 'logo' => 'Logo'],
        'auth_layout' => ['simple' => 'Simple', 'split' => 'Split', 'card' => 'Card'],
        'layout' => ['sidebar' => 'Sidebar', 'header' => 'Header'],
        'theme' => [
            'zinc' => 'Zinc', 'slate' => 'Slate', 'emerald' => 'Emerald',
            'rose' => 'Rose', 'indigo' => 'Indigo', 'violet' => 'Violet',
            'cyan' => 'Cyan', 'orange' => 'Orange', 'teal' => 'Teal',
            'fuchsia' => 'Fuchsia',
        ],
        'font' => [
            'inter' => 'Inter',
            'sora-inter' => 'Sora / Inter',
            'plus-jakarta-dm-sans' => 'Plus Jakarta Sans / DM Sans',
            'space-grotesk-inter' => 'Space Grotesk / Inter',
            'nunito-plus-jakarta' => 'Nunito / Plus Jakarta Sans',
        ],
    ],
    'toast' => [
        'updated' => 'Style settings updated.',
    ],
    'error' => [
        'temporary_upload_invalid' => 'The staged image is unavailable. Upload it again.',
    ],
];
