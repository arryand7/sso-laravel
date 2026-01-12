# Panduan Sinkronisasi User

Dokumen ini menjelaskan mekanisme sinkronisasi user antara Gate SSO, data ekspor SSS, dan LMS Moodle.

## 1) SSS -> Gate
Script: `/var/www/gate.sabira-iibs.id/scripts/sync_users.php`

Sumber data default:
- `student_email.csv`
- `staff_email.csv`
- `data_guru.xlsx`

Lokasi default:
`/var/www/sss.sabira-iibs.id/temp/import/DATA`

Aturan mapping:
- `username`: NIS (student) atau NIP (staff)
- `email`: dari CSV
- `password`: dari CSV, jika kosong akan di-generate acak
- `type`:
  - student: `student`
  - staff: default `staff`, menjadi `teacher` jika kolom "Jenis Pegawai" mengandung kata `guru/teacher/ustadz/ustad`
- role disinkronkan sesuai `type`

Cara menjalankan:
```bash
php /var/www/gate.sabira-iibs.id/scripts/sync_users.php
```

Opsi:
- `--dry-run` simulasi tanpa menulis DB
- `--update-passwords` update password untuk user yang sudah ada
- `--students=/path/to/student_email.csv`
- `--staff=/path/to/staff_email.csv`
- `--staff-info=/path/to/data_guru.xlsx`

Catatan:
- Pastikan role `student`, `teacher`, `staff` sudah ada (RoleSeeder).

## 2) Gate -> LMS (Moodle)
Script: `/var/www/gate.sabira-iibs.id/scripts/sync_gate_to_lms.php`

Aturan matching (merge):
- Cocokkan `gate.username` dengan `mdl_user.firstname` di LMS.
- Jika match dan `firstname` tidak duplikat, email LMS akan diubah menjadi email dari Gate.

Aturan insert (jika tidak match):
- `username` Moodle = email dari Gate
- `firstname` Moodle = `gate.username`
- `lastname` Moodle = `gate.name`
- `auth` = `oauth2`
- `confirmed` = `1`
- `mnethostid` = `1`
- `suspended` mengikuti `gate.status` (active = 0, selain itu 1)

Cara menjalankan:
```bash
php /var/www/gate.sabira-iibs.id/scripts/sync_gate_to_lms.php
```

Opsi:
- `--dry-run` simulasi tanpa menulis DB
- `--limit=100` batasi jumlah user Gate
- `--update-lastname` update `lastname` LMS saat match

Catatan penting:
- Jika `firstname` di LMS duplikat, script akan skip untuk menghindari salah merge.
- Pastikan konfigurasi DB Gate terbaca dari `.env` dan Moodle dari `moodle/config.php`.
