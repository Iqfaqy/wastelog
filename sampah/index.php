<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Data Sampah Masuk';
$active_menu = 'sampah';

// Ambil data kategori & lokasi untuk filter dropdown
$kategoriList = $pdo->query("SELECT id, nama_kategori FROM kategori_sampah ORDER BY nama_kategori")->fetchAll();
$lokasiList = $pdo->query("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi")->fetchAll();

// Ambil parameter filter
$search = trim($_GET['search'] ?? '');
$kategori_id = $_GET['kategori_id'] ?? '';
$lokasi_id = $_GET['lokasi_id'] ?? '';
$tgl_mulai = $_GET['tanggal_mulai'] ?? '';
$tgl_akhir = $_GET['tanggal_akhir'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Bangun query dinamis
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "sm.keterangan LIKE ?";
    $params[] = "%$search%";
}
if ($kategori_id !== '') {
    $where[] = "sm.kategori_id = ?";
    $params[] = $kategori_id;
}
if ($lokasi_id !== '') {
    $where[] = "sm.lokasi_id = ?";
    $params[] = $lokasi_id;
}
if ($tgl_mulai !== '') {
    $where[] = "sm.tanggal >= ?";
    $params[] = $tgl_mulai;
}
if ($tgl_akhir !== '') {
    $where[] = "sm.tanggal <= ?";
    $params[] = $tgl_akhir;
}

$whereClause = $where ? " WHERE " . implode(" AND ", $where) : "";

// Query utama dengan JOIN
$baseQuery = "SELECT sm.*, ks.nama_kategori, l.nama_lokasi
              FROM sampah_masuk sm
              JOIN kategori_sampah ks ON sm.kategori_id = ks.id
              JOIN lokasi l ON sm.lokasi_id = l.id"
              . $whereClause
              . " ORDER BY sm.tanggal DESC, sm.id DESC";

$pagination = paginate($pdo, $baseQuery, $params, $page, $perPage);
$rows = $pagination['data'];

// Hitung summary (total record & total berat) berdasarkan filter
$summaryQuery = "SELECT COUNT(*) AS total_record, COALESCE(SUM(sm.berat), 0) AS total_berat
                 FROM sampah_masuk sm" . $whereClause;
$stmtSummary = $pdo->prepare($summaryQuery);
$stmtSummary->execute($params);
$summary = $stmtSummary->fetch();

// Bangun base URL untuk pagination (pertahankan filter)
$filterParams = [];
if ($search !== '') $filterParams['search'] = $search;
if ($kategori_id !== '') $filterParams['kategori_id'] = $kategori_id;
if ($lokasi_id !== '') $filterParams['lokasi_id'] = $lokasi_id;
if ($tgl_mulai !== '') $filterParams['tanggal_mulai'] = $tgl_mulai;
if ($tgl_akhir !== '') $filterParams['tanggal_akhir'] = $tgl_akhir;
$baseUrl = BASE_URL . '/sampah/index.php?' . http_build_query($filterParams);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Data Sampah Masuk</h4>
        <a href="<?= BASE_URL ?>/sampah/tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Data
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
                <div class="col-md-2">
                    <label class="form-label">Kategori</label>
                    <select name="kategori_id" class="form-select">
                        <option value="">-- Semua --</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= $kategori_id == $kat['id'] ? 'selected' : '' ?>>
                                <?= e($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lokasi</label>
                    <select name="lokasi_id" class="form-select">
                        <option value="">-- Semua --</option>
                        <?php foreach ($lokasiList as $lok): ?>
                            <option value="<?= $lok['id'] ?>" <?= $lokasi_id == $lok['id'] ? 'selected' : '' ?>>
                                <?= e($lok['nama_lokasi']) ?>
                            </option>
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
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
            <div class="mt-2">
                <a href="<?= BASE_URL ?>/sampah/index.php" class="text-decoration-none">Reset Filter</a>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="table-wrapper">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Berat (kg)</th>
                    <th>Status Pengelolaan</th>
                    <th style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada data sampah masuk.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $no = ($pagination['page'] - 1) * $pagination['per_page'] + 1;
                    foreach ($rows as $row):
                        // Hitung status pengelolaan
                        $stmtKelola = $pdo->prepare("SELECT COALESCE(SUM(berat_dikelola), 0) FROM pengelolaan WHERE sampah_id = ?");
                        $stmtKelola->execute([$row['id']]);
                        $totalKelola = (float)$stmtKelola->fetchColumn();
                        $sisaBerat = round((float)$row['berat'] - $totalKelola, 2);

                        if ($totalKelola <= 0) {
                            $statusLabel = 'Belum Dikelola';
                            $statusClass = 'badge-belum';
                        } elseif ($sisaBerat > 0) {
                            $statusLabel = 'Dalam Proses';
                            $statusClass = 'badge-proses';
                        } else {
                            $statusLabel = 'Selesai';
                            $statusClass = 'badge-selesai';
                        }
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e(format_tanggal($row['tanggal'])) ?></td>
                            <td><?= e($row['nama_kategori']) ?></td>
                            <td><?= e($row['nama_lokasi']) ?></td>
                            <td>
                                <strong><?= format_angka($row['berat']) ?></strong> kg
                                <?php if ($sisaBerat > 0): ?>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Sisa: <?= format_angka($sisaBerat) ?> kg</small>
                                <?php else: ?>
                                    <small class="text-success d-block" style="font-size: 0.75rem;"><i class="bi bi-check-all"></i> Terkelola Penuh</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-status <?= $statusClass ?>"><?= e($statusLabel) ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($sisaBerat > 0): ?>
                                        <a href="<?= BASE_URL ?>/pengelolaan/tambah.php?sampah_id=<?= $row['id'] ?>"
                                           class="btn btn-sm btn-success btn-action" title="Kelola Sampah Ini (Proses ke Selesai)">
                                            <i class="bi bi-gear-fill"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/sampah/edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-action" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/sampah/hapus.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
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

    <!-- Summary -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted">
            Total: <strong><?= number_format($summary['total_record'], 0, ',', '.') ?></strong> data |
            Total Berat: <strong><?= format_angka($summary['total_berat']) ?> kg</strong>
        </div>
        <?= render_pagination($pagination, $baseUrl) ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
