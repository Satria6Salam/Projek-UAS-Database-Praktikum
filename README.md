# MBG-APP - Sistem Manajemen Makan Bergizi Gratis

MBG-APP adalah aplikasi web berbasis PHP dan MariaDB/MySQL untuk membantu pengelolaan Program Makan Bergizi Gratis (MBG). Sistem ini menghubungkan Admin Pusat, Dapur Umum, Sekolah, dan Supplier dalam satu alur data terintegrasi, mulai dari pendataan master, pasokan bahan baku, pengiriman makanan, hingga presensi makan siswa.

## Tujuan Sistem

Aplikasi ini dibuat untuk mengurangi pencatatan manual pada proses operasional MBG. Dengan sistem ini, data distribusi makanan, stok bahan baku, status pengiriman, dan presensi siswa dapat dicatat secara lebih rapi, terhubung, dan mudah ditelusuri.

## Pengguna Sistem

1. **Admin**
   - Mengelola data master sekolah, siswa, dapur umum, staf dapur, supplier, bahan baku, dan akun pengguna.

2. **Dapur**
   - Mengelola menu makanan.
   - Memvalidasi pasokan bahan baku dari supplier.
   - Membuat manifes distribusi makanan ke sekolah.

3. **Sekolah**
   - Memantau status pengiriman makanan.
   - Mengonfirmasi penerimaan kiriman makanan.
   - Mencatat presensi makan siswa.

4. **Supplier**
   - Mencatat nota pasokan bahan baku.
   - Memantau riwayat dan status verifikasi pasokan.

## Fitur Utama

- Login multi-role berbasis hak akses.
- Dashboard berbeda untuk Admin, Dapur, Sekolah, dan Supplier.
- CRUD data master sekolah, siswa, dapur umum, staf dapur, supplier, bahan baku, dan pengguna.
- Pengelolaan menu dan rincian komposisi bahan baku per porsi.
- Input pasokan bahan baku oleh supplier.
- Verifikasi pasokan bahan oleh dapur dengan status `Pending`, `Disetujui`, atau `Ditolak`.
- Pembuatan manifes distribusi makanan dari dapur ke sekolah.
- Monitoring status pengiriman makanan oleh sekolah.
- Konfirmasi jumlah porsi makanan yang diterima sekolah.
- Presensi makan siswa berbasis data pengiriman dan porsi diterima.
- Perhitungan stok riil berdasarkan pasokan terverifikasi dan kebutuhan menu terkirim.

## Teknologi yang Digunakan

- PHP
- MariaDB/MySQL
- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- AJAX
- XAMPP/phpMyAdmin

## Struktur Folder

```text
mbg-app/
├── actions/
│   ├── admin/
│   ├── auth/
│   ├── dapur/
│   ├── sekolah/
│   └── supplier/
├── assets/
│   ├── css/
│   ├── images/
│   └── js/
├── config/
│   ├── database.php
│   ├── fungsi_stok.php
│   └── security.php
├── views/
│   ├── admin/
│   ├── dapur/
│   ├── sekolah/
│   ├── supplier/
│   └── templates/
└── index.php
```

## Tabel Database Utama

Database menggunakan nama default:

```text
mbg_app
```

Tabel utama yang digunakan:

- `pengguna`
- `sekolah`
- `siswa`
- `dapur_umum`
- `staf_dapur`
- `menu`
- `bahan_baku`
- `supplier`
- `detail_menu`
- `pasokan_bahan`
- `pengiriman`
- `presensi_makan`

## Alur Singkat Sistem

1. Admin membuat data master dan akun pengguna.
2. Supplier mencatat pasokan bahan baku ke dapur.
3. Dapur memverifikasi pasokan bahan.
4. Dapur membuat menu dan komposisi bahan.
5. Dapur membuat pengiriman makanan ke sekolah.
6. Sekolah memantau status pengiriman.
7. Sekolah mengonfirmasi jumlah porsi yang diterima.
8. Sekolah mencatat presensi makan siswa.

## Cara Menjalankan Project

1. Pastikan XAMPP sudah terpasang.
2. Jalankan service **Apache** dan **MySQL** melalui XAMPP Control Panel.
3. Letakkan folder project `mbg-app` ke dalam folder:

```text
C:\xampp\htdocs\
```

4. Buka phpMyAdmin melalui browser:

```text
http://localhost/phpmyadmin
```

5. Buat database baru dengan nama:

```text
mbg_app
```

6. Import file SQL database jika tersedia.
7. Pastikan konfigurasi database pada file `config/database.php` sesuai:

```php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "mbg_app";
```

8. Jalankan aplikasi melalui browser:

```text
http://localhost/mbg-app/
```

## Catatan Implementasi

- Sistem menggunakan session untuk menyimpan data login pengguna.
- Hak akses halaman dikontrol melalui `config/security.php`.
- Koneksi database menggunakan MySQLi pada `config/database.php`.
- Perhitungan stok bahan baku dibantu oleh `config/fungsi_stok.php`.
- Presensi makan siswa diproses secara AJAX melalui file `actions/sekolah/presensi_siswa.php`.

## Status Project

Project ini telah mencakup alur utama program MBG, yaitu pengelolaan data master, pasokan bahan baku, validasi logistik, distribusi makanan, verifikasi penerimaan oleh sekolah, dan presensi makan siswa. Sistem masih dapat dikembangkan lebih lanjut dengan fitur laporan cetak, grafik statistik, notifikasi real-time, dan ekspor data.
