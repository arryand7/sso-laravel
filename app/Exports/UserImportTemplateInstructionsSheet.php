<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserImportTemplateInstructionsSheet implements FromArray, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        return [
            ['PETUNJUK PENGGUNAAN TEMPLATE IMPORT USER — SABIRA CONNECT'],
            [''],
            ['Versi Template: SABIRA_USER_IMPORT_V1'],
            [''],
            ['KOLOM', 'WAJIB', 'KETERANGAN'],
            ['username', 'Ya', 'Username unik untuk login. Diperlakukan sebagai teks (string). Jangan mengubah format cell.'],
            ['name', 'Ya', 'Nama lengkap user.'],
            ['email', 'Ya', 'Alamat email yang valid dan unik.'],
            ['type', 'Ya', 'Tipe user: student, teacher, parent, staff, admin. Lihat sheet References.'],
            ['nis', 'Wajib untuk student', 'Nomor Induk Siswa. Format teks — pastikan cell diformat sebagai Text agar leading zero tidak hilang.'],
            ['nip', 'Wajib untuk teacher/staff', 'Nomor Induk Pegawai. Format teks — pastikan cell diformat sebagai Text.'],
            ['status', 'Ya', 'Status user: active, suspended, pending. Lihat sheet References.'],
            ['qr_code', 'Tidak', 'Kode QR kartu anggota. Unik jika diisi. Format teks.'],
            ['password', 'Tidak', 'Password user. Kosongkan untuk password acak (user baru) atau mempertahankan password lama (update).'],
            ['user_id', 'Tidak', 'ID internal user (untuk mode Update). Kosongkan untuk user baru.'],
            [''],
            ['ATURAN PENTING:'],
            ['1. Jangan mengubah header pada baris pertama sheet Users.'],
            ['2. Kolom username, nis, nip, dan qr_code HARUS diformat sebagai Text agar leading zero tidak hilang.'],
            ['3. Jangan gunakan formula pada kolom identifier (username, nis, nip, qr_code).'],
            ['4. NIS/NIP yang terbaca sebagai scientific notation (contoh: 2.2001E+07) akan DITOLAK.'],
            ['5. Untuk membuat user baru, kosongkan kolom user_id.'],
            ['6. Untuk mengupdate user, isi kolom user_id dengan ID internal yang valid.'],
            ['7. Email harus unik — email yang sudah dipakai user lain akan ditolak.'],
            ['8. QR code harus unik — QR yang sudah dipakai akan ditolak.'],
            ['9. Foto profil TIDAK diimport melalui template ini.'],
            ['10. Kolom password opsional. Jika diisi, password user akan di-set sesuai nilai tersebut. Jika dikosongkan pada user baru, password acak akan dibuat. Jika dikosongkan saat update, password lama tetap dipertahankan.'],
            [''],
            ['CONTOH DATA:'],
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'password'],
            ['22001001', 'Ahmad Siswa', 'ahmad@sabira.id', 'student', '22001001', '', 'active', '00001234', 'secret123'],
            ['19850101', 'Budi Guru', 'budi@sabira.id', 'teacher', '', '19850101', 'active', '00005678', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(80);

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true, 'color' => ['rgb' => '2463EB']]],
            5 => ['font' => ['bold' => true]],
            16 => ['font' => ['bold' => true]],
            28 => ['font' => ['bold' => true]],
            29 => ['font' => ['bold' => true, 'color' => ['rgb' => '999999']]],
        ];
    }
}
