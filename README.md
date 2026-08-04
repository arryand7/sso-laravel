# Sabira Connect (Gate SSO)

Sabira Connect adalah pusat identitas utama dan Single Sign-On (SSO) untuk ekosistem sekolah Sabira. Aplikasi ini menyediakan portal login, manajemen user dan aplikasi, **Application Access Management**, **User Synchronization API (Pull-Based)**, serta layanan OAuth2/OpenID Connect (OIDC) untuk aplikasi pihak ketiga yang terhubung (Smart, Laptop, SSS, Moodle, Progress, Absensi, dll).

---

## Fitur utama

- **SSO Berbasis OAuth2 + OpenID Connect**: `id_token` RS256, userinfo, OIDC discovery, dan JWKS endpoint.
- **Identitas Canonical & User UUID**: UUID v4 permanen (`gate_user_uuid`) sebagai identifier utama user lintas ekosistem aplikasi.
- **Application Access Management**: Relasi *many-to-many* explicit antara user dan aplikasi, mendukung `application_role` spesifik per aplikasi, status akses (`active`, `inactive`, `revoked`), serta pencatatan audit `granted_by`, `granted_at`, `revoked_by`, `revoked_at`.
- **Provisioning API & Pull-Based Synchronization**: API terisolasi berbasis kredensial aplikasi (`X-Client-Id` & `X-Client-Secret`) bagi aplikasi client untuk menarik data canonical user, signed photo temporary URL, dan melaporkan status sinkronisasi balik (`POST /api/provisioning/sync-results`).
- **Engine Rekonsiliasi 8 Kategori**: Menyediakan alur *dry-run preview* dan perbandingan 8 kategori sinkronisasi (*Matched, Needs Update, Missing in Application, Access Revoked, Inactive in Gate, Local Only, Conflict, Reactivation Required*).
- **Pengolahan Foto Profil & QR Code**: Foto diproses otomatis menjadi bingkai 4:3 tanpa crop/stretch dan dikompresi ≤ 300 KB. Setiap user dapat memiliki satu kode QR kartu anggota.
- **Import & Export User Excel**: Template Excel 3-sheet resmi (`Users`, `Instructions`, `References`), validasi komprehensif sebelum penulisan DB, penanganan kolom `password` opsional, dan laporan error XLSX.
- **Aksi Massal Admin**: Fitur *bulk actions* (Suspend, Nonaktifkan, Hapus Massal khusus Superadmin, Bulk Grant/Revoke Access).
- **Portal & Admin Panel**: Admin panel Blade berbasis Tailwind CSS untuk CRUD user, aplikasi, role mapping, log login, dan server settings.
- **Server Settings**: Pengaturan OAuth Google (dengan filter domain sekolah), SMTP email outgoing, dan dokumentasi API.

---

## Flow Aplikasi

### 1) Login Portal (Session)
1. User membuka `/login`.
2. Submit form ke `POST /login` (rate limit `login`).
3. Auth sukses -> update `last_login_at` + simpan `login_logs`.
4. Redirect ke `/dashboard` dan tampilkan daftar aplikasi yang berhak diakses.

### 2) OAuth2 / OIDC (SSO untuk Aplikasi Client)
1. Aplikasi pihak ketiga redirect ke `/oauth/authorize` dengan `client_id`, `redirect_uri`, `scope`, `state`, `response_type=code`.
2. `AuthorizeController` memvalidasi client dan redirect URI, lalu meneruskan ke Passport.
3. Aplikasi menukar authorization code ke `POST /oauth/token`.
4. Passport mengembalikan `access_token`, `refresh_token`, dan `id_token` (OIDC).
5. Aplikasi dapat memanggil `/oauth/userinfo` untuk mengambil klaim user.

### 3) Application Access Management (Admin Gate)
1. Admin membuka detail user (`/admin/users/{user}`) atau detail aplikasi (`/admin/applications/{application}`).
2. Admin memberikan akses ke aplikasi, menentukan `application_role` (opsional), dan mengaktifkan akses.
3. Hak akses langsung tersimpan di tabel `user_application_accesses` dengan riwayat `granted_by` / `granted_at`.
4. Jika akses dicabut, status diubah menjadi `revoked` tanpa menghapus data historis user.

### 4) Pull-Based Synchronization (Aplikasi Client)
1. Superadmin aplikasi client (misal Smart/Laptop) menekan **"Sync Users from Gate"**.
2. Aplikasi client memanggil `GET /api/provisioning/users` menggunakan `X-Client-Id` & `X-Client-Secret`.
3. Aplikasi client membandingkan data Gate dengan data lokal dalam alur *dry-run preview* (8 kategori).
4. Superadmin meninjau laporan perbandingan dan mengonfirmasi **"Apply Synchronization"**.
5. Aplikasi client memperbarui DB lokal (create/update/suspend/reactivate).
6. Aplikasi client mengirimkan hasil ke `POST /api/provisioning/sync-results` untuk memperbarui status sync di Gate.

---

## Endpoint Penting

| Endpoint | Keterangan | Auth |
| :--- | :--- | :--- |
| `/login` | Form login portal | Guest |
| `/dashboard` | Portal user SSO | Session |
| `/admin` | Admin panel Gate SSO | Session + role admin |
| `/.well-known/openid-configuration` | OIDC discovery metadata | Public |
| `/.well-known/jwks.json` | JSON Web Key Set | Public |
| `/oauth/authorize` | Authorization endpoint | Session |
| `/oauth/token` | Token exchange endpoint | Public (rate limited) |
| `/oauth/userinfo` | OIDC UserInfo endpoint | Bearer Token |
| `/api/provisioning/me` | Status & capabilities aplikasi client | App Credential (`X-Client-Id`/`Secret`) |
| `/api/provisioning/users` | Daftar canonical active users | App Credential (`X-Client-Id`/`Secret`) |
| `/api/provisioning/users/{uuid}` | Detail 1 canonical user | App Credential (`X-Client-Id`/`Secret`) |
| `/api/provisioning/changes` | User berubah sejak `?since=...` | App Credential (`X-Client-Id`/`Secret`) |
| `/api/provisioning/photo/{user}` | Download foto profil sementara | Temporary Signed URL |
| `/api/provisioning/sync-results` | Pelaporan hasil sinkronisasi client | App Credential (`X-Client-Id`/`Secret`) |

---

## Data Model Ringkas

- `users`: `uuid` (UUID v4), `username`, `email`, `type` (*student/teacher/parent/staff/admin*), `nis`, `nip`, `status` (*active/suspended/pending*), `photo_path`, `qr_code`, `last_login_at`.
- `applications`: `name`, `slug`, `client_id`, `client_secret`, `base_url`, `redirect_uri`, `sso_login_url`, `is_active`, `sync_enabled`, `sync_capabilities` (JSON), `api_rate_limit`.
- `user_application_accesses`: `user_id`, `application_id`, `application_role`, `status` (*active/inactive/revoked*), `granted_at`, `granted_by`, `revoked_at`, `revoked_by`, `last_synced_at`.
- `application_user_sync_statuses`: `user_id`, `application_id`, `status` (*never_synced/matched/needs_update/missing_in_application/suspended/conflict/failed*), `external_user_id`, `last_sync_at`, `last_reported_at`, `error_code`, `error_message`, `local_checksum`, `gate_checksum`.
- `roles`, `model_has_roles`, `role_has_permissions`: Spatie permissions.
- `login_logs`: Audit log login user.
- `settings`: Konfigurasi terenkripsi (OAuth Google, SMTP).

### Provisioning identity contract (schema 1.0, additive)

Field identity berikut ditambahkan secara opsional tanpa menghapus field schema 1.0 sebelumnya. Hanya user aktif dengan `user_application_accesses.status=active` untuk aplikasi pemanggil yang dikirim. `email_verified` berasal langsung dari keberadaan `users.email_verified_at`; client tidak boleh menyimpulkannya dari email. `legacy_oidc_subject` adalah representasi string dari primary key `users.id`, sumber yang sama dengan claim `sub` pada implementasi OIDC Gate saat ini. Nilai tersebut read-only dan tidak menggantikan UUID utama. NIS belum memiliki unique constraint di schema; duplicate NIS adalah conflict review, bukan alasan untuk memakai nama sebagai identity match.

Contoh response (data sintetis):

```json
{
  "schema_version": "1.0",
  "total_users": 1,
  "users": [{
    "uuid": "86d79fe3-9d74-4b4c-9859-c9ca0553d19e",
    "gate_user_uuid": "86d79fe3-9d74-4b4c-9859-c9ca0553d19e",
    "username": "student-example",
    "name": "Example Student",
    "email": "student@example.test",
    "email_verified": true,
    "type": "student",
    "user_type": "student",
    "nis": "EXAMPLE-001",
    "nip": null,
    "legacy_oidc_subject": "123",
    "status": "active",
    "application_access": {
      "status": "active",
      "role": "santri",
      "granted_at": "2026-07-30T10:00:00+07:00",
      "last_synced_at": null
    }
  }]
}
```

Administrator dapat menjalankan preview read-only tanpa membuat assignment:

```bash
php artisan gate:preview-application-population smart
```

Preview menghitung assignment aktif, tipe user, ketersediaan UUID/NIS/verified email/legacy subject, user inactive, identity incomplete, dan duplicate identity groups.

---

## Setup dan Running

### Prasyarat
- PHP 8.3+ (disarankan PHP 8.4)
- Composer & Node.js
- SQLite atau MySQL

### Langkah Cepat
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan passport:install
php artisan storage:link
npm install
npm run build
php artisan serve
```

### Jalankan Testing & Code Style
```bash
# Menjalankan PHPUnit & Pint Formatter
vendor/bin/pint && ./vendor/bin/phpunit -d memory_limit=512M
```

---

## Dokumentasi Tambahan

- **Kontrak dan Pemetaan User Gate**: [`docs/gate-user-mapping.md`](docs/gate-user-mapping.md) — Referensi field, enum canonical, mapping schema legacy, validasi preflight, dan penanganan error enum database client.
- **Ketentuan Integrasi Aplikasi Client**: [`SYNC-APP-CLIENT.md`](SYNC-APP-CLIENT.md) — Panduan lengkap ketentuan & langkah pengembangan bagi pengembang aplikasi client (Smart, Laptop, SSS, Moodle) agar dapat menggunakan fitur sinkronisasi ini.
- **Panduan Teknis API Provisioning**: [connector_integration_guide.md](file:///Users/ryand/.gemini/antigravity-ide/brain/ceb48790-fc07-4194-96a4-c1738b04dbc2/connector_integration_guide.md)
- **Dokumentasi Walkthrough**: [walkthrough.md](file:///Users/ryand/.gemini/antigravity-ide/brain/ceb48790-fc07-4194-96a4-c1738b04dbc2/walkthrough.md)

---

## Lisensi & Kredit

Internal project untuk ekosistem sekolah Sabira. Dibuat oleh **Ryand Arifriantoni** (`arryand7@gmail.com`).
