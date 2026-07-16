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

    'common' => [
        'action' => [
            'continue' => 'Lanjutkan',
            'logout' => 'Keluar',
        ],
        'aria' => [
            'otp' => 'Kode autentikasi sekali pakai',
            'recovery_code' => 'Kode pemulihan',
        ],
    ],

    'login' => [
        'title' => 'Masuk',
        'card' => [
            'heading' => 'Masuk ke akun Anda',
            'description' => 'Masukkan email dan kata sandi Anda di bawah ini untuk masuk',
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
            'heading' => 'Lupa kata sandi',
            'description' => 'Masukkan email Anda untuk menerima tautan reset kata sandi',
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
            'heading' => 'Atur ulang kata sandi',
            'description' => 'Silakan masukkan kata sandi baru Anda di bawah ini',
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
            'heading' => 'Konfirmasi kata sandi',
            'description' => 'Ini adalah area aman pada aplikasi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.',
        ],
        'label' => [
            'password' => 'Kata sandi',
        ],
        'action' => [
            'submit' => 'Konfirmasi kata sandi',
        ],
        'passkey' => [
            'label' => 'Konfirmasi dengan passkey',
            'loading' => 'Mengonfirmasi...',
            'separator' => 'Atau konfirmasi dengan kata sandi',
        ],
    ],

    'verify_email' => [
        'title' => 'Verifikasi email',
        'card' => [
            'heading' => 'Verifikasi email',
            'description' => 'Silakan verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan kepada Anda.',
        ],
        'resent' => 'Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.',
        'action' => [
            'resend' => 'Kirim ulang email verifikasi',
        ],
    ],

    'two_factor_challenge' => [
        'title' => 'Autentikasi dua faktor',
        'prompt' => 'atau Anda bisa',
        'authentication_code' => [
            'title' => 'Kode autentikasi',
            'description' => 'Masukkan kode autentikasi yang diberikan oleh aplikasi autentikator Anda.',
            'toggle' => 'masuk menggunakan kode pemulihan',
        ],
        'recovery_code' => [
            'title' => 'Kode pemulihan',
            'description' => 'Silakan konfirmasi akses ke akun Anda dengan memasukkan salah satu kode pemulihan darurat Anda.',
            'toggle' => 'masuk menggunakan kode autentikasi',
            'placeholder' => 'Masukkan kode pemulihan',
        ],
    ],

];
