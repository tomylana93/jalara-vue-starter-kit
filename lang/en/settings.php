<?php

return [
    'sidebar' => 'Settings',
    'layout' => [
        'aria' => 'Account settings',
    ],
    'nav' => [
        'profile' => 'Profile',
        'security' => 'Security',
    ],
    'profile' => [
        'title' => 'Profile settings',
        'heading' => 'Profile settings',
        'description' => 'Update your profile information and avatar.',
    ],
    'security' => [
        'title' => 'Security settings',
        'heading' => 'Security settings',
        'description' => 'Manage your password and sign-in security.',
    ],
    'index' => [
        'title' => 'Settings',
        'heading' => 'Settings',
        'description' => 'Manage your application configuration.',
        'action' => [
            'open' => 'Open',
        ],
    ],
    'general' => [
        'title' => 'General settings',
        'heading' => 'General',
        'description' => 'Update the application name, description, and default language.',
        'label' => [
            'site_name' => 'Application name',
            'site_description' => 'Application description',
            'site_locale' => 'Default language',
        ],
        'placeholder' => [
            'site_name' => 'Application name',
            'site_description' => 'A short description of your application',
        ],
        'action' => [
            'save' => 'Save',
        ],
        'toast' => [
            'updated' => 'General settings updated.',
        ],
    ],
];
