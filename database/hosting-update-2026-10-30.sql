-- UPDATE HOSTING: seluruh perubahan database sesi 30 Oktober 2026.
-- MySQL / MariaDB. Pilih database aplikasi di phpMyAdmin sebelum import.
-- BACKUP database dan upload kode aplikasi terbaru terlebih dahulu.
-- Jalankan pemeriksaan ini dahulu. Hasil harus KOSONG sebelum lanjut.
-- Jika ada hasil, gabungkan data listrik/air ganda secara manual, jangan hapus otomatis.
SELECT id_kavling, COUNT(*) AS jumlah_data
FROM listrik_air GROUP BY id_kavling HAVING COUNT(*) > 1;

-- Hentikan import jika ada error. DDL MySQL melakukan implicit commit.
-- 1. Hapus tabel khusus Panduan Aplikasi beserta seluruh isinya.
DROP TABLE IF EXISTS hak_akses_panduan_apk;
DROP TABLE IF EXISTS menu_panduan_aplikasi;

INSERT INTO migrations (migration, batch)
SELECT '2026_10_30_000000_remove_panduan_aplikasi_tables', COALESCE(MAX(batch), 0) + 1
FROM migrations
HAVING NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_10_30_000000_remove_panduan_aplikasi_tables');

-- 2. Nomor rekening dapat diisi listrik dulu atau air dulu.
ALTER TABLE listrik_air
    MODIFY norek_listrik VARCHAR(35) NULL DEFAULT NULL,
    MODIFY norek_air VARCHAR(35) NULL DEFAULT NULL;

INSERT INTO migrations (migration, batch)
SELECT '2026_10_30_010000_make_listrik_air_accounts_nullable', COALESCE(MAX(batch), 0) + 1
FROM migrations
HAVING NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_10_30_010000_make_listrik_air_accounts_nullable');

-- 3. Satu unit hanya boleh memiliki satu data listrik/air.
-- Index tidak ditambah lagi jika sudah tersedia.
SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'listrik_air'
      AND index_name = 'listrik_air_unit_unique'
);
SET @index_sql = IF(@index_exists > 0, 'SELECT 1',
    'ALTER TABLE listrik_air ADD UNIQUE INDEX listrik_air_unit_unique (id_kavling)');
PREPARE nazura_index_statement FROM @index_sql;
EXECUTE nazura_index_statement;
DEALLOCATE PREPARE nazura_index_statement;

INSERT INTO migrations (migration, batch)
SELECT '2026_10_30_020000_add_unique_unit_to_listrik_air', COALESCE(MAX(batch), 0) + 1
FROM migrations
HAVING NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_10_30_020000_add_unique_unit_to_listrik_air')
   AND EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE()
       AND table_name = 'listrik_air' AND index_name = 'listrik_air_unit_unique' AND non_unique = 0);

-- 4. Menu Berkas Transaksi setelah Transaksi, dengan submenu PPJB dan BAST.
-- ID menu diambil dari database hosting, bukan dari ID lokal.
START TRANSACTION;
SET @transaksi_order = (SELECT urutan FROM menu WHERE title = 'Transaksi' AND id_parent = 0 LIMIT 1);
SET @berkas_id = (SELECT id FROM menu WHERE title = 'Berkas Transaksi' AND id_parent = 0 LIMIT 1);
SET @children_count = (SELECT COUNT(*) FROM menu WHERE route_name IN ('ppjb.index', 'bast.index'));
SET @menu_ready = (@transaksi_order IS NOT NULL AND @children_count = 2);

UPDATE menu SET urutan = urutan + 1
WHERE id_parent = 0 AND urutan > @transaksi_order AND @berkas_id IS NULL AND @menu_ready;

INSERT INTO menu (id_parent, title, route_name, icon, urutan, lihat, tambah, edit, hapus)
SELECT 0, 'Berkas Transaksi', '#', 'fa-folder-open', @transaksi_order + 1, 1, 0, 0, 0
WHERE @berkas_id IS NULL AND @menu_ready;

SET @berkas_id = (SELECT id FROM menu WHERE title = 'Berkas Transaksi' AND id_parent = 0 LIMIT 1);

-- Akses induk mengikuti hak lihat submenu yang sudah dimiliki pengguna.
UPDATE hak_akses AS parent_access
JOIN (
    SELECT access.id_user, MAX(access.lihat) AS lihat
    FROM hak_akses AS access JOIN menu AS child ON child.id = access.id_menu
    WHERE child.route_name IN ('ppjb.index', 'bast.index') GROUP BY access.id_user
) AS child_access ON child_access.id_user = parent_access.id_user
SET parent_access.lihat = child_access.lihat,
    parent_access.beranda = 0, parent_access.tambah = 0, parent_access.edit = 0, parent_access.hapus = 0
WHERE parent_access.id_menu = @berkas_id AND @menu_ready;

INSERT INTO hak_akses (id_user, id_menu, lihat, beranda, tambah, edit, hapus)
SELECT access.id_user, @berkas_id, MAX(access.lihat), 0, 0, 0, 0
FROM hak_akses AS access JOIN menu AS child ON child.id = access.id_menu
WHERE child.route_name IN ('ppjb.index', 'bast.index') AND @menu_ready AND @berkas_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM hak_akses AS existing WHERE existing.id_user = access.id_user AND existing.id_menu = @berkas_id)
GROUP BY access.id_user;

-- Akses induk untuk template role, tanpa mengubah hak akses PPJB/BAST.
UPDATE role_user AS parent_access
JOIN (
    SELECT access.id_role, MAX(access.lihat) AS lihat
    FROM role_user AS access JOIN menu AS child ON child.id = access.id_menu
    WHERE child.route_name IN ('ppjb.index', 'bast.index') GROUP BY access.id_role
) AS child_access ON child_access.id_role = parent_access.id_role
SET parent_access.lihat = child_access.lihat,
    parent_access.beranda = 0, parent_access.tambah = 0, parent_access.edit = 0, parent_access.hapus = 0
WHERE parent_access.id_menu = @berkas_id AND @menu_ready;

INSERT INTO role_user (id_role, id_menu, lihat, beranda, tambah, edit, hapus)
SELECT access.id_role, @berkas_id, MAX(access.lihat), 0, 0, 0, 0
FROM role_user AS access JOIN menu AS child ON child.id = access.id_menu
WHERE child.route_name IN ('ppjb.index', 'bast.index') AND @menu_ready AND @berkas_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM role_user AS existing WHERE existing.id_role = access.id_role AND existing.id_menu = @berkas_id)
GROUP BY access.id_role;

UPDATE menu
SET id_parent = @berkas_id, urutan = CASE route_name WHEN 'ppjb.index' THEN 1 ELSE 2 END
WHERE route_name IN ('ppjb.index', 'bast.index') AND @menu_ready AND @berkas_id IS NOT NULL;

INSERT INTO migrations (migration, batch)
SELECT '2026_10_30_030000_add_berkas_transaksi_menu', COALESCE(MAX(batch), 0) + 1
FROM migrations
HAVING NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_10_30_030000_add_berkas_transaksi_menu')
    AND @menu_ready AND @berkas_id IS NOT NULL;
COMMIT;

-- Pemeriksaan akhir: harus ada induk Berkas Transaksi dengan PPJB dan BAST.
SELECT parent.title AS menu_induk, child.title AS submenu, child.route_name, child.urutan
FROM menu AS child JOIN menu AS parent ON parent.id = child.id_parent
WHERE child.route_name IN ('ppjb.index', 'bast.index') ORDER BY child.urutan;

SELECT IF(@menu_ready AND @berkas_id IS NOT NULL,
    'OK: menu Berkas Transaksi selesai',
    'PERIKSA: menu Transaksi, PPJB, atau BAST belum tersedia/duplikat') AS status_menu;

-- Perubahan dashboard, siteplan, lampiran, dan tema hanya memerlukan upload kode.
-- Migration dicatat agar php artisan migrate tidak mengulang perubahan manual ini.
