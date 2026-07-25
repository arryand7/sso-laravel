# Ketentuan Integrasi Sinkronisasi User di Aplikasi Client — Sabira Connect (Gate SSO)

Dokumen ini berisi ketentuan teknis, arsitektur, dan alur pengembangan yang **wajib** diterapkan pada setiap aplikasi client (seperti **Smart, Laptop, SSS, Moodle, Progress, Absensi**) agar dapat terhubung dan menggunakan fitur **Pull-Based User Synchronization & Application Access Management** dari **Gate SSO**.

---

## 1. Konsep Utama & Prinsip Identitas

```text
Gate SSO (Pusat SSO & Identitas Canonical)
       │
       │  Pull Data Canonical (Authenticated via X-Client-Id & Secret)
       ▼
Aplikasi Client (Smart / Laptop / SSS / Moodle)
 ├── 1. Dry-Run Preview (Pencocokan 8 Kategori)
 ├── 2. Peninjauan Laporan oleh Superadmin Client
 ├── 3. Konfirmasi "Apply Synchronization" (Local DB Transaction)
 └── 4. Reporting Hasil Sync Balik ke Gate (POST /api/provisioning/sync-results)
```

### Aturan Utama:
1. **Gate SSO sebagai Source of Truth**: Seluruh data identitas dasar user dikelola terpusat di Gate SSO.
2. **UUID Identifier Lintas Aplikasi (`gate_user_uuid`)**:
   - Setiap user di Gate SSO memiliki `uuid` (UUID v4) permanen.
   - Aplikasi client **wajib** menggunakan `gate_user_uuid` sebagai identifier utama relasi lintas aplikasi.
   - **Dilarang keras** menggunakan `nama`, `email`, `username`, `NIS`, atau `NIP` sebagai relasi permanen atau melakukan *auto-merge* berbasis kemiripan string.
3. **Model Sinkronisasi Pull-Based**:
   - Gate SSO tidak memaksa mengubah database aplikasi client secara otomatis.
   - Sinkronisasi dipicu oleh **Superadmin aplikasi client** melalui tombol **"Sync Users from Gate"**.
4. **Isolasi Password**:
   - Password **TIDAK PERNAH** disinkronkan atau dikirimkan via API.
   - Autentikasi login pengguna tetap menggunakan OAuth2 / OpenID Connect (OIDC) Gate SSO.
5. **Non-Destructive Access Revocation**:
   - Pencabutan akses di Gate SSO **TIDAK BOLEH** menghapus record user atau data historis di aplikasi client.
   - Akun lokal hanya di-`suspend` agar transaksi, nilai, absensi, peminjaman, atau histori domain aplikasi tetap aman.

---

## 2. Ketentuan Database Aplikasi Client

Setiap aplikasi client harus menyesuaikan struktur tabel `users` (atau tabel user lokal) dengan menambahkan kolom berikut:

| Kolom | Tipe Data | Sifat | Keterangan |
| :--- | :--- | :--- | :--- |
| `gate_user_uuid` | `VARCHAR(36)` / `UUID` | `NULLABLE`, `UNIQUE` | Menyimpan UUID v4 canonical dari Gate SSO |
| `status` | `ENUM('active', 'suspended')` | `NOT NULL`, `DEFAULT 'active'` | Status keaktifan akun di aplikasi lokal |
| `application_role` / `role` | `VARCHAR(255)` | `NULLABLE` | Role khusus user pada aplikasi lokal (misal: santri, guru, admin) |
| `photo_path` / `avatar` | `VARCHAR(255)` | `NULLABLE` | Path/URL foto profil lokal jika disinkronkan |

### Contoh Migration (Laravel Client):
```php
Schema::table('users', function (Blueprint $table) {
    if (!Schema::hasColumn('users', 'gate_user_uuid')) {
        $table->uuid('gate_user_uuid')->nullable()->unique()->after('id');
    }
    if (!Schema::hasColumn('users', 'status')) {
        $table->enum('status', ['active', 'suspended'])->default('active');
    }
    if (!Schema::hasColumn('users', 'application_role')) {
        $table->string('application_role')->nullable();
    }
});
```

---

## 3. Autentikasi & Keamanan API Provisioning

Seluruh komunikasi API ke Gate SSO wajib melalui HTTPS dan diotentikasi dengan kredensial aplikasi yang terdaftar di Gate SSO.

### Header Request Wajib:
```http
X-Client-Id: <CLIENT_ID_APLIKASI>
X-Client-Secret: <CLIENT_SECRET_APLIKASI>
Accept: application/json
```

### Ketentuan Keamanan:
- Setiap aplikasi client harus memiliki `client_id` dan `client_secret` sendiri. Dilarang berbagi kredensial antar aplikasi.
- Secret disimpan terenkripsi di `.env` atau database client dan **dilarang dicatat di log**.
- Endpoint API Provisioning dilengkapi *rate limiting* (default 60 request/menit).

---

## 4. Ketentuan Alur Sinkronisasi 2-Step (Dry-Run & Commit)

Saat Superadmin aplikasi client menekan tombol **"Sync Users from Gate"**:

### Step 1: Dry-Run Preview (Tanpa Mengubah Database)
Aplikasi client mengambil data canonical dari Gate via `GET /api/provisioning/users`, membandingkan dengan database lokal, dan mengelompokkan ke dalam **8 Kategori Hasil Pencocokan**:

| Kategori | Kondisi Pencocokan | Tindakan yang Disediakan |
| :--- | :--- | :--- |
| **`matched`** | `gate_user_uuid` cocok & seluruh field dasar sama | Tidak ada perubahan (*No Change*) |
| **`needs_update`** | `gate_user_uuid` cocok, tetapi ada perbedaan nama/email/username/tipe/role | Pilihan update data lokal ke nilai Gate |
| **`missing_in_application`** | User memiliki akses aktif di Gate tetapi belum ada di database lokal | Pilihan buat akun user lokal baru (*Create Local User*) |
| **`access_revoked`** | Akses user ke aplikasi dicabut (`revoked`/`inactive`) di Gate | Pilihan suspend akun user lokal (*Suspend Local User*) |
| **`inactive_in_gate`** | User utama di Gate berstatus `inactive` / `suspended` | Pilihan suspend akun user lokal (*Suspend Local User*) |
| **`reactivation_required`** | User aktif di Gate, tetapi akun lokal saat ini berstatus `suspended` | Pilihan aktifkan kembali akun lokal (*Reactivate Local User*) |
| **`local_only`** | User ada di database lokal tetapi tidak terdaftar di Gate | Laporan peninjauan (*Report only / Manual review*) |
| **`conflict`** | Email/username cocok dengan user lokal lain tetapi `gate_user_uuid` belum terhubung | Tahan perubahan (*Manual review required* — **Dilarang Auto-Merge**) |

### Step 2: Review & Apply Transaction
1. Aplikasi menampilkan ringkasan jumlah per kategori dan tabel perbandingan field (nilai lokal vs nilai Gate).
2. Superadmin meninjau dan menekan tombol **"Apply Synchronization"**.
3. Perubahan diterapkan dalam **database transaction** lokal.
4. Aplikasi memanggil `POST /api/provisioning/sync-results` untuk melaporkan status hasil sync ke Gate.

---

## 5. Ketentuan Pelaporan Status Sync ke Gate SSO

Setelah sinkronisasi diterapkan di aplikasi client, aplikasi client **wajib** mengomunikasikan status akhir kembali ke Gate SSO.

### Endpoint:
`POST /api/provisioning/sync-results`

### Contoh Payload Request:
```json
{
  "items": [
    {
      "gate_user_uuid": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "status": "matched",
      "external_user_id": "101"
    },
    {
      "gate_user_uuid": "b2c3d4e5-f6a7-8901-bcde-f23456789012",
      "status": "needs_update",
      "external_user_id": "102"
    },
    {
      "gate_user_uuid": "c3d4e5f6-a7b8-9012-cdef-345678901234",
      "status": "conflict",
      "external_user_id": null,
      "error_code": "unlinked_matching_identifier",
      "error_message": "Email cocok tetapi gate_user_uuid belum terhubung."
    }
  ]
}
```

---

## 6. Pengolahan Foto Profil & QR Code

1. **Foto Profil**:
   - Jika capability `sync_photo = true`, Gate mengembalikan objek foto:
     ```json
     "photo": {
       "available": true,
       "url": "https://gate.sabira-iibs.id/api/provisioning/photo/12?expires=...&signature=...",
       "checksum": "a8f5f167f44f4964e6c998dee827110c"
     }
     ```
   - Aplikasi client mengunduh foto via `url` (Temporary Signed URL) jika checksum berbeda dari checksum lokal.
2. **Kode QR Kartu**:
   - Kolom `qr_code` hanya dikirim oleh Gate jika aplikasi client dikonfigurasi memiliki capability `sync_qr = true`.

---

## 7. Isolasi Data Domain Aplikasi

Sinkronisasi ini **HANYA** boleh memperbarui data identitas dasar:
- `name`
- `email`
- `username`
- `type`
- `status`
- `application_role`
- `photo_path` (jika diizinkan)
- `qr_code` (jika diizinkan)
- `gate_user_uuid`

Data domain bawaan aplikasi **DILARAANG** diubah atau dihapus oleh proses sinkronisasi:
- **Smart**: Saldo dompet, transaksi, histori pembelian, limit harian.
- **Moodle**: Nilai kuis, pendaftaran kursus, histori jawaban, tugas.
- **Laptop**: Histori peminjaman, kondisi perangkat, pengembalian.
- **SSS**: Histori absensi, catatan pelanggaran, poin prestasi.

---

## 8. Contoh Kode Integrasi Client (Laravel Connector)

Berikut adalah contoh controller lengkap pada aplikasi client (misal: Smart/Laptop/SSS):

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GateUserSyncController extends Controller
{
    /**
     * Step 1: Fetch & Preview Reconciliation
     */
    public function preview()
    {
        $gateUrl = config('services.gate.url');
        $clientId = config('services.gate.client_id');
        $clientSecret = config('services.gate.client_secret');

        // Fetch Gate canonical users
        $response = Http::withHeaders([
            'X-Client-Id' => $clientId,
            'X-Client-Secret' => $clientSecret,
        ])->get("{$gateUrl}/api/provisioning/users");

        if (! $response->successful()) {
            return back()->with('error', 'Gagal terhubung ke Provisioning API Gate SSO.');
        }

        $gateUsers = $response->json('users') ?? [];
        $localUsers = User::all()->toArray();

        // Run Reconciliation Engine
        $report = $this->reconcileData($gateUsers, $localUsers);

        // Store report in session for confirmation step
        session(['gate_sync_preview' => $report]);

        return view('admin.sync.preview', compact('report'));
    }

    /**
     * Step 2: Apply Confirmed Changes & Report Back
     */
    public function apply(Request $request)
    {
        $report = session('gate_sync_preview');
        if (! $report) {
            return redirect()->route('admin.sync.preview')->with('error', 'Preview sinkronisasi kadaluarsa.');
        }

        $syncResultItems = [];

        DB::transaction(function () use ($report, &$syncResultItems) {
            // 1. Missing in Application -> Create Local User
            foreach ($report['categories']['missing_in_application'] as $item) {
                $gu = $item['gate_user'];
                $newUser = User::create([
                    'gate_user_uuid' => $gu['uuid'],
                    'name' => $gu['name'],
                    'username' => $gu['username'],
                    'email' => $gu['email'],
                    'type' => $gu['type'],
                    'status' => 'active',
                    'application_role' => $gu['application_access']['role'] ?? null,
                ]);

                $syncResultItems[] = [
                    'gate_user_uuid' => $gu['uuid'],
                    'status' => 'matched',
                    'external_user_id' => (string) $newUser->id,
                ];
            }

            // 2. Needs Update -> Update Local User
            foreach ($report['categories']['needs_update'] as $item) {
                $gu = $item['gate_user'];
                $lu = $item['local_user'];
                
                User::where('id', $lu['id'])->update([
                    'name' => $gu['name'],
                    'email' => $gu['email'],
                    'username' => $gu['username'],
                    'type' => $gu['type'],
                    'application_role' => $gu['application_access']['role'] ?? null,
                ]);

                $syncResultItems[] = [
                    'gate_user_uuid' => $gu['uuid'],
                    'status' => 'matched',
                    'external_user_id' => (string) $lu['id'],
                ];
            }

            // 3. Access Revoked / Inactive -> Suspend Local User
            $suspendItems = array_merge(
                $report['categories']['access_revoked'],
                $report['categories']['inactive_in_gate']
            );

            foreach ($suspendItems as $item) {
                $gu = $item['gate_user'];
                $lu = $item['local_user'];

                User::where('id', $lu['id'])->update(['status' => 'suspended']);

                $syncResultItems[] = [
                    'gate_user_uuid' => $gu['uuid'],
                    'status' => 'suspended',
                    'external_user_id' => (string) $lu['id'],
                ];
            }

            // 4. Reactivation Required -> Reactivate Local User
            foreach ($report['categories']['reactivation_required'] as $item) {
                $gu = $item['gate_user'];
                $lu = $item['local_user'];

                User::where('id', $lu['id'])->update(['status' => 'active']);

                $syncResultItems[] = [
                    'gate_user_uuid' => $gu['uuid'],
                    'status' => 'matched',
                    'external_user_id' => (string) $lu['id'],
                ];
            }
        });

        // Report results back to Gate SSO
        Http::withHeaders([
            'X-Client-Id' => config('services.gate.client_id'),
            'X-Client-Secret' => config('services.gate.client_secret'),
        ])->post(config('services.gate.url') . '/api/provisioning/sync-results', [
            'items' => $syncResultItems,
        ]);

        session()->forget('gate_sync_preview');

        return redirect()->route('admin.sync.preview')->with('status', 'Sinkronisasi user dari Gate SSO berhasil diterapkan.');
    }

    protected function reconcileData(array $gateUsers, array $localUsers): array
    {
        // Engine pencocokan 8 kategori (lihat spesifikasi Bab 4)
        return app(\App\Services\SyncReconciliationService::class)->generatePreviewReport($gateUsers, $localUsers);
    }
}
```

---

## 9. Checklist Kesiapan Aplikasi Client

Sebelum membuka fitur sinkronisasi di produksi, pastikan checklist berikut terpenuhi:

- [ ] Migration `gate_user_uuid` (unique, nullable) dan `status` sudah dijalankan di database client.
- [ ] `X-Client-Id` dan `X-Client-Secret` terkonfigurasi di file `.env` aplikasi client.
- [ ] Halaman menu **User Synchronization** diproteksi hanya untuk **Superadmin** aplikasi client.
- [ ] Tombol **"Sync Users from Gate"** pertama kali selalu menjalankan *dry-run preview* (tidak langsung mengubah DB).
- [ ] Laporan preview menampilkan 8 kategori pencocokan dan detail perbedaan data.
- [ ] Perubahan DB lokal dieksekusi dalam database transaction saat tombol **"Apply Synchronization"** diklik.
- [ ] Akun lokal yang kehilangan akses di-`suspend` (bukan dihapus).
- [ ] Aplikasi client melaporkan status sync ke Gate via `POST /api/provisioning/sync-results`.
- [ ] Data domain lokal (saldo, kuis, absensi, transaksi) tidak pernah terhapus atau terubah oleh sinkronisasi.
