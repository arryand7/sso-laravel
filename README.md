# Sabira Connect (Gate SSO)

Sabira Connect adalah aplikasi Single Sign-On (SSO) untuk ekosistem sekolah. Aplikasi ini menyediakan portal login, manajemen user dan aplikasi, serta layanan OAuth2/OIDC untuk aplikasi pihak ketiga (LMS, absensi, laptop, dan lain-lain).

## Fitur utama

- SSO berbasis OAuth2 + OpenID Connect (id_token RS256, userinfo, well-known).
- Portal pengguna untuk akses aplikasi sekolah.
- Admin panel untuk CRUD user, aplikasi, mapping role, dan log login.
- Sinkron otomatis aplikasi ke Passport client.
- Server settings untuk OAuth services (Google, Facebook), outgoing mail, dan dokumentasi API.
- Login Google (manual OAuth flow) dengan filter domain sekolah.
- Rate limiting untuk endpoint /login dan /oauth/token.

## Flow aplikasi

### 1) Login portal (session)
1. User membuka `/login`.
2. Submit form ke `POST /login` (rate limit `login`).
3. Auth sukses -> update `last_login_at` + simpan `login_logs`.
4. Redirect ke `/dashboard` dan tampilkan aplikasi sesuai role.

### 2) OAuth2/OIDC (SSO untuk aplikasi)
1. Aplikasi pihak ketiga redirect ke `/oauth/authorize` dengan `client_id`, `redirect_uri`, `scope`, `state`, `response_type=code`.
2. `AuthorizeController` memvalidasi client dan redirect URI, lalu meneruskan ke Passport.
3. Aplikasi menukar code ke `POST /oauth/token`.
4. `TokenController` mengembalikan `access_token`, `refresh_token`, dan `id_token` (OIDC).
5. Aplikasi dapat memanggil `/oauth/userinfo` untuk mengambil klaim user.

### 3) Google OAuth (opsional)
1. User klik "Login dengan Google" pada halaman login.
2. `GET /auth/google` redirect ke Google OAuth.
3. Callback ke `/auth/google/callback`.
4. Domain email divalidasi sesuai setting admin.
5. User lokal ditemukan, session login dibuat, lalu redirect ke dashboard.

### 4) Admin flow
- `/admin` untuk dashboard admin.
- CRUD user, aplikasi, role mapping, login log.
- `/admin/server` untuk pengaturan OAuth, SMTP, dan Web Services (superadmin saja).

## Endpoint penting

| Endpoint | Keterangan | Auth |
| --- | --- | --- |
| `/login` | Form login | Guest |
| `/dashboard` | Portal user | Session |
| `/admin` | Admin panel | Session + role admin |
| `/.well-known/openid-configuration` | OIDC discovery | Public |
| `/.well-known/jwks.json` | JWKS | Public |
| `/oauth/authorize` | Authorization endpoint | Session |
| `/oauth/token` | Token endpoint | Public (rate limited) |
| `/oauth/userinfo` | Userinfo | Bearer token |
| `/auth/google` | Google OAuth redirect | Guest |
| `/auth/google/callback` | Google OAuth callback | Guest |

## Struktur kode

- `app/Http/Controllers/Auth`  
  Login, logout, password reset, dan social auth.
- `app/Http/Controllers/Portal`  
  Dashboard dan profile user.
- `app/Http/Controllers/Admin`  
  User, aplikasi, role, login log, dan server settings.
- `app/Http/Controllers/OAuth`  
  Authorize, token, userinfo, well-known.
- `app/Models`  
  `User`, `Application`, `LoginLog`, `Setting`, dan `PassportClient`.
- `app/Services/OidcTokenService`  
  Penerbitan id_token RS256.
- `routes/web.php`  
  Semua route web + OAuth endpoints.
- `resources/views`  
  Blade templates untuk portal, admin, dan auth pages.

## Data model ringkas

- `users`  
  Field penting: `username`, `email`, `type`, `nis`, `nip`, `status`, `last_login_at`.
- `roles`, `model_has_roles`, `role_has_permissions`  
  Spatie permission untuk role access.
- `applications`  
  Data aplikasi + OAuth client.
- `application_role`  
  Mapping role ke aplikasi.
- `login_logs`  
  Audit login user.
- `settings`  
  Konfigurasi server (OAuth, email, web).
- `oauth_*`  
  Tabel Passport untuk token dan client.

## Style dan UI

- Framework UI: Tailwind CSS via CDN.
- Font utama: Inter (Google Fonts).
- Icon: Material Symbols Outlined.
- Token warna ada di layout:
  - `primary`, `primary-hover`
  - `background-light`, `background-dark`
  - `surface-light`, `surface-dark`
  - `border-light`, `border-dark`
- Layouts:
  - `resources/views/layouts/guest.blade.php` untuk login dan auth.
  - `resources/views/layouts/app.blade.php` untuk portal user.
  - `resources/views/layouts/admin.blade.php` untuk admin panel.
- Dark mode: menggunakan class `dark` pada `<html>`. Default `light`.

## Setup dan running

### Prasyarat
- PHP 8.3+ (disarankan PHP 8.4)
- Composer
- SQLite atau MySQL

### Langkah cepat
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan passport:install
php artisan serve
```

Catatan:
- Untuk OIDC token, pastikan `passport:install` atau `passport:keys` sudah dijalankan.
- DB default menggunakan SQLite, sesuaikan `.env` jika menggunakan MySQL.

## Akun default hasil seed

- Superadmin  
  `username: superadmin`  
  `password: password`
- Admin  
  `username: admin`  
  `password: password`
- Demo user  
  `teacher001`, `student001`, `parent001`, `staff001` (password: `password`)

## Konfigurasi OAuth Google

1. Buka `Admin > Server`.
2. Aktifkan Google OAuth dan isi:
   - Client ID
   - Client Secret
   - Redirect URI (`/auth/google/callback`)
   - Allowed domains (contoh: `sabira-iibs.id`, bisa lebih dari satu, pisahkan koma)
3. User login dengan Google akan ditolak jika domain tidak sesuai.

## Konfigurasi Email Outgoing

1. Buka `Admin > Server`.
2. Isi SMTP host, port, username, password.
3. Scheme gunakan `smtp` atau `smtps` (untuk SSL).
4. Gunakan fitur "Test Send" untuk verifikasi.

## Catatan pengembangan

- `Application::syncPassportClient()` memastikan data aplikasi selalu sinkron dengan tabel Passport.
- `TokenController` menambah `id_token` untuk kebutuhan OIDC.
- Rate limiting sudah disiapkan di `AppServiceProvider`.

## Lisensi

Internal project untuk ekosistem sekolah Sabira.
