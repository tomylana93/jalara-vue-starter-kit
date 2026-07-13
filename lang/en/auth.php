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

    'login' => [
        'title' => 'Log in',
        'card' => [
            'heading' => 'Welcome back!',
            'description' => 'Please enter your credentials to log in.',
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
            'heading' => 'Reset your password',
            'description' => 'Enter your email address and we will send you a link to reset your password.',
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
            'heading' => 'Reset your password',
            'description' => 'Please enter your new password below.',
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
            'heading' => 'Confirm your password',
            'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        ],
        'label' => [
            'password' => 'Password',
        ],
        'action' => [
            'submit' => 'Confirm password',
        ],
    ],

];
