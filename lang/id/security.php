<?php

return [
    'password' => [
        'heading' => 'Perbarui kata sandi',
        'description' => 'Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman',
        'label' => [
            'current' => 'Kata sandi saat ini',
            'new' => 'Kata sandi baru',
            'confirm' => 'Konfirmasi kata sandi',
        ],
        'placeholder' => [
            'current' => 'Kata sandi saat ini',
            'new' => 'Kata sandi baru',
            'confirm' => 'Konfirmasi kata sandi',
        ],
        'action' => [
            'save' => 'Simpan',
        ],
        'toast' => [
            'updated' => 'Kata sandi diperbarui.',
        ],
    ],
    'two_factor' => [
        'heading' => 'Autentikasi dua faktor',
        'description' => 'Kelola pengaturan autentikasi dua faktor Anda',
        'disabled_description' => 'Saat Anda mengaktifkan autentikasi dua faktor, Anda akan diminta memasukkan pin aman saat masuk. Pin ini dapat diperoleh dari aplikasi yang mendukung TOTP di ponsel Anda.',
        'enabled_description' => 'Anda akan diminta memasukkan pin aman dan acak saat masuk, yang dapat Anda peroleh dari aplikasi yang mendukung TOTP di ponsel Anda.',
        'error' => [
            'qr_code' => 'Gagal mengambil kode QR.',
            'setup_key' => 'Gagal mengambil kunci penyiapan.',
            'recovery_codes' => 'Gagal mengambil kode pemulihan.',
        ],
        'action' => [
            'enable' => 'Aktifkan 2FA',
            'disable' => 'Nonaktifkan 2FA',
            'continue_setup' => 'Lanjutkan penyiapan',
        ],
        'setup' => [
            'create' => [
                'title' => 'Aktifkan autentikasi dua faktor',
                'description' => 'Untuk menyelesaikan pengaktifan autentikasi dua faktor, pindai kode QR atau masukkan kunci penyiapan di aplikasi autentikator Anda',
            ],
            'verify' => [
                'title' => 'Verifikasi kode autentikasi',
                'description' => 'Masukkan kode 6 digit dari aplikasi autentikator Anda',
            ],
            'enabled' => [
                'title' => 'Autentikasi dua faktor diaktifkan',
                'description' => 'Autentikasi dua faktor kini aktif. Pindai kode QR atau masukkan kunci penyiapan di aplikasi autentikator Anda.',
            ],
            'manual_separator' => 'atau, masukkan kode secara manual',
            'action' => [
                'continue' => 'Lanjutkan',
                'close' => 'Tutup',
                'back' => 'Kembali',
                'confirm' => 'Konfirmasi',
            ],
        ],
        'recovery' => [
            'title' => 'Kode pemulihan 2FA',
            'description' => 'Kode pemulihan memungkinkan Anda memperoleh kembali akses jika kehilangan perangkat 2FA. Simpan di pengelola kata sandi yang aman.',
            'instructions' => 'Setiap kode pemulihan dapat digunakan sekali untuk mengakses akun Anda dan akan dihapus setelah digunakan. Jika perlu lebih banyak, klik :action di atas.',
            'action' => [
                'show' => 'Lihat kode pemulihan',
                'hide' => 'Sembunyikan kode pemulihan',
                'regenerate' => 'Buat ulang kode',
            ],
        ],
    ],
    'passkeys' => [
        'heading' => 'Passkey',
        'description' => 'Kelola passkey Anda untuk masuk tanpa kata sandi',
        'count' => '{0} Tidak ada passkey|{1} Satu passkey|[2,*] :count passkey',
        'unsupported' => 'Passkey tidak didukung di peramban ini.',
        'empty' => [
            'title' => 'Belum ada passkey',
            'description' => 'Tambahkan passkey untuk masuk tanpa kata sandi',
        ],
        'form' => [
            'label' => 'Nama passkey',
            'placeholder' => 'mis., MacBook Pro, iPhone',
            'help' => 'Nama membantu Anda mengenali passkey ini nanti.',
        ],
        'action' => [
            'add' => 'Tambah passkey',
            'register' => 'Daftarkan passkey',
            'registering' => 'Mendaftarkan...',
        ],
        'item' => [
            'added' => 'Ditambahkan :time',
            'last_used' => 'Terakhir digunakan :time',
            'remove_aria' => 'Hapus',
        ],
        'remove' => [
            'title' => 'Hapus passkey',
            'description' => 'Apakah Anda yakin ingin menghapus passkey ":name"? Anda tidak akan dapat menggunakannya lagi untuk masuk.',
            'action' => [
                'remove' => 'Hapus passkey',
                'removing' => 'Menghapus...',
            ],
        ],
        'verify' => [
            'label' => 'Masuk dengan passkey',
            'loading' => 'Mengautentikasi...',
            'separator' => 'Atau lanjutkan dengan email',
        ],
    ],
];
