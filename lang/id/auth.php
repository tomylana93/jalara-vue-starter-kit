<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    */

    'failed' => 'Kredensial ini tidak cocok dengan data kami.',
    'password' => 'Kata sandi yang diberikan salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik.',

    'login' => [
        'title' => 'Masuk',
        'card' => [
            'heading' => 'Selamat datang kembali!',
            'description' => 'Silakan masukkan kredensial Anda untuk masuk.',
        ],
        'label' => [
            'email' => 'Alamat email',
            'password' => 'Kata sandi',
            'remember' => 'Ingat saya',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
            'password' => 'Kata sandi',
        ],
        'action' => [
            'submit' => 'Masuk',
        ],
        'link' => [
            'forgot' => 'Lupa kata sandi Anda?',
        ],
    ],

    'forgot_password' => [
        'title' => 'Lupa kata sandi',
        'card' => [
            'heading' => 'Atur ulang kata sandi Anda',
            'description' => 'Masukkan alamat email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.',
        ],
        'label' => [
            'email' => 'Alamat email',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
        ],
        'action' => [
            'submit' => 'Kirim tautan reset kata sandi',
        ],
        'prompt' => 'Atau, kembali ke',
        'link' => [
            'login' => 'masuk',
        ],
    ],

    'reset_password' => [
        'title' => 'Atur ulang kata sandi',
        'card' => [
            'heading' => 'Atur ulang kata sandi Anda',
            'description' => 'Silakan masukkan kata sandi baru Anda di bawah ini.',
        ],
        'label' => [
            'email' => 'Email',
            'password' => 'Kata sandi',
            'password_confirmation' => 'Konfirmasi kata sandi',
        ],
        'placeholder' => [
            'password' => 'Kata sandi',
            'password_confirmation' => 'Konfirmasi kata sandi',
        ],
        'action' => [
            'submit' => 'Atur ulang kata sandi',
        ],
    ],

    'confirm_password' => [
        'title' => 'Konfirmasi kata sandi',
        'card' => [
            'heading' => 'Konfirmasi kata sandi Anda',
            'description' => 'Ini adalah area aman pada aplikasi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.',
        ],
        'label' => [
            'password' => 'Kata sandi',
        ],
        'action' => [
            'submit' => 'Konfirmasi kata sandi',
        ],
    ],

];
