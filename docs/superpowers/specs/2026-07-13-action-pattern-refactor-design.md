# Action Pattern Refactor Design

## Goal

Menetapkan standar action pattern untuk business operation Laravel tanpa mengubah route contract atau perilaku HTTP yang sudah berjalan.

## Scope

Dokumen ini hanya menetapkan arsitektur dan rencana migrasi. Implementasi awal berfokus pada use case Settings yang sudah memiliki business mutation logic di controller:

- Update profile user.
- Delete account user.
- Update password user.

Fortify tetap menggunakan boundary yang sudah ada di `app/Actions/Fortify`. Migrasi action Fortify bukan bagian dari refactor massal ini.

## Architecture

Setiap business use case direpresentasikan oleh satu class action dengan method publik `handle()`. Action tidak menggunakan `__invoke()`, tidak mewarisi `BaseAction`, dan tidak bergantung pada HTTP request.

Action aplikasi berada di `app/Actions`:

```text
app/Actions/
├── Fortify/
│   └── ResetUserPassword.php
├── UpdateUserProfile.php
├── DeleteUserAccount.php
└── UpdateUserPassword.php
```

Controller tetap menjadi adapter HTTP. Controller menerima Form Request, memanggil action melalui dependency injection, lalu mengatur Inertia response, redirect, flash message, logout, dan session lifecycle.

Form Request tetap bertanggung jawab atas validation dan authorization. Model tetap bertanggung jawab atas relasi, cast, accessor/mutator, serta aturan yang melekat pada entity. Tidak dibuat service generik yang menampung banyak use case.

## Action Contract

Setiap action wajib memiliki:

- Nama berbentuk verba + objek yang menjelaskan satu use case.
- Method `handle()` sebagai entry point.
- Type declaration eksplisit untuk parameter dan return value.
- Input berupa model dan data tervalidasi, bukan `Request` atau `FormRequest`.
- Dokumentasi PHPDoc untuk array shape jika input masih berupa array.
- Return value yang konsisten dengan kebutuhan use case, yaitu model yang berubah atau `void`.

Contoh bentuk action:

```php
final class UpdateUserProfile
{
    /**
     * @param array{name: string, email: string} $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        // business operation
    }
}
```

Action hanya menjalankan business operation. Action tidak mengembalikan redirect, response Inertia, flash message, atau memanipulasi session HTTP.

## Data Flow and Error Handling

Alur mutasi standar:

1. Request masuk melalui route dan Form Request.
2. Form Request memvalidasi input dan authorization.
3. Controller mengirim user dan data tervalidasi ke `handle()`.
4. Action menjalankan perubahan state dan mengembalikan hasil domain.
5. Controller membuat flash message dan response HTTP yang sama seperti sebelumnya.

Action tidak menangkap exception secara umum. Exception validation dan database diteruskan ke exception handler Laravel. `DB::transaction()` hanya digunakan ketika satu use case memiliki beberapa perubahan state yang harus atomic.

`DeleteUserAccount` hanya bertanggung jawab menghapus user. Logout, invalidasi session, dan regenerasi CSRF token tetap berada di controller karena merupakan lifecycle autentikasi HTTP.

## Testing Strategy

Unit test action ditempatkan berdasarkan domain dan use case:

```text
tests/Unit/
└── Domain/
    └── Auth/
        └── Actions/
            ├── UpdateUserProfileTest.php
            ├── DeleteUserAccountTest.php
            └── UpdateUserPasswordTest.php
```

Feature test tetap berada berdasarkan endpoint:

```text
tests/Feature/Settings/
├── ProfileUpdateTest.php
└── SecurityTest.php
```

Unit test memverifikasi business rule action secara langsung. Feature test mempertahankan coverage route, middleware, validation, redirect, flash message, logout, dan Inertia response.

## Migration Strategy

Migrasi dilakukan bertahap:

1. Tambahkan unit test untuk action.
2. Buat action dengan method `handle()`.
3. Pindahkan business mutation logic dari controller ke action.
4. Inject action ke controller.
5. Pertahankan response HTTP dan route name yang ada.
6. Jalankan unit test, feature test terkait, Pint, dan quality checks yang relevan.

Refactor tidak mengubah dependency proyek, endpoint, payload frontend, route name, middleware, atau perilaku pengguna.

## Completion Criteria

- Mutation logic Settings tidak lagi berada langsung di controller.
- Semua action baru menggunakan `handle()`.
- Unit test action berada di `tests/Unit/Domain/Auth/Actions`.
- Feature test endpoint tetap berada di `tests/Feature/Settings`.
- Route contract dan perilaku HTTP tetap kompatibel.
- Test terkait dan formatter PHP lulus.
- Tidak ada `BaseAction`, service generik, atau dependency baru.
