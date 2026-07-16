<?php

return [
    'title' => 'Pengaturan gaya',
    'description' => 'Kelola branding, tata letak, warna, dan tipografi aplikasi.',
    'action' => [
        'save' => 'Simpan pengaturan gaya',
    ],
    'label' => [
        'site_logo_style' => 'Gaya logo',
        'site_auth_layout' => 'Tata letak autentikasi',
        'site_layout' => 'Tata letak aplikasi',
        'site_theme' => 'Tema warna',
        'site_font' => 'Tipografi',
    ],
    'asset' => [
        'icon' => 'Ikon utama',
        'icon_dark' => 'Ikon mode gelap',
        'logo' => 'Logo utama',
        'logo_dark' => 'Logo mode gelap',
        'favicon' => 'Favicon',
        'auth_split_background' => 'Latar autentikasi terbagi',
    ],
    'options' => [
        'logo_style' => ['icon' => 'Ikon', 'logo' => 'Logo'],
        'auth_layout' => ['simple' => 'Sederhana', 'split' => 'Terbagi', 'card' => 'Kartu'],
        'layout' => ['sidebar' => 'Bilah samping', 'header' => 'Header'],
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
        'updated' => 'Pengaturan gaya diperbarui.',
    ],
    'error' => [
        'temporary_upload_invalid' => 'Gambar sementara tidak tersedia. Unggah kembali.',
    ],
];
