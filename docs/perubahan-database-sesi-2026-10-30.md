# Catatan perubahan database — sesi 30 Oktober 2026

Catatan ini diperbarui sepanjang revisi sesi ini untuk panduan update hosting.

## Paket SQL hosting

- File gabungan: `database/hosting-update-2026-10-30.sql`.
- Mencakup seluruh 4 migration sesi ini dan mencatatnya ke tabel `migrations` agar tidak dijalankan ulang oleh Artisan.
- Diuji dua kali pada salinan tabel terisolasi di database lokal, termasuk kondisi SQL dijalankan ulang. Seluruh tabel pengujian dibersihkan setelah selesai.
- Backup hosting terlebih dahulu. Jalankan pemeriksaan duplikasi di awal file secara terpisah; lanjut import hanya jika hasilnya kosong. Hentikan jika terdapat error.
- Upload kode aplikasi terbaru juga, karena perubahan tampilan dan perilaku aplikasi tidak tercakup SQL.

## 1. Hapus Panduan Aplikasi

- Migration: `database/migrations/2026_10_30_000000_remove_panduan_aplikasi_tables.php`.
- Menghapus tabel `hak_akses_panduan_apk` terlebih dahulu, lalu `menu_panduan_aplikasi`, termasuk seluruh datanya.
- Tabel bersama seperti `role` tetap digunakan aplikasi dan tidak dihapus.
- Deploy kode terbaru dan backup database hosting sebelum menjalankan migration.
- Perintah khusus perubahan ini: `php artisan migrate --path=database/migrations/2026_10_30_000000_remove_panduan_aplikasi_tables.php --force`.
- Penghapusan data tidak dapat di-rollback; pemulihan memerlukan backup.
- Status eksekusi lokal: berhasil dijalankan; kedua tabel telah dihapus.

## 2. Input listrik dan air secara bertahap

- Migration: `database/migrations/2026_10_30_010000_make_listrik_air_accounts_nullable.php`.
- Mengubah kolom `listrik_air.norek_listrik` dan `listrik_air.norek_air` menjadi nullable; panjang tetap 35 karakter dan data lama tetap tersimpan.
- Form tambah/edit menerima listrik saja atau air saja; minimal salah satu nomor rekening wajib diisi. Foto opsional.
- Perintah hosting: `php artisan migrate --path=database/migrations/2026_10_30_010000_make_listrik_air_accounts_nullable.php --force`.
- Status eksekusi lokal: berhasil dijalankan. Validasi tambah/edit dan penyimpanan listrik saja/air saja berhasil diuji dengan transaksi yang di-rollback.

## 3. Cegah data listrik/air ganda per unit

- Migration: `database/migrations/2026_10_30_020000_add_unique_unit_to_listrik_air.php`.
- Menambah unique index `listrik_air_unit_unique` pada `listrik_air.id_kavling`.
- Satu unit hanya boleh memiliki satu data listrik/air, termasuk jika baru diisi salah satunya. Data dilengkapi lewat Edit.
- Perintah hosting: `php artisan migrate --path=database/migrations/2026_10_30_020000_add_unique_unit_to_listrik_air.php --force`.
- Jika hosting memiliki data ganda, migration berhenti tanpa menghapusnya. Gabungkan data ganda dahulu, lalu jalankan ulang migration.
- Status lokal: tidak ditemukan duplikasi; migration berhasil dijalankan.
- Pengujian transaksi rollback berhasil: penolakan duplikasi, filter unit tambah, unit saat edit, unggah PDF, penggantian file, dan penghapusan lampiran.
- Pratinjau, hapus, dan ganti lampiran menggunakan kolom foto yang sudah tersedia; tidak menambah kolom database.

## 4. Samakan hitungan Proses Bank dan dashboard

- Tidak ada perubahan struktur database atau migration baru.
- Menu, kartu dashboard/beranda, rekap Proses Bank per proyek, detail kartu dashboard, dan export Excel menggunakan kriteria Proses Bank aktif: wawancara status 1, customer tidak diarsipkan, dan belum selesai akad (detail akad status 2 dengan induk akad tersedia).
- Audit awal: dashboard/beranda 5, menu 3; selisih 2 merupakan customer yang sudah selesai akad.

## 5. Dropdown siteplan dan ekspor mandiri

- Tidak ada perubahan database atau migration baru.
- Lokasi dan jenis siteplan menggunakan dropdown seperti referensi Apmaland; lokasi terpilih dipertahankan ketika mengganti jenis siteplan.
- Sidebar hanya menampilkan satu tautan Siteplan tanpa submenu; pilihan jenis tetap melalui dropdown halaman sesuai hak akses. Tidak menghapus data menu/hak akses database.
- Berlaku untuk siteplan Penjualan, Balik Nama, BPHTB/SSP, Air, dan Listrik.
- Script bersama: `app/Services/SiteplanSelection.php`, `app/Services/SiteplanPrint.php`, serta view `admin/siteplan/selector.blade.php` dan `admin/siteplan/export.blade.php`.
- PDF dirender langsung menggunakan TCPDF; JPG dibuat di browser menggunakan SVG dan canvas. Tidak memakai API konversi eksternal atau tambahan dependency hosting.
- Pemeriksaan berhasil: render dropdown, pemilihan satu lokasi, respons PDF, dan halaman script JPG untuk 5 jenis siteplan pada 4 lokasi berdenah, dengan HTTP eksternal diblokir selama pengujian.

## 6. Tema warna logo Nazura Group

- Tidak ada perubahan database atau migration baru.
- Tema utama menggunakan biru `#324697`, biru gelap `#1c2852`, dan aksen merah `#df1b23` berdasarkan logo perusahaan yang aktif.
- File tema: `public/assets/css/nazura-theme.css`, dimuat oleh layout admin dengan versi berdasarkan waktu perubahan file.
- Sidebar, navbar, tombol utama, modal, pagination, form, beranda, dan login disesuaikan. Warna indikator status tetap digunakan sesuai fungsinya; mode gelap tetap didukung.
- Kompilasi Blade serta render layout admin dan login berhasil diperiksa.

## 7. Menu Berkas Transaksi

- Migration: `database/migrations/2026_10_30_030000_add_berkas_transaksi_menu.php`.
- Menambah menu induk **Berkas Transaksi** setelah Transaksi dan memindahkan menu PPJB serta BAST ke bawahnya (urutan PPJB, BAST).
- Mengubah data tabel `menu`, `hak_akses`, dan `role_user`; tidak menambah tabel atau kolom.
- Akses lihat menu induk mengikuti akses lihat PPJB/BAST yang sudah ada, untuk pengguna dan role. Hak akses submenu tetap utuh.
- Tidak mengubah dokumen atau data transaksi PPJB/BAST.
- Perintah hosting: `php artisan migrate --path=database/migrations/2026_10_30_030000_add_berkas_transaksi_menu.php --force`.
- Status lokal: migration berhasil dijalankan.
