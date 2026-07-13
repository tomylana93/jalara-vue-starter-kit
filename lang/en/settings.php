<?php

return [
    'sidebar' => 'Settings',
    'index' => [
        'title' => 'Settings',
        'heading' => 'Settings',
        'description' => 'Manage your application configuration.',
        'general' => [
            'title' => 'General',
            'description' => 'Application name, description, and default language.',
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
