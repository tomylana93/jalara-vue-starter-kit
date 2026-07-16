<?php

return [
    'password' => [
        'heading' => 'Update password',
        'description' => 'Ensure your account is using a long, random password to stay secure',
        'label' => [
            'current' => 'Current password',
            'new' => 'New password',
            'confirm' => 'Confirm password',
        ],
        'placeholder' => [
            'current' => 'Current password',
            'new' => 'New password',
            'confirm' => 'Confirm password',
        ],
        'action' => [
            'save' => 'Save',
        ],
        'toast' => [
            'updated' => 'Password updated.',
        ],
    ],
    'two_factor' => [
        'heading' => 'Two-factor authentication',
        'description' => 'Manage your two-factor authentication settings',
        'disabled_description' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
        'enabled_description' => 'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
        'error' => [
            'qr_code' => 'Failed to fetch the QR code.',
            'setup_key' => 'Failed to fetch the setup key.',
            'recovery_codes' => 'Failed to fetch recovery codes.',
        ],
        'action' => [
            'enable' => 'Enable 2FA',
            'disable' => 'Disable 2FA',
            'continue_setup' => 'Continue setup',
        ],
        'setup' => [
            'create' => [
                'title' => 'Enable two-factor authentication',
                'description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
            ],
            'verify' => [
                'title' => 'Verify authentication code',
                'description' => 'Enter the 6-digit code from your authenticator app',
            ],
            'enabled' => [
                'title' => 'Two-factor authentication enabled',
                'description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            ],
            'manual_separator' => 'or, enter the code manually',
            'action' => [
                'continue' => 'Continue',
                'close' => 'Close',
                'back' => 'Back',
                'confirm' => 'Confirm',
            ],
        ],
        'recovery' => [
            'title' => '2FA recovery codes',
            'description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
            'instructions' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click :action above.',
            'action' => [
                'show' => 'View recovery codes',
                'hide' => 'Hide recovery codes',
                'regenerate' => 'Regenerate codes',
            ],
        ],
    ],
    'passkeys' => [
        'heading' => 'Passkeys',
        'description' => 'Manage your passkeys for passwordless sign-in',
        'count' => '{0} No passkeys|{1} One passkey|[2,*] :count passkeys',
        'unsupported' => 'Passkeys are not supported in this browser.',
        'empty' => [
            'title' => 'No passkeys yet',
            'description' => 'Add a passkey to sign in without a password',
        ],
        'form' => [
            'label' => 'Passkey name',
            'placeholder' => 'e.g., MacBook Pro, iPhone',
            'help' => 'A name helps you identify this passkey later.',
        ],
        'action' => [
            'add' => 'Add passkey',
            'register' => 'Register passkey',
            'registering' => 'Registering...',
        ],
        'item' => [
            'added' => 'Added :time',
            'last_used' => 'Last used :time',
            'remove_aria' => 'Remove',
        ],
        'remove' => [
            'title' => 'Remove passkey',
            'description' => 'Are you sure you want to remove the ":name" passkey? You will no longer be able to use it to sign in.',
            'action' => [
                'remove' => 'Remove passkey',
                'removing' => 'Removing...',
            ],
        ],
        'verify' => [
            'label' => 'Sign in with a passkey',
            'loading' => 'Authenticating...',
            'separator' => 'Or continue with email',
        ],
    ],
];
