# Kontrak dan Pemetaan User Gate untuk Aplikasi Client

Dokumen ini adalah acuan normatif untuk aplikasi client yang menarik user dari Provisioning API Sabira Connect (Gate SSO). Tujuannya adalah mencegah kegagalan sinkronisasi akibat nama kolom, enum, dan arti field lokal yang tidak sama dengan kontrak Gate.

Kontrak yang berlaku saat ini: `schema_version = "1.0"`.

## Diagnosis error `Data truncated for column 'role'`

Contoh kegagalan:

```text
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'role'
...
type = student, role = student, application_role = null, status = aktif
```

Penyebabnya bukan UUID atau koneksi API. Client menyalin `type = student` ke kolom lokal `role`, sedangkan enum `users.role` milik client tidak menyediakan nilai `student`.

Aturan yang harus diterapkan:

1. `type` dan `role` adalah dua konsep berbeda.
2. API Gate tidak mengirim field top-level `role`.
3. Role khusus aplikasi hanya tersedia pada `application_access.role` dan dapat bernilai `null`. Key `role` tetap ada, tetapi hanya dapat bernilai non-null bila capability `sync_role` aktif.
4. Jangan memasukkan `type` atau `application_access.role` langsung ke enum lokal. Nilai harus melewati mapping dan allowlist milik client.
5. Jika mapping tidak ditemukan, masukkan user ke kategori `conflict`/`failed`; jangan melakukan insert parsial.

Untuk kasus Lara Absensi pada contoh di atas:

- Jika `users.role` adalah role otorisasi lokal, jangan isi dari `type`; gunakan mapping dari `application_access.role` atau biarkan null jika schema mengizinkan.
- Jika `users.role` adalah nama lain dari tipe identitas, set `role_source = type` dan petakan `student` ke salah satu nilai yang benar-benar ada pada enum lokal, misalnya `siswa` hanya bila enum client memang memiliki nilai tersebut.
- Nilai `status = aktif` adalah hasil mapping lokal, bukan nilai dari Gate. Gunakan itu hanya bila enum client menerima `aktif`; jika storage client mengikuti kontrak canonical, simpan `active`.
- Jalankan preflight enum sebelum apply. Jangan memperbaiki masalah dengan menonaktifkan MySQL strict mode karena itu dapat menyimpan nilai kosong/terpotong tanpa terlihat.

## Sumber data resmi

Client mengambil kontrak dari endpoint berikut:

| Endpoint | Fungsi |
| --- | --- |
| `GET /api/provisioning/me` | Versi schema, identitas aplikasi, dan capabilities |
| `GET /api/provisioning/users` | Seluruh user aktif yang mempunyai akses aktif ke aplikasi pemanggil |
| `GET /api/provisioning/users/{uuid}` | Satu user berdasarkan UUID Gate |
| `GET /api/provisioning/changes?since=<ISO-8601>` | User aktif yang berubah sejak waktu tertentu |
| `POST /api/provisioning/sync-results` | Laporan hasil sinkronisasi dari client ke Gate |

Header provisioning wajib:

```http
X-Client-Id: <CLIENT_ID>
X-Client-Secret: <CLIENT_SECRET>
Accept: application/json
```

Client wajib memeriksa `schema_version` sebelum memproses data. Perubahan minor dalam schema 1.x bersifat additive, sehingga client harus mengabaikan field baru yang belum dikenali dan tidak boleh gagal hanya karena ada field tambahan.

## Bentuk payload canonical user

Contoh data sintetis:

```json
{
  "schema_version": "1.0",
  "application": {
    "uuid": "client-id-aplikasi",
    "slug": "lara-absensi",
    "name": "Lara Absensi"
  },
  "generated_at": "2026-08-04T14:14:45+07:00",
  "total_users": 1,
  "users": [
    {
      "uuid": "34b3206d-bb18-4d63-9dbc-66b0d0e26762",
      "gate_user_uuid": "34b3206d-bb18-4d63-9dbc-66b0d0e26762",
      "username": "250001",
      "name": "Example Student",
      "email": "student@example.test",
      "email_verified": true,
      "type": "student",
      "user_type": "student",
      "nis": "250001",
      "nip": null,
      "legacy_oidc_subject": "123",
      "status": "active",
      "application_access": {
        "status": "active",
        "role": null,
        "granted_at": "2026-08-04T13:00:00+07:00",
        "last_synced_at": null
      },
      "updated_at": "2026-08-04T13:30:00+07:00",
      "photo": {
        "available": false,
        "url": null,
        "checksum": null
      }
    }
  ]
}
```

## Pemetaan field

| Field Gate | Tipe/nilai | Kolom client yang disarankan | Aturan |
| --- | --- | --- | --- |
| `gate_user_uuid` | UUID v4 | `gate_user_uuid` UUID/VARCHAR(36), unique | Identifier lintas aplikasi utama. Wajib disimpan tanpa perubahan. |
| `uuid` | UUID v4 | Tidak perlu kolom kedua | Alias kompatibilitas dari `gate_user_uuid`; nilainya sama. |
| `legacy_oidc_subject` | String angka | Opsional, read-only | Claim OIDC `sub` lama. Bukan pengganti UUID. |
| `name` | String | `name` | Data identitas canonical. |
| `username` | String | `username` | Boleh digunakan untuk login/display lokal, bukan relasi permanen. |
| `email` | String/null | `email` nullable | Jangan menganggap selalu terisi. |
| `email_verified` | Boolean | `email_verified_at`/boolean lokal | `true` berarti email telah diverifikasi di Gate. |
| `type` | Enum canonical | `type` | Simpan sebagai tipe identitas, jangan otomatis disalin ke `role`. |
| `user_type` | Enum canonical | Tidak perlu kolom kedua | Alias kompatibilitas dari `type`. |
| `nis` | String/null | `nis` nullable | Umumnya untuk student; pertahankan leading zero. |
| `nip` | String/null | `nip` nullable | Umumnya untuk teacher/staff; pertahankan sebagai string. |
| `status` | Enum canonical | `status` atau hasil mapping status | Status akun utama di Gate. |
| `application_access.status` | Enum akses | Kolom akses lokal | Berbeda dari status akun utama. |
| `application_access.role` | String/null | `application_role` VARCHAR nullable | Role khusus untuk aplikasi pemanggil. Nilainya bukan enum global dan dapat null. |
| `application_access.granted_at` | ISO-8601/null | Timestamp opsional | Waktu pemberian akses. |
| `application_access.last_synced_at` | ISO-8601/null | Timestamp opsional | Informasi observabilitas, bukan identity key. |
| `updated_at` | ISO-8601 | Timestamp sync/checksum | Dipakai untuk incremental sync. |
| `photo` | Object | Kolom foto/checksum lokal | Unduh hanya melalui signed URL dan bila capability aktif. |
| `qr_code` | String/null, conditional | Kolom QR lokal | Hanya ada bila capability `sync_qr` aktif. |

Field berikut bukan bagian dari payload user Gate dan tidak boleh diharapkan dari API:

- `role` top-level;
- `auth_source`;
- `password` atau password hash;
- permission/role framework lokal;
- data domain seperti kelas, shift, saldo, nilai, atau histori absensi.

`auth_source = gate` boleh dibuat sebagai konstanta oleh client. Password lokal boleh `null` atau random unusable hash sesuai schema client, tetapi tidak boleh dianggap sebagai password Gate. Login tetap menggunakan OAuth2/OIDC.

## Enum resmi Gate

### `type` / `user_type`

```text
student
teacher
parent
staff
admin
```

### Status akun `status`

```text
active
suspended
pending
```

Saat ini endpoint daftar provisioning hanya mengembalikan user dengan status akun `active` dan assignment aplikasi `active`. Client tetap harus memahami nilai lain untuk rekonsiliasi, kompatibilitas, atau perubahan kontrak berikutnya.

### Status `application_access.status`

```text
active
inactive
revoked
```

### Status laporan ke `POST /api/provisioning/sync-results`

```text
matched
needs_update
missing_in_application
suspended
conflict
failed
never_synced
```

Kategori preview lokal seperti `access_revoked`, `inactive_in_gate`, `reactivation_required`, dan `local_only` dipakai saat dry-run. Saat melapor ke Gate, gunakan salah satu status laporan yang didukung di atas.

## Desain database client yang disarankan

Gunakan nilai canonical pada storage dan terjemahkan hanya label UI:

```php
Schema::table('users', function (Blueprint $table) {
    $table->uuid('gate_user_uuid')->nullable()->unique();
    $table->string('type', 32)->index();
    $table->string('status', 32)->default('active')->index();
    $table->string('application_role', 100)->nullable()->index();
    $table->string('auth_source', 20)->default('gate');
});
```

`VARCHAR` dengan validasi aplikasi lebih aman daripada enum database untuk `application_role`, karena role tersebut merupakan konfigurasi per aplikasi dan dapat bertambah. Bila client wajib memakai enum untuk `type`, enum harus menerima kelima nilai canonical Gate.

Jangan membuat satu kolom `role` menanggung tiga arti sekaligus:

- `type`: kategori identitas canonical Gate;
- `application_role`: role user khusus pada satu aplikasi;
- role/permission lokal: otorisasi internal milik client.

Pisahkan ketiganya bila aplikasi memang membutuhkan ketiganya.

## Mapping untuk schema client legacy

Bila client memakai enum berbahasa Indonesia atau enum lama, mapping harus eksplisit. Contoh berikut hanya template; nilai sebelah kanan harus disesuaikan dengan enum nyata pada database client.

```php
// config/gate-sync.php pada aplikasi client
return [
    'type_map' => [
        'student' => 'siswa',
        'teacher' => 'guru',
        'parent' => 'wali',
        'staff' => 'staff',
        'admin' => 'admin',
    ],

    'status_map' => [
        'active' => 'aktif',
        'suspended' => 'nonaktif',
        'pending' => 'pending',
    ],

    // Key adalah application_access.role dari Gate, value adalah role lokal.
    // Biarkan kosong bila role aplikasi tidak digunakan.
    'application_role_map' => [
        'santri' => 'siswa',
        'guru' => 'guru',
    ],

    // Khusus schema legacy jika users.role sebenarnya berarti tipe identitas.
    'type_to_local_role_map' => [
        'student' => 'siswa',
        'teacher' => 'guru',
        'parent' => 'wali',
        'staff' => 'staff',
        'admin' => 'admin',
    ],

    // Pilih secara sadar: application_role, type, atau none.
    'role_source' => 'application_role',

    // Nilai enum yang benar-benar diterima kolom users.role milik client.
    'allowed_local_roles' => ['siswa', 'guru', 'wali', 'staff', 'admin'],
];
```

Jangan memakai fallback diam-diam seperti `role = type`. Mapping yang tidak tersedia harus menghasilkan conflict agar perubahan schema/mapping dibahas secara sadar.

Jika kolom `users.role` bersifat `NOT NULL`, client harus memilih `role_source = type` dengan mapping lengkap, memilih `role_source = application_role` dengan fallback yang disetujui domain, atau memigrasikan kolom tersebut menjadi nullable. Jangan membiarkan keputusan itu terjadi secara implisit di tengah proses apply.

## Contoh mapper Laravel yang aman

Kode ini berada di aplikasi client, bukan di Gate:

```php
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

function mapGateUserForLocalDatabase(array $gateUser): array
{
    $canonicalTypes = ['student', 'teacher', 'parent', 'staff', 'admin'];
    $canonicalStatuses = ['active', 'suspended', 'pending'];

    $gateType = Arr::get($gateUser, 'type');
    $gateStatus = Arr::get($gateUser, 'status');
    $gateApplicationRole = Arr::get($gateUser, 'application_access.role');

    if (! in_array($gateType, $canonicalTypes, true)) {
        throw new DomainException("Gate type tidak didukung: {$gateType}");
    }

    if (! in_array($gateStatus, $canonicalStatuses, true)) {
        throw new DomainException("Gate status tidak didukung: {$gateStatus}");
    }

    $localType = config("gate-sync.type_map.{$gateType}");
    $localStatus = config("gate-sync.status_map.{$gateStatus}");
    $localRole = match (config('gate-sync.role_source', 'none')) {
        'application_role' => $gateApplicationRole === null
            ? null
            : config("gate-sync.application_role_map.{$gateApplicationRole}"),
        'type' => config("gate-sync.type_to_local_role_map.{$gateType}"),
        'none' => null,
        default => throw new DomainException('Konfigurasi role_source tidak valid.'),
    };

    if ($localType === null || $localStatus === null) {
        throw new DomainException('Mapping type/status Gate belum lengkap.');
    }

    if (config('gate-sync.role_source') === 'application_role'
        && $gateApplicationRole !== null
        && $localRole === null) {
        throw new DomainException("Application role belum dipetakan: {$gateApplicationRole}");
    }

    if (config('gate-sync.role_source') === 'type' && $localRole === null) {
        throw new DomainException("Gate type belum dipetakan ke role lokal: {$gateType}");
    }

    if ($localRole !== null && ! in_array($localRole, config('gate-sync.allowed_local_roles', []), true)) {
        throw new DomainException("Role lokal tidak diterima schema: {$localRole}");
    }

    return [
        'gate_user_uuid' => $gateUser['gate_user_uuid'] ?? $gateUser['uuid'],
        'name' => $gateUser['name'],
        'email' => $gateUser['email'] ?? null,
        'username' => $gateUser['username'],
        'type' => $localType,
        'role' => $localRole,
        'application_role' => $gateApplicationRole,
        'status' => $localStatus,
        'auth_source' => 'gate',
    ];
}
```

Jika schema client mempunyai `type` canonical dan `role` lokal terpisah, simpan `type` langsung sebagai `$gateType` dan gunakan mapping hanya untuk `role`.

## Pola yang salah dan benar

Salah—`type` dipaksa menjadi role lokal tanpa pemeriksaan enum:

```php
User::create([
    'type' => $gateUser['type'],
    'role' => $gateUser['type'],
]);
```

Benar—setiap domain dipetakan secara terpisah dan divalidasi sebelum query:

```php
$attributes = mapGateUserForLocalDatabase($gateUser);

validator($attributes, [
    'gate_user_uuid' => ['required', 'uuid'],
    'type' => ['required', Rule::in(array_values(config('gate-sync.type_map')))],
    'role' => ['nullable', Rule::in(config('gate-sync.allowed_local_roles'))],
    'status' => ['required', Rule::in(array_values(config('gate-sync.status_map')))],
])->validate();

User::updateOrCreate(
    ['gate_user_uuid' => $attributes['gate_user_uuid']],
    $attributes
);
```

## Preflight sebelum tombol Apply aktif

Dry-run client wajib menjalankan pemeriksaan berikut:

1. `schema_version` didukung.
2. Semua `gate_user_uuid` valid dan unik dalam payload.
3. Setiap `type` dan `status` termasuk enum canonical.
4. Semua hasil mapping sesuai enum/constraint database client.
5. `application_access.role = null` diterima sebagai kondisi normal.
6. Mapping role yang tidak dikenal masuk `conflict`, bukan memakai fallback.
7. Email dan username duplikat ditahan untuk review; jangan auto-merge tanpa UUID.
8. Preview menampilkan nilai Gate, hasil mapping lokal, dan alasan konflik.
9. Apply dibungkus satu transaksi database atau transaksi per batch yang dapat dilanjutkan dengan aman.
10. Password dan data domain lokal tidak ditimpa.

Untuk memeriksa enum MySQL client:

```sql
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME IN ('type', 'role', 'application_role', 'status');
```

Hasil query tersebut harus dibandingkan dengan output mapping sebelum sinkronisasi pertama dijalankan.

## Penanganan error dan retry

Jika mapping atau constraint gagal:

1. Rollback batch; jangan meninggalkan sebagian user sudah berubah.
2. Tandai item sebagai `conflict` saat membutuhkan keputusan mapping, atau `failed` untuk kegagalan teknis.
3. Simpan `gate_user_uuid`, field sumber, hasil mapping, dan pesan error—jangan simpan secret atau password.
4. Kirim hasil ke `POST /api/provisioning/sync-results` dengan `error_code` stabil, misalnya `unsupported_local_role`.
5. Perbaiki config/migration client, jalankan dry-run ulang, lalu apply ulang secara idempotent dengan `updateOrCreate` berdasarkan `gate_user_uuid`.

Contoh laporan gagal:

```json
{
  "items": [
    {
      "gate_user_uuid": "34b3206d-bb18-4d63-9dbc-66b0d0e26762",
      "status": "conflict",
      "external_user_id": null,
      "error_code": "unsupported_local_role",
      "error_message": "Gate type student belum memiliki mapping ke enum users.role client."
    }
  ]
}
```

## Checklist integrasi client

- [ ] Menyimpan dan mencocokkan user menggunakan `gate_user_uuid`.
- [ ] Menyimpan UUID, NIS, dan NIP sebagai string agar format tidak berubah.
- [ ] Memisahkan `type`, `application_role`, dan role/permission lokal.
- [ ] Mendukung lima nilai canonical `type` atau menyediakan mapping lengkap.
- [ ] Mendukung status canonical atau menyediakan mapping lengkap.
- [ ] Mengizinkan `application_access.role` bernilai null.
- [ ] Memvalidasi hasil mapping terhadap enum database sebelum apply.
- [ ] Menampilkan conflict pada preview, bukan melakukan fallback diam-diam.
- [ ] Tidak menerima atau menyinkronkan password dari Gate.
- [ ] Tidak menghapus user saat akses dicabut; suspend akun lokal.
- [ ] Melaporkan hasil sinkronisasi kembali ke Gate.
- [ ] Menjalankan test contract dengan minimal satu user untuk setiap tipe dan setiap mapping role.

## Contract test minimum untuk client

Setiap repository client sebaiknya mempunyai automated test yang memastikan:

1. Payload `student` tidak pernah langsung ditulis ke enum `role` yang tidak mendukungnya.
2. Semua tipe `student`, `teacher`, `parent`, `staff`, dan `admin` dapat dipetakan atau menghasilkan conflict yang jelas.
3. `application_access.role = null` dapat diproses.
4. UUID yang sama melakukan update, bukan membuat user duplikat.
5. Status Gate dapat dipetakan ke status lokal.
6. Batch di-rollback bila satu mapping invalid.
7. Field tambahan pada schema 1.x diabaikan dengan aman.
