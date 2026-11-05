-- Perubahan 5 November 2026 (MySQL/MariaDB).
-- Pilih database aplikasi sebelum menjalankan file ini.
-- Revisi pemasukan/dashboard hanya mengubah PHP/Blade, tanpa perubahan schema.
-- Script ini dapat dijalankan ulang tanpa menimpa pengaturan yang sudah ada.

CREATE TABLE IF NOT EXISTS `jenis_berkas_pengajuan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(150) NOT NULL,
    `singkatan` VARCHAR(30) NOT NULL,
    `kolom_lama` VARCHAR(255) NULL,
    `urutan` INT UNSIGNED NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @ddl_berkas = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'persyaratan_legal'
          AND COLUMN_NAME = 'status_berkas_dinamis'
    ),
    'SELECT ''Kolom status_berkas_dinamis sudah tersedia'' AS info',
    'ALTER TABLE `persyaratan_legal` ADD COLUMN `status_berkas_dinamis` JSON NULL'
);
PREPARE stmt_berkas FROM @ddl_berkas;
EXECUTE stmt_berkas;
DEALLOCATE PREPARE stmt_berkas;

START TRANSACTION;

-- Status lama tetap berada di kolom lama, agar modul Akad tetap sinkron.
INSERT INTO `jenis_berkas_pengajuan`
    (`nama`, `singkatan`, `kolom_lama`, `urutan`, `aktif`, `created_at`, `updated_at`)
SELECT seed.nama, seed.nama, seed.kolom_lama, seed.urutan, 1, NOW(), NOW()
FROM (
    SELECT 'IPH' AS nama, 'IPH' AS kolom_lama, 1 AS urutan
    UNION ALL SELECT 'SHGB', 'SHGB', 2
    UNION ALL SELECT 'SSP', 'SSP', 3
    UNION ALL SELECT 'BPHTB', 'BPHTB', 4
    UNION ALL SELECT 'SIKUMBANG', 'SIKUMBANG', 5
    UNION ALL SELECT 'DAFTAR SIKASEP', 'DAFTAR_SIKASEP', 6
    UNION ALL SELECT 'FOTO SIKASEP', 'FOTO_SIKASEP', 7
    UNION ALL SELECT 'TRILOGI', 'TRILOGI', 8
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM `jenis_berkas_pengajuan` existing
    WHERE existing.kolom_lama = seed.kolom_lama
);

-- Tempatkan menu baru pada induk yang sama dengan Pengajuan Berkas.
SET @pengajuan_menu_id = (
    SELECT id FROM `menu` WHERE route_name = 'pengajuan-berkas.index' LIMIT 1
);
SET @legal_parent_id = (
    SELECT id_parent FROM `menu` WHERE id = @pengajuan_menu_id
);
SET @jenis_urutan = (
    SELECT COALESCE(MAX(urutan), 0) + 1 FROM `menu` WHERE id_parent = @legal_parent_id
);

INSERT INTO `menu`
    (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @legal_parent_id, 'Jenis Berkas Pengajuan', 'jenis-berkas-pengajuan.index',
       'fas fa-list', @jenis_urutan, 1, 1, 1, 1
WHERE @pengajuan_menu_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu` WHERE route_name = 'jenis-berkas-pengajuan.index');

SET @jenis_menu_id = (
    SELECT id FROM `menu` WHERE route_name = 'jenis-berkas-pengajuan.index' LIMIT 1
);

-- Izin mengatur jenis berkas mengikuti izin edit Pengajuan Berkas.
INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `tambah`, `edit`, `hapus`, `beranda`)
SELECT sumber.id_user, @jenis_menu_id, sumber.edit, sumber.edit, sumber.edit, sumber.edit, 0
FROM `hak_akses` sumber
WHERE sumber.id_menu = @pengajuan_menu_id
  AND @jenis_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `hak_akses` tujuan
      WHERE tujuan.id_user = sumber.id_user AND tujuan.id_menu = @jenis_menu_id
  );

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `tambah`, `edit`, `hapus`, `beranda`)
SELECT sumber.id_role, @jenis_menu_id, sumber.edit, sumber.edit, sumber.edit, sumber.edit, 0
FROM `role_user` sumber
WHERE sumber.id_menu = @pengajuan_menu_id
  AND @jenis_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `role_user` tujuan
      WHERE tujuan.id_role = sumber.id_role AND tujuan.id_menu = @jenis_menu_id
  );

-- Catat migration agar php artisan migrate tidak menjalankannya lagi.
SET @jenis_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_05_120000_create_jenis_berkas_pengajuan', @jenis_batch
WHERE @jenis_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `migrations`
      WHERE migration = '2026_11_05_120000_create_jenis_berkas_pengajuan'
  );

COMMIT;

SELECT IF(@jenis_menu_id IS NULL,
    'Menu sumber pengajuan-berkas.index tidak ditemukan; periksa database yang dipilih.',
    'Perubahan jenis berkas pengajuan selesai.') AS hasil;
