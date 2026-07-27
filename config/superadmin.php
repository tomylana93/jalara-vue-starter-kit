<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin Profile
    |--------------------------------------------------------------------------
    |
    | These values seed the Super Admin account created during authorization
    | setup. The password has no production fallback here; validating its
    | presence in non-local, non-testing environments happens in a later
    | initializer, not in this configuration file.
    |
    */

    'name' => env('SUPERADMIN_NAME', 'Super Admin'),

    'email' => env('SUPERADMIN_EMAIL', 'superadmin@jalara.dev'),

    'phone' => env('SUPERADMIN_PHONE'),

    'status' => env('SUPERADMIN_STATUS', 'active'),

    'email_verified' => env('SUPERADMIN_EMAIL_VERIFIED', true),

    'password' => env('SUPER_ADMIN_PASSWORD') ?? (in_array(env('APP_ENV'), ['local', 'testing'], true) ? 'password' : null),

];
