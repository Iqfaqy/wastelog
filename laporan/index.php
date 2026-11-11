<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Laporan';
$active_menu = 'laporan';

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

// --- Fetch filter dropdown data ---
$kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori_sampah ORDER BY nama_kategori")->fetchAll();
$lokasi_list   = $pdo->query("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi")->fetchAll();

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

$total_masuk     = (float) $summary['total_masuk'];
$total_dikelola  = (float) $summary['total_dikelola'];
$total_belum     = $total_masuk - $total_dikelola;
$persentase      = $total_masuk > 0 ? round(($total_dikelola / $total_masuk) * 100, 1) : 0;

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

// --- Rekapitulasi berdasarkan Lokasi ---
$sql_lokasi = "
    SELECT l.nama_lokasi,
           SUM(sm.berat) AS total_masuk,
           COALESCE(SUM(pk.total_dikelola), 0) AS total_dikelola
    FROM sampah_masuk sm
    JOIN lokasi l ON sm.lokasi_id = l.id
    LEFT JOIN (
        SELECT sampah_id, SUM(berat_dikelola) AS total_dikelola
        FROM pengelolaan
        GROUP BY sampah_id
    ) pk ON pk.sampah_id = sm.id
    $where_sql
    GROUP BY l.id, l.nama_lokasi
    ORDER BY l.nama_lokasi
";
$stmt = $pdo->prepare($sql_lokasi);
$stmt->execute($params);
$rekap_lokasi = $stmt->fetchAll();

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

// --- Detail Data Sampah Masuk ---
$sql_detail = "
    SELECT sm.id, sm.tanggal, ks.nama_kategori, l.nama_lokasi,
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

// --- Build export query string ---
$export_params = http_build_query(array_filter([
    'tanggal_mulai' => $tanggal_mulai,
    'tanggal_akhir' => $tanggal_akhir,
    'kategori_id'   => $kategori_id,
    'lokasi_id'     => $lokasi_id,
]));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: #fff; }
}
</style>

<!-- Filter Bar -->
<div class="filter-bar no-print">
    <form method="GET" action="">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control" value="<?= e($tanggal_mulai) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= e($tanggal_akhir) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Kategori</label>
                <select name="kategori_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= e($kat['id']) ?>" <?= $kategori_id == $kat['id'] ? 'selected' : '' ?>>
                            <?= e($kat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Lokasi</label>
                <select name="lokasi_id" class="form-select">
                    <option value="">Semua Lokasi</option>
                    <?php foreach ($lokasi_list as $lok): ?>
                        <option value="<?= e($lok['id']) ?>" <?= $lokasi_id == $lok['id'] ? 'selected' : '' ?>>
                            <?= e($lok['nama_lokasi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Tampilkan
                </button>
                <a href="<?= BASE_URL ?>/laporan/index.php" class="btn btn-outline-secondary ms-1">Reset</a>
            </div>
            <div class="col-md-auto ms-auto">
                <button type="button" class="btn btn-secondary" onclick="cetakLaporan()">
                    <i class="fas fa-print me-1"></i>Cetak Laporan
                </button>
                <a href="<?= BASE_URL ?>/laporan/export.php?<?= e($export_params) ?>" class="btn btn-success ms-1">
                    <i class="fas fa-file-csv me-1"></i>Ekspor CSV
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Report Header (visible in print) -->
<div class="text-center mb-4 mt-3">
    <h3><strong>LAPORAN PENGELOLAAN SAMPAH</strong></h3>
    <p class="text-muted">
        Periode:
        <?php if ($tanggal_mulai && $tanggal_akhir): ?>
            <?= e(format_tanggal($tanggal_mulai)) ?> s/d <?= e(format_tanggal($tanggal_akhir)) ?>
        <?php elseif ($tanggal_mulai): ?>
            Mulai <?= e(format_tanggal($tanggal_mulai)) ?>
        <?php elseif ($tanggal_akhir): ?>
            Sampai <?= e(format_tanggal($tanggal_akhir)) ?>
        <?php else: ?>
            Semua Periode
        <?php endif; ?>
    </p>
</div>

<!-- Summary Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft">
                <i class="fas fa-weight-hanging"></i>
            </div>
            <div class="stat-info">
                <span>Total Sampah Masuk</span>
                <strong><?= e(format_angka($total_masuk, 2)) ?> kg</strong>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft">
                <i class="fas fa-recycle"></i>
            </div>
            <div class="stat-info">
                <span>Total Sampah Dikelola</span>
                <strong><?= e(format_angka($total_dikelola, 2)) ?> kg</strong>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <span>Total Belum Dikelola</span>
                <strong><?= e(format_angka($total_belum, 2)) ?> kg</strong>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-info">
                <span>Persentase Pengelolaan</span>
                <strong><?= e(format_angka($persentase, 1)) ?>%</strong>
            </div>
        </div>
    </div>
</div>

<!-- Rekapitulasi berdasarkan Kategori -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Rekapitulasi berdasarkan Kategori</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kategori</th>
                        <th class="text-end">Berat Masuk (kg)</th>
                        <th class="text-end">Berat Dikelola (kg)</th>
                        <th class="text-end">Belum Dikelola (kg)</th>
                        <th class="text-end">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rekap_kategori)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($rekap_kategori as $rk): ?>
                            <?php
                                $rk_masuk    = (float) $rk['total_masuk'];
                                $rk_dikelola = (float) $rk['total_dikelola'];
                                $rk_belum    = $rk_masuk - $rk_dikelola;
                                $rk_persen   = $rk_masuk > 0 ? round(($rk_dikelola / $rk_masuk) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= e($rk['nama_kategori']) ?></td>
                                <td class="text-end"><?= e(format_angka($rk_masuk, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($rk_dikelola, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($rk_belum, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($rk_persen, 1)) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rekapitulasi berdasarkan Lokasi -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Rekapitulasi berdasarkan Lokasi</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lokasi</th>
                        <th class="text-end">Berat Masuk (kg)</th>
                        <th class="text-end">Berat Dikelola (kg)</th>
                        <th class="text-end">Belum Dikelola (kg)</th>
                        <th class="text-end">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rekap_lokasi)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($rekap_lokasi as $rl): ?>
                            <?php
                                $rl_masuk    = (float) $rl['total_masuk'];
                                $rl_dikelola = (float) $rl['total_dikelola'];
                                $rl_belum    = $rl_masuk - $rl_dikelola;
                                $rl_persen   = $rl_masuk > 0 ? round(($rl_dikelola / $rl_masuk) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= e($rl['nama_lokasi']) ?></td>
                                <td class="text-end"><?= e(format_angka($rl_masuk, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($rl_dikelola, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($rl_belum, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($rl_persen, 1)) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rekapitulasi berdasarkan Jenis Pengelolaan -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Rekapitulasi berdasarkan Jenis Pengelolaan</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jenis Pengelolaan</th>
                        <th class="text-end">Total Berat Dikelola (kg)</th>
                        <th class="text-end">Jumlah Proses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rekap_jenis)): ?>
                        <tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($rekap_jenis as $rj): ?>
                            <tr>
                                <td><?= e($rj['jenis_pengolahan']) ?></td>
                                <td class="text-end"><?= e(format_angka((float) $rj['total_berat'], 2)) ?></td>
                                <td class="text-end"><?= e(format_angka((int) $rj['jumlah_proses'], 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Data Sampah Masuk -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detail Data Sampah Masuk</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th class="text-end">Berat (kg)</th>
                        <th class="text-end">Dikelola (kg)</th>
                        <th class="text-end">Sisa (kg)</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detail_data)): ?>
                        <tr><td colspan="8" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($detail_data as $dd): ?>
                            <?php
                                $dd_berat    = (float) $dd['berat'];
                                $dd_dikelola = (float) $dd['dikelola'];
                                $dd_sisa     = $dd_berat - $dd_dikelola;

                                if ($dd_dikelola <= 0) {
                                    $status_label = 'Belum';
                                    $status_class = 'badge-belum';
                                } elseif ($dd_sisa > 0) {
                                    $status_label = 'Proses';
                                    $status_class = 'badge-proses';
                                } else {
                                    $status_label = 'Selesai';
                                    $status_class = 'badge-selesai';
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= e(format_tanggal($dd['tanggal'])) ?></td>
                                <td><?= e($dd['nama_kategori']) ?></td>
                                <td><?= e($dd['nama_lokasi']) ?></td>
                                <td class="text-end"><?= e(format_angka($dd_berat, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($dd_dikelola, 2)) ?></td>
                                <td class="text-end"><?= e(format_angka($dd_sisa, 2)) ?></td>
                                <td class="text-center">
                                    <span class="badge-status <?= $status_class ?>"><?= e($status_label) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cetakLaporan() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
