<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'common' => [
        'action' => [
            'continue' => 'Continue',
            'logout' => 'Log out',
        ],
        'aria' => [
            'otp' => 'One-time authentication code',
            'recovery_code' => 'Recovery code',
        ],
    ],

    'login' => [
        'title' => 'Log in',
        'card' => [
            'heading' => 'Log in to your account',
            'description' => 'Enter your email and password below to log in',
        ],
        'label' => [
            'email' => 'Email address',
            'password' => 'Password',
            'remember' => 'Remember me',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
            'password' => 'Password',
        ],
        'action' => [
            'submit' => 'Log in',
        ],
        'link' => [
            'forgot' => 'Forgot your password?',
        ],
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'card' => [
            'heading' => 'Forgot password',
            'description' => 'Enter your email to receive a password reset link',
        ],
        'label' => [
            'email' => 'Email address',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
        ],
        'action' => [
            'submit' => 'Email password reset link',
        ],
        'prompt' => 'Or, return to',
        'link' => [
            'login' => 'log in',
        ],
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'card' => [
            'heading' => 'Reset password',
            'description' => 'Please enter your new password below',
        ],
        'label' => [
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Confirm password',
        ],
        'placeholder' => [
            'password' => 'Password',
            'password_confirmation' => 'Confirm password',
        ],
        'action' => [
            'submit' => 'Reset password',
        ],
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'card' => [
            'heading' => 'Confirm password',
            'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        ],
        'label' => [
            'password' => 'Password',
        ],
        'action' => [
            'submit' => 'Confirm password',
        ],
        'passkey' => [
            'label' => 'Confirm with passkey',
            'loading' => 'Confirming...',
            'separator' => 'Or confirm with password',
        ],
    ],

    'verify_email' => [
        'title' => 'Email verification',
        'card' => [
            'heading' => 'Email verification',
            'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        ],
        'resent' => 'A new verification link has been sent to the email address you provided during registration.',
        'action' => [
            'resend' => 'Resend verification email',
        ],
    ],

    'two_factor_challenge' => [
        'title' => 'Two-factor authentication',
        'prompt' => 'or you can',
        'authentication_code' => [
            'title' => 'Authentication code',
            'description' => 'Enter the authentication code provided by your authenticator application.',
            'toggle' => 'login using a recovery code',
        ],
        'recovery_code' => [
            'title' => 'Recovery code',
            'description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
            'toggle' => 'login using an authentication code',
            'placeholder' => 'Enter recovery code',
        ],
    ],

];
