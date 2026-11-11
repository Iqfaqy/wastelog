<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Pengelolaan Sampah';
$active_menu = 'pengelolaan';

// --- Filter parameters ---
$search       = trim($_GET['search'] ?? '');
$filter_jenis = trim($_GET['jenis_pengolahan'] ?? '');
$tgl_mulai    = trim($_GET['tanggal_mulai'] ?? '');
$tgl_akhir    = trim($_GET['tanggal_akhir'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;

$jenis_options = [
    'Pemilahan',
    'Daur ulang',
    'Pengomposan',
    'Pengangkutan ke pengelola',
    'Pengolahan lainnya',
];

// --- Build query ---
$baseQuery = "
    FROM pengelolaan p
    JOIN sampah_masuk sm ON p.sampah_id = sm.id
    JOIN kategori_sampah ks ON sm.kategori_id = ks.id
    JOIN lokasi l ON sm.lokasi_id = l.id
";

$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[] = "p.keterangan LIKE ?";
    $params[]     = "%{$search}%";
}

if ($filter_jenis !== '' && in_array($filter_jenis, $jenis_options, true)) {
    $conditions[] = "p.jenis_pengolahan = ?";
    $params[]     = $filter_jenis;
}

if ($tgl_mulai !== '') {
    $conditions[] = "p.tanggal >= ?";
    $params[]     = $tgl_mulai;
}

if ($tgl_akhir !== '') {
    $conditions[] = "p.tanggal <= ?";
    $params[]     = $tgl_akhir;
}

$whereClause = '';
if (!empty($conditions)) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

$selectQuery = "SELECT p.*, sm.tanggal AS tanggal_masuk, sm.berat AS berat_masuk,
                       ks.nama_kategori, l.nama_lokasi
                {$baseQuery}{$whereClause}
                ORDER BY p.tanggal DESC, p.id DESC";

$pagination = paginate($pdo, $selectQuery, $params, $page, $perPage);

// Hitung total ringkasan
$stmtSummary = $pdo->prepare("SELECT COUNT(*) AS total_rows, COALESCE(SUM(p.berat_dikelola), 0) AS total_berat {$baseQuery}{$whereClause}");
$stmtSummary->execute($params);
$summary = $stmtSummary->fetch();

// Build filter query string for pagination links
$filterParams = [];
if ($search !== '')       $filterParams['search']           = $search;
if ($filter_jenis !== '') $filterParams['jenis_pengolahan']  = $filter_jenis;
if ($tgl_mulai !== '')    $filterParams['tanggal_mulai']     = $tgl_mulai;
if ($tgl_akhir !== '')    $filterParams['tanggal_akhir']     = $tgl_akhir;
$filterQuery = http_build_query($filterParams);
$baseUrl     = BASE_URL . '/pengelolaan/index.php?' . $filterQuery;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-gear-wide-connected me-2"></i>Pengelolaan Sampah</h4>
        <a href="<?= BASE_URL ?>/pengelolaan/tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Tambah Pengelolaan
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-3">
        <form method="GET" action="">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Cari Keterangan</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari keterangan..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Pengolahan</label>
                    <select name="jenis_pengolahan" class="form-select">
                        <option value="">-- Semua Jenis --</option>
                        <?php foreach ($jenis_options as $jenis): ?>
                            <option value="<?= e($jenis) ?>" <?= $filter_jenis === $jenis ? 'selected' : '' ?>><?= e($jenis) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="<?= e($tgl_mulai) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control" value="<?= e($tgl_akhir) ?>">
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="<?= BASE_URL ?>/pengelolaan/index.php" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-wrapper">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal Pengelolaan</th>
                    <th>Data Sampah Asal</th>
                    <th>Jenis Pengolahan</th>
                    <th>Berat Dikelola</th>
                    <th>Keterangan</th>
                    <th style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                            Tidak ada data pengelolaan ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $no = ($pagination['page'] - 1) * $perPage + 1;
                    foreach ($pagination['data'] as $row):
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= format_tanggal($row['tanggal']) ?></td>
                            <td>
                                <strong><?= e($row['nama_kategori']) ?></strong> &middot; <?= e($row['nama_lokasi']) ?>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>Masuk: <?= format_tanggal($row['tanggal_masuk']) ?> (<?= format_angka($row['berat_masuk']) ?> kg)
                                </small>
                            </td>
                            <td>
                                <span class="badge-status badge-proses"><?= e($row['jenis_pengolahan']) ?></span>
                            </td>
                            <td><strong><?= format_angka($row['berat_dikelola']) ?></strong> kg</td>
                            <td><?= e($row['keterangan'] ?? '-') ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>/pengelolaan/edit.php?id=<?= (int)$row['id'] ?>"
                                       class="btn btn-sm btn-warning btn-action" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/pengelolaan/hapus.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-action btn-delete" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary & Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <div class="text-muted small">
            Menampilkan <strong><?= count($pagination['data']) ?></strong> dari <strong><?= (int)$summary['total_rows'] ?></strong> transaksi | Total Berat Dikelola: <strong><?= format_angka($summary['total_berat']) ?> kg</strong>
        </div>
        <?php if (!empty($pagination['data'])): ?>
            <div>
                <?= render_pagination($pagination, $baseUrl) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
