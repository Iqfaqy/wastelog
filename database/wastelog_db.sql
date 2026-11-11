-- ============================================
-- WasteLog Database
-- Sistem Informasi Pencatatan dan Monitoring
-- Pengelolaan Sampah Berbasis Web
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `wastelog_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `wastelog_db`;

-- --------------------------------------------------------
-- Tabel: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengelolaan`;
DROP TABLE IF EXISTS `sampah_masuk`;
DROP TABLE IF EXISTS `lokasi`;
DROP TABLE IF EXISTS `kategori_sampah`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id`         INT NOT NULL AUTO_INCREMENT,
    `nama`       VARCHAR(100) NOT NULL,
    `username`   VARCHAR(50) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel: kategori_sampah
-- --------------------------------------------------------
CREATE TABLE `kategori_sampah` (
    `id`             INT NOT NULL AUTO_INCREMENT,
    `nama_kategori`  VARCHAR(100) NOT NULL,
    `deskripsi`      TEXT,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel: lokasi
-- --------------------------------------------------------
CREATE TABLE `lokasi` (
    `id`          INT NOT NULL AUTO_INCREMENT,
    `nama_lokasi` VARCHAR(100) NOT NULL,
    `alamat`      TEXT,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel: sampah_masuk
-- --------------------------------------------------------
CREATE TABLE `sampah_masuk` (
    `id`           INT NOT NULL AUTO_INCREMENT,
    `kategori_id`  INT NOT NULL,
    `lokasi_id`    INT NOT NULL,
    `tanggal`      DATE NOT NULL,
    `berat`        DECIMAL(10,2) NOT NULL,
    `keterangan`   TEXT,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kategori` (`kategori_id`),
    KEY `idx_lokasi` (`lokasi_id`),
    KEY `idx_tanggal` (`tanggal`),
    CONSTRAINT `fk_sampah_kategori`
        FOREIGN KEY (`kategori_id`) REFERENCES `kategori_sampah`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_sampah_lokasi`
        FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel: pengelolaan
-- --------------------------------------------------------
CREATE TABLE `pengelolaan` (
    `id`              INT NOT NULL AUTO_INCREMENT,
    `sampah_id`       INT NOT NULL,
    `tanggal`         DATE NOT NULL,
    `jenis_pengolahan` VARCHAR(100) NOT NULL,
    `berat_dikelola`  DECIMAL(10,2) NOT NULL,
    `keterangan`      TEXT,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sampah` (`sampah_id`),
    KEY `idx_tanggal_kelola` (`tanggal`),
    CONSTRAINT `fk_pengelolaan_sampah`
        FOREIGN KEY (`sampah_id`) REFERENCES `sampah_masuk`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- DATA DEMONSTRASI
-- ========================================================

-- Admin (password: admin123)
INSERT INTO `users` (`nama`, `username`, `password`) VALUES
('Administrator', 'admin', '$2y$10$536hGAbkzQah0ABtyHj06etfxwzxXWxXwsXEvj2UJGzweOVv.hSze');

-- Kategori Sampah
INSERT INTO `kategori_sampah` (`nama_kategori`, `deskripsi`) VALUES
('Organik',    'Sampah yang berasal dari bahan-bahan alami dan mudah terurai secara biologis, seperti sisa makanan, daun, dan ranting.'),
('Anorganik',  'Sampah yang tidak mudah terurai secara alami, seperti plastik, kaca, logam, dan kertas.'),
('Residu',     'Sampah sisa yang tidak dapat didaur ulang atau dikomposkan, biasanya berakhir di TPA.'),
('B3',         'Sampah Bahan Berbahaya dan Beracun, seperti baterai, limbah kimia, obat kadaluarsa, dan lampu neon.');

-- Lokasi
INSERT INTO `lokasi` (`nama_lokasi`, `alamat`) VALUES
('Gedung A',      'Gedung utama kampus, lantai 1-3, area administrasi dan ruang kuliah.'),
('Gedung B',      'Gedung perkuliahan blok B, lantai 1-4.'),
('Kantin',        'Area kantin pusat kampus, dekat Gedung A.'),
('Laboratorium',  'Gedung laboratorium terpadu, lantai 1-2.'),
('Area Parkir',   'Area parkir utama kampus, sisi barat.');

-- Sampah Masuk (data demonstrasi)
INSERT INTO `sampah_masuk` (`kategori_id`, `lokasi_id`, `tanggal`, `berat`, `keterangan`) VALUES
(1, 3, '2026-11-01', 50.00, 'Sisa makanan dari kantin kampus'),
(2, 1, '2026-11-02', 30.00, 'Botol plastik dan kertas dari Gedung A'),
(3, 2, '2026-11-03', 20.00, 'Residu campuran dari Gedung B'),
(4, 4, '2026-11-04',  5.00, 'Limbah baterai dan bahan kimia laboratorium'),
(1, 5, '2026-11-05', 15.00, 'Daun dan ranting dari area parkir'),
(2, 3, '2026-11-06', 12.00, 'Kemasan plastik makanan dari kantin'),
(1, 1, '2026-11-07', 25.00, 'Sisa makanan dan sampah organik Gedung A'),
(3, 4, '2026-11-08',  8.00, 'Residu dari laboratorium'),
(2, 2, '2026-11-09', 18.00, 'Sampah kertas dan plastik Gedung B'),
(1, 3, '2026-11-10', 35.00, 'Sisa makanan akhir pekan kantin');

-- Pengelolaan Sampah (data demonstrasi)
INSERT INTO `pengelolaan` (`sampah_id`, `tanggal`, `jenis_pengolahan`, `berat_dikelola`, `keterangan`) VALUES
(1, '2026-11-02', 'Pengomposan',                 30.00, 'Dikomposkan di area kompos kampus'),
(1, '2026-11-03', 'Pengomposan',                 15.00, 'Lanjutan pengomposan sisa makanan kantin'),
(2, '2026-11-03', 'Daur ulang',                  15.00, 'Botol plastik didaur ulang'),
(2, '2026-11-04', 'Daur ulang',                  10.00, 'Kertas didaur ulang'),
(3, '2026-11-05', 'Pengangkutan ke pengelola',    10.00, 'Diangkut ke TPA kota'),
(4, '2026-11-06', 'Pengolahan lainnya',            2.00, 'Dicatat dan disiapkan untuk penangan B3 khusus'),
(5, '2026-11-06', 'Pengomposan',                 10.00, 'Daun dan ranting dikomposkan'),
(6, '2026-11-07', 'Daur ulang',                   8.00, 'Kemasan plastik didaur ulang'),
(7, '2026-11-08', 'Pengomposan',                 20.00, 'Sampah organik Gedung A dikomposkan');
