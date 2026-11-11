<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

// --- Filter Parameters ---
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$kategori_id   = $_GET['kategori_id'] ?? '';
$lokasi_id     = $_GET['lokasi_id'] ?? '';

// --- Build WHERE clauses ---
$where_conditions = [];
$params = [];

if ($tanggal_mulai) {
    $where_conditions[] = 'sm.tanggal >= :tanggal_mulai';
    $params[':tanggal_mulai'] = $tanggal_mulai;
}
if ($tanggal_akhir) {
    $where_conditions[] = 'sm.tanggal <= :tanggal_akhir';
    $params[':tanggal_akhir'] = $tanggal_akhir;
}
if ($kategori_id) {
    $where_conditions[] = 'sm.kategori_id = :kategori_id';
    $params[':kategori_id'] = $kategori_id;
}
if ($lokasi_id) {
    $where_conditions[] = 'sm.lokasi_id = :lokasi_id';
    $params[':lokasi_id'] = $lokasi_id;
}

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// --- Summary Stats ---
$sql_summary = "
    SELECT
        COALESCE(SUM(sm.berat), 0) AS total_masuk,
        COALESCE(SUM(pk.total_dikelola), 0) AS total_dikelola
    FROM sampah_masuk sm
    LEFT JOIN (
        SELECT sampah_id, SUM(berat_dikelola) AS total_dikelola
        FROM pengelolaan
        GROUP BY sampah_id
    ) pk ON pk.sampah_id = sm.id
    $where_sql
";
$stmt = $pdo->prepare($sql_summary);
$stmt->execute($params);
$summary = $stmt->fetch();

$total_masuk    = (float) $summary['total_masuk'];
$total_dikelola = (float) $summary['total_dikelola'];
$total_belum    = $total_masuk - $total_dikelola;
$persentase     = $total_masuk > 0 ? round(($total_dikelola / $total_masuk) * 100, 1) : 0;

// --- Detail Data ---
$sql_detail = "
    SELECT sm.tanggal, ks.nama_kategori, l.nama_lokasi,
           sm.berat,
           COALESCE(pk.total_dikelola, 0) AS dikelola
    FROM sampah_masuk sm
    JOIN kategori_sampah ks ON sm.kategori_id = ks.id
    JOIN lokasi l ON sm.lokasi_id = l.id
    LEFT JOIN (
        SELECT sampah_id, SUM(berat_dikelola) AS total_dikelola
        FROM pengelolaan
        GROUP BY sampah_id
    ) pk ON pk.sampah_id = sm.id
    $where_sql
    ORDER BY sm.tanggal DESC, sm.id DESC
";
$stmt = $pdo->prepare($sql_detail);
$stmt->execute($params);
$detail_data = $stmt->fetchAll();

// --- Rekapitulasi berdasarkan Kategori ---
$sql_kategori = "
    SELECT ks.nama_kategori,
           SUM(sm.berat) AS total_masuk,
           COALESCE(SUM(pk.total_dikelola), 0) AS total_dikelola
    FROM sampah_masuk sm
    JOIN kategori_sampah ks ON sm.kategori_id = ks.id
    LEFT JOIN (
        SELECT sampah_id, SUM(berat_dikelola) AS total_dikelola
        FROM pengelolaan
        GROUP BY sampah_id
    ) pk ON pk.sampah_id = sm.id
    $where_sql
    GROUP BY ks.id, ks.nama_kategori
    ORDER BY ks.nama_kategori
";
$stmt = $pdo->prepare($sql_kategori);
$stmt->execute($params);
$rekap_kategori = $stmt->fetchAll();

// --- Rekapitulasi berdasarkan Jenis Pengelolaan ---
$sql_jenis = "
    SELECT p.jenis_pengolahan,
           SUM(p.berat_dikelola) AS total_berat,
           COUNT(p.id) AS jumlah_proses
    FROM pengelolaan p
    JOIN sampah_masuk sm ON p.sampah_id = sm.id
    $where_sql
    GROUP BY p.jenis_pengolahan
    ORDER BY p.jenis_pengolahan
";
$stmt = $pdo->prepare($sql_jenis);
$stmt->execute($params);
$rekap_jenis = $stmt->fetchAll();

// --- Output CSV ---
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_wastelog_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Title
fputcsv($output, ['LAPORAN PENGELOLAAN SAMPAH - WASTELOG']);

// Period
$periode = 'Periode: ';
if ($tanggal_mulai && $tanggal_akhir) {
    $periode .= $tanggal_mulai . ' s/d ' . $tanggal_akhir;
} elseif ($tanggal_mulai) {
    $periode .= 'Mulai ' . $tanggal_mulai;
} elseif ($tanggal_akhir) {
    $periode .= 'Sampai ' . $tanggal_akhir;
} else {
    $periode .= 'Semua Periode';
}
fputcsv($output, [$periode]);

// Blank row
fputcsv($output, []);

// Summary
fputcsv($output, ['Total Sampah Masuk (kg)', number_format($total_masuk, 2, '.', '')]);
fputcsv($output, ['Total Sampah Dikelola (kg)', number_format($total_dikelola, 2, '.', '')]);
fputcsv($output, ['Total Belum Dikelola (kg)', number_format($total_belum, 2, '.', '')]);
fputcsv($output, ['Persentase Pengelolaan (%)', number_format($persentase, 1, '.', '')]);

// Blank row
fputcsv($output, []);

// Detail Data
fputcsv($output, ['DETAIL DATA SAMPAH MASUK']);
fputcsv($output, ['No', 'Tanggal', 'Kategori', 'Lokasi', 'Berat (kg)', 'Dikelola (kg)', 'Sisa (kg)', 'Status']);

$no = 1;
foreach ($detail_data as $dd) {
    $dd_berat    = (float) $dd['berat'];
    $dd_dikelola = (float) $dd['dikelola'];
    $dd_sisa     = $dd_berat - $dd_dikelola;

    if ($dd_dikelola <= 0) {
        $status = 'Belum';
    } elseif ($dd_sisa > 0) {
        $status = 'Proses';
    } else {
        $status = 'Selesai';
    }

    fputcsv($output, [
        $no++,
        $dd['tanggal'],
        $dd['nama_kategori'],
        $dd['nama_lokasi'],
        number_format($dd_berat, 2, '.', ''),
        number_format($dd_dikelola, 2, '.', ''),
        number_format($dd_sisa, 2, '.', ''),
        $status,
    ]);
}

// Blank row
fputcsv($output, []);

// Rekap Kategori
fputcsv($output, ['REKAPITULASI BERDASARKAN KATEGORI']);
fputcsv($output, ['Kategori', 'Berat Masuk (kg)', 'Berat Dikelola (kg)', 'Belum Dikelola (kg)', 'Persentase (%)']);

foreach ($rekap_kategori as $rk) {
    $rk_masuk    = (float) $rk['total_masuk'];
    $rk_dikelola = (float) $rk['total_dikelola'];
    $rk_belum    = $rk_masuk - $rk_dikelola;
    $rk_persen   = $rk_masuk > 0 ? round(($rk_dikelola / $rk_masuk) * 100, 1) : 0;

    fputcsv($output, [
        $rk['nama_kategori'],
        number_format($rk_masuk, 2, '.', ''),
        number_format($rk_dikelola, 2, '.', ''),
        number_format($rk_belum, 2, '.', ''),
        number_format($rk_persen, 1, '.', ''),
    ]);
}

// Blank row
fputcsv($output, []);

// Rekap Jenis Pengelolaan
fputcsv($output, ['REKAPITULASI BERDASARKAN JENIS PENGELOLAAN']);
fputcsv($output, ['Jenis Pengelolaan', 'Total Berat Dikelola (kg)', 'Jumlah Proses']);

foreach ($rekap_jenis as $rj) {
    fputcsv($output, [
        $rj['jenis_pengolahan'],
        number_format((float) $rj['total_berat'], 2, '.', ''),
        $rj['jumlah_proses'],
    ]);
}

fclose($output);
exit;
