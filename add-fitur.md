Anda bekerja sebagai **senior Laravel engineer, application security engineer, dan full-stack developer** pada project:

# Sabira Connect — Gate SSO

Project ini sudah mempunyai:

* manajemen user;
* foto profil user;
* QR kartu anggota;
* import user melalui Excel;
* Laravel Storage;
* role dan permission;
* audit log;
* pemrosesan foto menjadi bingkai 4:3;
* kompresi foto maksimal sekitar 300 KB;
* login OAuth2/OpenID Connect;
* Laravel Passport.

Saya ingin menambahkan fitur baru:

# Bulk Import Foto User melalui ZIP

Fitur ini memungkinkan superadmin meng-upload satu file `.zip` yang berisi banyak foto user. Setiap foto dicocokkan ke user berdasarkan nama file yang berupa NIS atau NIP.

Contoh isi ZIP:

```text
22001001.jpg
22001002.jpeg
22001003.png
22001004.webp
198707012010011001.jpg
```

Contoh pencocokan:

```text
22001001.jpg
→ nama file tanpa ekstensi: 22001001
→ cocokkan dengan users.nis = "22001001"
```

atau:

```text
198707012010011001.png
→ nama file tanpa ekstensi: 198707012010011001
→ cocokkan dengan users.nip = "198707012010011001"
```

Jangan langsung membuat kode sebelum memeriksa implementasi repository existing.

# Tujuan utama

Implementasikan fitur yang memungkinkan:

1. Superadmin membuka halaman Bulk Import Foto.
2. Superadmin memilih metode pencocokan:

   * NIS untuk siswa;
   * NIP untuk guru dan staff.
3. Superadmin meng-upload file ZIP.
4. Sistem memeriksa keamanan dan isi ZIP.
5. Sistem membaca seluruh file foto.
6. Nama file tanpa ekstensi dicocokkan secara exact dengan NIS atau NIP.
7. Sistem menampilkan preview sebelum mengubah data user.
8. Sistem menampilkan laporan file cocok, tidak cocok, duplikat, rusak, dan format tidak didukung.
9. Superadmin menentukan apakah foto user existing dilewati atau diganti.
10. Setelah konfirmasi, sistem memproses foto menggunakan mekanisme foto profil existing.
11. Sistem menyimpan laporan hasil import.
12. File temporary dan hasil ekstraksi dibersihkan setelah proses selesai.

# Ketentuan penting

* Jangan melakukan redesign.
* Pertahankan layout, navbar, sidebar, typography, warna, card, tabel, tombol, modal, dan pola UI existing.
* Gunakan komponen Blade dan CSS existing.
* Fitur hanya boleh diakses superadmin atau permission khusus.
* Jangan mengubah flow login, OAuth2, OIDC, Passport, UserInfo, JWKS, atau Google OAuth.
* Jangan mengubah password user.
* Jangan menghapus user.
* Jangan membuat user baru dari foto.
* Jangan melakukan fuzzy matching.
* Jangan mencocokkan berdasarkan nama orang.
* Jangan menebak NIS atau NIP secara otomatis jika admin sudah memilih mode pencocokan.
* Jangan menyimpan file ZIP atau hasil ekstraksi pada public storage.
* Jangan menggunakan nama file upload sebagai path final foto.
* Jangan langsung menimpa foto lama sebelum foto baru berhasil diproses dan disimpan.

# Tahap pertama: audit repository

Sebelum implementasi, periksa:

* versi Laravel;
* versi PHP;
* model `User`;
* field `photo_path`;
* field `nis`;
* field `nip`;
* UUID atau identifier internal user;
* `UserPhotoService` atau service pemrosesan foto existing;
* library image processing;
* dukungan GD atau Imagick;
* dukungan WebP;
* Laravel Storage disk;
* halaman manajemen user;
* role dan permission;
* audit log;
* queue dan scheduler;
* batas upload PHP dan Nginx;
* automated tests;
* pola import batch existing;
* pola laporan Excel/CSV existing.

Gunakan kembali service foto existing. Jangan menduplikasi algoritma resize, frame 4:3, kompresi, dan penyimpanan apabila implementasinya sudah tersedia.

Jika implementasi existing belum dipisahkan menjadi service, lakukan refactor minimal dan aman agar pemrosesan foto individual dan bulk import memakai satu mekanisme yang sama.

# A. Halaman Bulk Import Foto

Tambahkan halaman admin:

```text
Admin > Users > Bulk Import Photos
```

Halaman hanya dapat diakses oleh superadmin atau permission:

```text
users.bulk-import-photos
```

Form minimal memiliki:

## 1. Metode pencocokan

```text
Match photos using:

( ) NIS — Students
( ) NIP — Teachers and Staff
```

Pilihan ini wajib.

Jika memilih NIS:

* hanya cari user student;
* cocokkan dengan kolom `nis`.

Jika memilih NIP:

* hanya cari user teacher atau staff;
* cocokkan dengan kolom `nip`.

Gunakan definisi type yang benar dari repository. Jangan mengasumsikan nama enum jika project menggunakan nilai berbeda.

## 2. Upload ZIP

Terima hanya file:

```text
.zip
```

Jangan menerima:

* `.rar`;
* `.7z`;
* `.tar`;
* `.tar.gz`;
* executable;
* file lain yang hanya diganti extension menjadi `.zip`.

Validasi MIME dan struktur ZIP, bukan extension saja.

## 3. Kebijakan foto existing

Sediakan pilihan:

```text
If the user already has a profile photo:

( ) Skip existing photo
( ) Replace existing photo
```

Default:

```text
Skip existing photo
```

Jika memilih replace, preview harus menunjukkan jumlah foto existing yang akan diganti.

# B. Aturan nama file

Identifier diambil dari:

```text
basename file
→ hapus extension terakhir
→ trim whitespace
→ gunakan sebagai NIS atau NIP
```

Contoh valid:

```text
22001001.jpg
22001001.JPG
22001001.jpeg
22001001.png
22001001.webp
198707012010011001.jpg
```

Contoh tidak valid:

```text
Ahmad-22001001.jpg
22001001-foto.jpg
22001001 (1).jpg
foto_22001001.jpg
22001001.jpg.exe
.jpg
```

Pencocokan harus exact.

Jangan:

* mengambil angka dari bagian tengah filename;
* menghapus karakter tambahan secara otomatis;
* menggunakan regex untuk menebak identifier;
* menggunakan fuzzy matching;
* mencocokkan nama user;
* mencocokkan email.

NIS dan NIP harus diperlakukan sebagai string.

Pertahankan:

* leading zero;
* seluruh digit NIP;
* panjang identifier;
* kapitalisasi jika identifier dapat mengandung huruf.

Jangan cast identifier ke integer atau float.

# C. Format foto yang didukung

Dukung format yang telah didukung oleh service foto existing, minimal:

```text
.jpg
.jpeg
.png
.webp
```

Validasi harus membaca:

* MIME type;
* image header;
* kemampuan decoder;
* apakah file dapat benar-benar dibuka sebagai image.

Tolak:

* SVG;
* GIF animasi;
* executable;
* PHP;
* JavaScript;
* HTML;
* file rusak;
* file yang hanya mengganti extension menjadi JPG;
* nested ZIP;
* symbolic link;
* shortcut;
* file dengan path berbahaya.

HEIC, HEIF, TIFF, atau BMP hanya boleh didukung jika environment server dan library existing mendukung format tersebut secara stabil.

Jika tidak didukung, masukkan ke laporan:

```text
UNSUPPORTED_FORMAT
```

Jangan menambahkan dukungan format baru tanpa memeriksa dependency server.

# D. Keamanan ZIP

Implementasikan perlindungan terhadap:

## Zip Slip

Tolak entry seperti:

```text
../../file.php
../storage/file
/absolute/path/file.jpg
folder/../../../file.jpg
```

Seluruh hasil ekstraksi harus tetap berada di temporary directory batch.

Validasi canonical path sebelum mengekstrak setiap file.

## Zip bomb

Batasi:

* ukuran ZIP;
* jumlah entry;
* ukuran satu file;
* total ukuran hasil ekstraksi;
* compression ratio;
* kedalaman folder;
* nested archive.

Gunakan batas awal yang dapat dikonfigurasi melalui config atau environment, misalnya:

```text
Maximum ZIP upload: 500 MB
Maximum photo files: 2,000
Maximum one photo input: 10 MB
Maximum extracted size: 2 GB
Maximum nested directory depth: 3
```

Sesuaikan nilainya dengan konfigurasi server, tetapi jangan hardcode seluruh batas di controller.

Contoh konfigurasi:

```text
config/user-photo-import.php
```

atau gunakan struktur config existing.

## File yang diabaikan

Abaikan dan catat bila perlu:

```text
__MACOSX/
.DS_Store
Thumbs.db
hidden system files
empty directories
```

Jangan menganggap file sistem tersebut sebagai foto gagal yang perlu diproses sebagai user.

## Temporary storage

Gunakan private temporary storage:

```text
storage/app/private/user-photo-imports/{batch_uuid}/
```

atau struktur private storage existing.

Jangan ekstrak ke:

```text
public/
public/storage/
storage/app/public/
project root
```

Temporary files harus dibersihkan setelah:

* validasi gagal;
* preview dibatalkan;
* import berhasil;
* import gagal;
* batch kedaluwarsa.

Tambahkan scheduled cleanup untuk batch temporary yang tertinggal.

# E. Pemrosesan foto

Gunakan mekanisme foto profil existing secara konsisten.

Setiap foto yang berhasil dicocokkan harus diproses menjadi:

```text
canvas 4:3
```

Default target:

```text
800 × 600 pixel
```

Gunakan service existing, misalnya:

```text
UserPhotoService
```

Ketentuan hasil foto:

* tidak crop;
* tidak stretch;
* tidak menggunakan `cover`;
* menggunakan `contain`, `fit`, atau letterbox;
* aspect ratio asli dipertahankan;
* foto diletakkan di tengah;
* area kosong ditambahkan pada sisi yang diperlukan;
* background frame menggunakan warna netral existing;
* orientasi EXIF diperbaiki;
* metadata EXIF dibuang;
* output menggunakan WebP jika didukung;
* ukuran akhir maksimal sekitar 300 KB;
* kompresi adaptif;
* file mentah tidak disimpan permanen.

Untuk foto portrait:

* area kosong berada di kiri dan kanan.

Untuk foto landscape yang lebih lebar dari 4:3:

* area kosong berada di atas dan bawah.

Untuk foto square:

* foto tetap square;
* area kosong berada di kiri dan kanan.

Jangan melakukan AI cropping, face detection, face replacement, background generation, atau manipulasi wajah.

# F. Proses upload tidak langsung mengubah user

Klik upload pertama hanya melakukan:

```text
Upload
→ Security validation
→ Extract to private temporary directory
→ Inspect files
→ Match identifiers
→ Build preview report
```

Pada tahap ini:

* jangan memperbarui `users.photo_path`;
* jangan menghapus foto lama;
* jangan menyimpan foto final;
* jangan menulis audit “photo updated”;
* jangan melakukan perubahan permanen pada user.

Preview harus aman dijalankan berulang kali.

# G. Kategori hasil preview

Setiap file harus masuk ke salah satu status berikut.

## MATCHED_NEW

Identifier cocok dengan satu user dan user belum memiliki foto.

Tindakan:

```text
Ready to import
```

## MATCHED_REPLACE

Identifier cocok dengan satu user, user sudah memiliki foto, dan kebijakan adalah replace.

Tindakan:

```text
Ready to replace
```

## SKIPPED_EXISTING

Identifier cocok dengan user yang sudah memiliki foto, tetapi kebijakan adalah skip.

Tindakan:

```text
No change
```

## USER_NOT_FOUND

Tidak ada user dengan NIS atau NIP tersebut.

Jangan membuat user baru.

## DUPLICATE_FILE_IDENTIFIER

Terdapat lebih dari satu file dengan identifier dasar yang sama.

Contoh:

```text
22001001.jpg
22001001.png
```

Jangan memilih salah satu secara otomatis.

Kedua file harus ditandai konflik.

## DUPLICATE_USER_IDENTIFIER

Satu NIS atau NIP ditemukan pada lebih dari satu user.

Jangan menentukan user otomatis.

Masukkan sebagai konflik data yang perlu diperbaiki.

## INVALID_FILENAME

Nama file tidak sesuai format identifier exact.

## UNSUPPORTED_FORMAT

Format atau decoder tidak didukung.

## CORRUPTED_IMAGE

File memiliki extension image tetapi tidak dapat dibaca sebagai gambar valid.

## FILE_TOO_LARGE

Ukuran foto melewati batas.

## IMAGE_DIMENSION_TOO_LARGE

Dimensi atau jumlah pixel melewati batas aman.

## SECURITY_REJECTED

File ditolak karena:

* path traversal;
* symbolic link;
* executable;
* nested ZIP;
* MIME berbahaya;
* masalah keamanan lain.

## PROCESSING_FAILED

Foto cocok dengan user, tetapi pemrosesan image gagal.

# H. Ringkasan preview

Tampilkan summary seperti:

```text
Total ZIP entries             520
Detected photo files          500
Ready to import               430
Ready to replace               25
Skipped existing photos        15
User not found                 12
Duplicate file identifiers      5
Duplicate user identifiers      2
Invalid filenames               4
Unsupported formats             3
Corrupted images                2
Security rejected files         2
```

Setiap kategori harus dapat difilter.

Tampilkan tabel minimal:

```text
Preview
Original Filename
Identifier Type
Identifier
Matched User
User Type
Existing Photo
Status
Reason
Planned Action
```

Contoh:

```text
22001001.jpg
NIS
22001001
Ahmad Fauzan
Student
No
MATCHED_NEW
Identifier matched exactly
Import photo
```

```text
22001002.png
NIS
22001002
Budi Santoso
Student
Yes
SKIPPED_EXISTING
Existing photo policy is skip
No change
```

```text
22001999.jpg
NIS
22001999
—
—
—
USER_NOT_FOUND
No user with this NIS
Manual review
```

# I. Konfirmasi sebelum import

Sebelum apply, tampilkan konfirmasi:

```text
430 photos will be added.
25 existing photos will be replaced.
15 existing photos will be skipped.
30 invalid or conflicting files will not be processed.
```

Sediakan tombol:

```text
Cancel
Download Preview Report
Confirm Import
```

Hanya file dengan status aman berikut yang boleh diproses:

```text
MATCHED_NEW
MATCHED_REPLACE
```

Jangan memproses secara otomatis:

```text
USER_NOT_FOUND
DUPLICATE_FILE_IDENTIFIER
DUPLICATE_USER_IDENTIFIER
INVALID_FILENAME
UNSUPPORTED_FORMAT
CORRUPTED_IMAGE
SECURITY_REJECTED
```

# J. Proses import

Untuk batch kecil, proses dapat dilakukan langsung jika masih aman.

Untuk batch besar, gunakan:

* queue;
* batch jobs;
* chunking;
* progress tracking;
* retry terbatas.

Rekomendasi:

```text
25–100 foto per job
```

Sesuaikan dengan memory dan waktu pemrosesan server.

Proses harus idempotent.

Jika job yang sama dijalankan ulang:

* jangan membuat file duplicate;
* jangan kehilangan foto lama;
* jangan menulis perubahan berkali-kali tanpa kebutuhan;
* gunakan batch item status dan identifier unik.

Setiap item diproses dengan urutan aman:

1. Pastikan item masih berstatus siap.
2. Pastikan user masih tersedia.
3. Pastikan NIS/NIP user masih cocok.
4. Baca file temporary.
5. Proses menggunakan `UserPhotoService`.
6. Simpan foto baru ke temporary final path.
7. Verifikasi foto hasil akhir.
8. Update `photo_path` user dalam transaction.
9. Setelah database berhasil, hapus foto lama jika replace.
10. Update status item.
11. Catat audit log.
12. Jika gagal, pertahankan foto lama dan bersihkan file baru.

Jangan menghapus foto lama sebelum foto baru berhasil diproses dan database berhasil diperbarui.

# K. Atomicity dan partial failure

Karena pemrosesan ratusan file melibatkan filesystem dan queue, jangan membuat satu database transaction besar untuk seluruh ZIP.

Gunakan:

```text
transaction per user/item
```

Batch dapat selesai dengan status:

```text
completed
completed_with_errors
failed
cancelled
```

Kegagalan satu foto tidak boleh merusak foto user lain.

Namun:

* preview harus selesai sebelum import;
* hanya item valid yang diproses;
* seluruh kegagalan harus tercatat;
* hasil akhir harus dapat direkonsiliasi.

# L. Struktur database batch

Gunakan tabel sesuai pola repository, misalnya:

```text
user_photo_import_batches
```

Field yang disarankan:

```text
id
uuid
matching_type
existing_photo_policy
original_filename
stored_zip_path
status
total_entries
total_photo_files
ready_new_count
ready_replace_count
skipped_count
failed_count
processed_count
uploaded_by
started_at
completed_at
expires_at
created_at
updated_at
```

Status batch:

```text
uploaded
inspecting
preview_ready
importing
completed
completed_with_errors
failed
cancelled
expired
```

Tambahkan tabel:

```text
user_photo_import_items
```

Field yang disarankan:

```text
id
batch_id
original_filename
temporary_path
detected_extension
detected_mime
identifier_type
identifier
user_id nullable
status
planned_action
error_code nullable
error_message nullable
old_photo_path nullable
new_photo_path nullable
input_size nullable
output_size nullable
processed_at nullable
created_at
updated_at
```

Unique constraint atau idempotency constraint harus mencegah item yang sama diproses berulang secara tidak sengaja.

Jangan menyimpan file binary di database.

# M. Service dan struktur kode

Gunakan separation of concerns.

Struktur yang dapat digunakan:

```text
app/Services/UserPhotoImportService.php
app/Services/UserPhotoZipInspector.php
app/Services/UserPhotoMatcher.php
app/Services/UserPhotoImportReportService.php
app/Jobs/ProcessUserPhotoImportBatch.php
app/Jobs/ProcessUserPhotoImportItem.php
app/Models/UserPhotoImportBatch.php
app/Models/UserPhotoImportItem.php
app/Http/Requests/Admin/UploadUserPhotoImportRequest.php
```

Gunakan struktur existing jika project memiliki pola lain.

Tanggung jawab:

## Controller

* authorization;
* menerima request;
* memanggil service;
* menampilkan response;
* redirect;
* flash message.

## ZIP inspector

* validasi ZIP;
* zip-slip protection;
* batas ukuran;
* entry inspection;
* MIME detection;
* ekstraksi aman.

## Matcher

* mengambil basename;
* menghapus extension;
* normalisasi aman;
* pencocokan exact NIS/NIP;
* duplicate detection;
* menghasilkan status preview.

## UserPhotoService

* orientasi;
* canvas 4:3;
* contain;
* resize;
* adaptive compression;
* storage;
* penggantian foto aman.

## Report service

* preview report;
* final report;
* CSV atau XLSX;
* formula-injection protection.

Jangan menaruh seluruh logika dalam controller atau Blade.

# N. Route

Tambahkan route sesuai konvensi project untuk:

```text
GET  /admin/users/photo-import
POST /admin/users/photo-import
GET  /admin/users/photo-import/{batch}
GET  /admin/users/photo-import/{batch}/report
POST /admin/users/photo-import/{batch}/confirm
POST /admin/users/photo-import/{batch}/cancel
GET  /admin/users/photo-import/{batch}/progress
```

Sesuaikan nama dan prefix dengan route existing.

Semua route harus:

* menggunakan auth;
* menggunakan permission;
* menggunakan policy;
* mencegah IDOR;
* memastikan user memiliki hak terhadap batch;
* menggunakan CSRF untuk operasi state-changing.

# O. Laporan

Sediakan laporan preview dan laporan akhir dalam XLSX atau CSV sesuai package existing.

Kolom minimal:

```text
original_filename
identifier_type
identifier
user_id
user_name
user_type
existing_photo
status
planned_action
error_code
reason
old_photo_path
new_photo_path
input_size
output_size
processed_at
```

Pastikan formula injection dicegah.

Nilai yang diawali:

```text
=
+
-
@
```

harus dinetralisasi sebelum export.

Jangan menampilkan private filesystem path lengkap kepada user. Jika menyimpan path untuk audit internal, laporan UI harus menampilkan versi aman.

# P. Audit log

Catat:

```text
bulk photo import uploaded
bulk photo preview generated
bulk photo import confirmed
bulk photo import cancelled
photo added through bulk import
photo replaced through bulk import
photo import item failed
bulk photo import completed
```

Audit minimal menyimpan:

```text
actor
batch UUID
user ID jika relevan
action
old photo indicator
new photo indicator
status
timestamp
```

Jangan mencatat:

* binary image;
* isi ZIP;
* credential;
* token;
* private path;
* metadata sensitif yang tidak diperlukan.

# Q. Permission

Gunakan atau tambahkan permission:

```text
users.bulk-import-photos
users.bulk-import-photos.preview
users.bulk-import-photos.apply
users.bulk-import-photos.download-report
```

Jika project menggunakan permission yang lebih sederhana, minimal seluruh fitur hanya tersedia bagi superadmin.

User biasa dan admin tanpa permission tidak boleh:

* melihat halaman;
* upload ZIP;
* melihat batch;
* mengunduh report;
* mengonfirmasi import.

# R. UI dan desain

Jangan melakukan redesign.

Gunakan:

* layout existing;
* card existing;
* form controls existing;
* alert existing;
* badge existing;
* modal existing;
* table existing;
* pagination existing;
* progress bar existing jika tersedia.

Halaman dapat menggunakan step:

```text
1. Choose Matching Type
2. Upload ZIP
3. Review Preview
4. Confirm Import
5. View Progress
6. Download Report
```

Tambahkan helper text:

```text
Rename each photo using the user's exact NIS or NIP before adding it to the ZIP. Example: 22001001.jpg. Photos will be placed into a 4:3 frame without cropping or stretching and optimized to approximately 300 KB.
```

# S. Progress import

Untuk proses queue, tampilkan:

```text
Processed 320 of 455 photos
Imported: 295
Replaced: 15
Failed: 10
Remaining: 135
```

Progress endpoint tidak boleh membocorkan data batch milik user lain.

Sediakan refresh otomatis yang ringan atau polling sesuai pola frontend existing.

Jangan menggunakan polling terlalu cepat.

# T. Cleanup

Setelah batch selesai atau kedaluwarsa:

* hapus ZIP temporary;
* hapus hasil ekstraksi;
* hapus temporary processed files;
* pertahankan hanya report dan metadata yang diperlukan;
* jangan menghapus foto final user;
* jangan menghapus foto lama yang masih dipakai.

Tambahkan scheduled command, misalnya:

```text
php artisan user-photos:cleanup-imports
```

atau gunakan scheduler existing.

Cleanup harus memeriksa status batch agar tidak menghapus file dari batch yang masih diproses.

# U. Automated tests

Tambahkan tests minimal.

## Authorization

```text
[ ] Superadmin dapat membuka halaman bulk import.
[ ] User tanpa permission mendapatkan 403.
[ ] User tidak dapat melihat batch milik konteks yang tidak valid.
[ ] User tanpa permission tidak dapat mengunduh report.
[ ] User tanpa permission tidak dapat mengonfirmasi import.
```

## ZIP validation

```text
[ ] File ZIP valid diterima.
[ ] File non-ZIP ditolak.
[ ] ZIP rusak ditolak.
[ ] Zip Slip ditolak.
[ ] Absolute path entry ditolak.
[ ] Symbolic link ditolak.
[ ] Nested ZIP ditolak.
[ ] Zip bomb atau compression ratio ekstrem ditolak.
[ ] Jumlah entry melewati batas ditolak.
[ ] Total extracted size melewati batas ditolak.
[ ] __MACOSX dan .DS_Store diabaikan.
```

## Filename matching

```text
[ ] JPG dengan nama NIS cocok ke student.
[ ] PNG dengan nama NIP cocok ke teacher/staff.
[ ] Leading zero dipertahankan.
[ ] Identifier tidak di-cast menjadi integer.
[ ] Nama file dengan suffix tambahan ditolak.
[ ] Dua file dengan identifier sama menjadi duplicate conflict.
[ ] NIS yang ditemukan pada dua user menjadi duplicate user conflict.
[ ] User tidak ditemukan menghasilkan USER_NOT_FOUND.
[ ] Mode NIS tidak mencocokkan NIP.
[ ] Mode NIP tidak mencocokkan student berdasarkan NIS.
[ ] Pencocokan dilakukan exact.
```

## Image validation

```text
[ ] JPG valid diterima.
[ ] JPEG valid diterima.
[ ] PNG valid diterima.
[ ] WebP valid diterima.
[ ] SVG ditolak.
[ ] File JPG palsu ditolak.
[ ] File rusak menghasilkan CORRUPTED_IMAGE.
[ ] File terlalu besar ditolak.
[ ] Dimensi ekstrem ditolak dengan aman.
```

## Preview

```text
[ ] Preview tidak mengubah users.photo_path.
[ ] Preview tidak menghapus foto lama.
[ ] User tanpa foto menjadi MATCHED_NEW.
[ ] User dengan foto dan mode skip menjadi SKIPPED_EXISTING.
[ ] User dengan foto dan mode replace menjadi MATCHED_REPLACE.
[ ] Ringkasan kategori sesuai hasil item.
[ ] Preview report dapat diunduh.
```

## Photo processing

```text
[ ] Foto diproses ke canvas 4:3.
[ ] Foto portrait tidak di-crop.
[ ] Foto landscape tidak di-crop.
[ ] Foto square tidak di-stretch.
[ ] Aspect ratio asli dipertahankan.
[ ] Area kosong ditambahkan dengan benar.
[ ] Foto diposisikan di tengah.
[ ] Output maksimal sekitar 300 KB.
[ ] EXIF orientation diperbaiki.
[ ] Metadata dibuang.
[ ] Temporary file dibersihkan.
```

## Apply import

```text
[ ] MATCHED_NEW memasang foto user.
[ ] MATCHED_REPLACE mengganti foto user.
[ ] SKIPPED_EXISTING tidak mengubah user.
[ ] USER_NOT_FOUND tidak membuat user.
[ ] Conflict tidak diproses.
[ ] Foto lama tetap ada jika foto baru gagal.
[ ] Foto lama dihapus setelah replace berhasil.
[ ] Kegagalan satu item tidak merusak item lain.
[ ] Proses ulang tidak membuat file duplicate.
[ ] Audit log tercatat.
[ ] Status batch selesai dengan benar.
```

## Cleanup

```text
[ ] ZIP temporary terhapus setelah selesai.
[ ] Extracted files terhapus setelah selesai.
[ ] Batch aktif tidak dibersihkan.
[ ] Batch expired dibersihkan.
[ ] Foto final user tidak ikut terhapus.
```

# V. Regression tests

Pastikan fitur existing tetap berfungsi:

```text
[ ] Create user.
[ ] Edit user.
[ ] Upload foto individual.
[ ] Replace foto individual.
[ ] Delete foto individual.
[ ] Import user Excel.
[ ] QR user.
[ ] Login.
[ ] OAuth authorize.
[ ] OAuth token.
[ ] OIDC discovery.
[ ] JWKS.
[ ] UserInfo.
[ ] Google OAuth.
[ ] User application access.
[ ] User synchronization API.
```

# W. Verifikasi

Jalankan:

```bash
php artisan test
php artisan migrate:status
php artisan route:list
```

Jalankan tool project lain jika tersedia:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run build
npm run lint
```

Jangan menyatakan test lulus jika command tidak benar-benar dijalankan.

Jika ada kegagalan existing yang tidak terkait fitur ini, laporkan secara terpisah.

# X. Output akhir agent

Setelah implementasi selesai, berikan laporan:

1. Ringkasan audit repository.
2. Arsitektur bulk photo import yang digunakan.
3. Batas keamanan ZIP.
4. Daftar format foto yang didukung.
5. Mekanisme filename matching.
6. Mekanisme pilihan NIS atau NIP.
7. Mekanisme preview.
8. Kategori status preview.
9. Kebijakan skip dan replace.
10. Mekanisme pemrosesan foto 4:3.
11. Mekanisme kompresi 300 KB.
12. Mekanisme queue dan chunk.
13. Mekanisme kegagalan per item.
14. Mekanisme cleanup.
15. Daftar migration.
16. Daftar model.
17. Daftar service.
18. Daftar job.
19. Daftar route.
20. Daftar permission.
21. Daftar audit event.
22. Daftar file yang ditambah.
23. Daftar file yang diubah.
24. Tests yang ditambahkan.
25. Hasil seluruh test.
26. Hasil lint dan build.
27. Risiko atau keterbatasan tersisa.
28. Perintah deployment.
29. Konfirmasi desain existing tidak dirombak.
30. Konfirmasi OAuth/OIDC tidak mengalami breaking change.

# Definition of done

Task dianggap selesai hanya jika:

* superadmin dapat meng-upload ZIP;
* admin wajib memilih mode NIS atau NIP;
* filename dicocokkan secara exact;
* leading zero tidak hilang;
* berbagai format foto yang didukung dapat diproses;
* file palsu dan berbahaya ditolak;
* Zip Slip dan zip bomb dicegah;
* file diekstrak ke private storage;
* preview tersedia sebelum perubahan;
* user cocok dan tidak cocok dilaporkan;
* duplicate file identifier dilaporkan;
* duplicate user identifier dilaporkan;
* kebijakan skip atau replace tersedia;
* preview tidak mengubah foto user;
* proses hanya dimulai setelah konfirmasi;
* foto menggunakan canvas 4:3;
* foto tidak di-crop;
* foto tidak di-stretch;
* metode contain digunakan;
* output maksimal sekitar 300 KB;
* foto lama aman jika proses gagal;
* laporan preview dan final tersedia;
* proses batch dapat dipantau;
* kegagalan satu item tidak merusak item lain;
* temporary files dibersihkan;
* seluruh endpoint memiliki authorization;
* seluruh aksi penting tercatat;
* desain existing tetap digunakan;
* login dan OAuth/OIDC tidak berubah secara breaking;
* automated tests dijalankan.
