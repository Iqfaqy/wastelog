<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth_check.php';

$page_title = 'Dashboard';
$active_menu = 'dashboard';

// ============================================================
// 1. STAT CARD QUERIES
// ============================================================

// Total Sampah Masuk (kg)
$stmt = $pdo->query("SELECT COALESCE(SUM(berat), 0) AS total FROM sampah_masuk");
$total_sampah_masuk = $stmt->fetchColumn();

// Total Sampah Dikelola (kg)
$stmt = $pdo->query("SELECT COALESCE(SUM(berat_dikelola), 0) AS total FROM pengelolaan");
$total_sampah_dikelola = $stmt->fetchColumn();

// Total Sampah Belum Dikelola (kg)
$total_belum_dikelola = $total_sampah_masuk - $total_sampah_dikelola;
if ($total_belum_dikelola < 0) $total_belum_dikelola = 0;

// Jumlah Kategori Sampah
$stmt = $pdo->query("SELECT COUNT(*) FROM kategori_sampah");
$jumlah_kategori = $stmt->fetchColumn();

// Jumlah Lokasi
$stmt = $pdo->query("SELECT COUNT(*) FROM lokasi");
$jumlah_lokasi = $stmt->fetchColumn();

// Jumlah Transaksi Sampah Masuk
$stmt = $pdo->query("SELECT COUNT(*) FROM sampah_masuk");
$jumlah_transaksi = $stmt->fetchColumn();

// Jumlah Proses Pengelolaan
$stmt = $pdo->query("SELECT COUNT(*) FROM pengelolaan");
$jumlah_pengelolaan = $stmt->fetchColumn();

// Persentase Pengelolaan
$persentase_pengelolaan = ($total_sampah_masuk > 0)
    ? round(($total_sampah_dikelola / $total_sampah_masuk) * 100, 1)
    : 0;

// ============================================================
// 2. CHART DATA QUERIES
// ============================================================

// --- Pie/Doughnut: Sampah berdasarkan Kategori ---
$stmt = $pdo->query("
    SELECT k.nama_kategori, COALESCE(SUM(sm.berat), 0) AS total_berat
    FROM sampah_masuk sm
    JOIN kategori_sampah k ON sm.kategori_id = k.id
    GROUP BY k.id, k.nama_kategori
    ORDER BY total_berat DESC
");
$kategori_chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kategori_labels = [];
$kategori_values = [];
foreach ($kategori_chart_data as $row) {
    $kategori_labels[] = $row['nama_kategori'];
    $kategori_values[] = (float) $row['total_berat'];
}

// --- Bar: Sampah Masuk per Bulan (last 6 months) ---
$stmt = $pdo->query("
    SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
           DATE_FORMAT(tanggal, '%M %Y') AS label_bulan,
           COALESCE(SUM(berat), 0) AS total_berat
    FROM sampah_masuk
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY bulan, label_bulan
    ORDER BY bulan ASC
");
$bulanan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bulanan_labels = [];
$bulanan_values = [];
foreach ($bulanan_data as $row) {
    $bulanan_labels[] = $row['label_bulan'];
    $bulanan_values[] = (float) $row['total_berat'];
}

// --- Grouped Bar: Sampah Masuk vs Dikelola per Bulan (last 6 months) ---
$stmt = $pdo->query("
    SELECT
        months.bulan,
        months.label_bulan,
        COALESCE(masuk.total_masuk, 0) AS total_masuk,
        COALESCE(kelola.total_dikelola, 0) AS total_dikelola
    FROM (
        SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
               DATE_FORMAT(tanggal, '%M %Y') AS label_bulan
        FROM sampah_masuk
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        UNION
        SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
               DATE_FORMAT(tanggal, '%M %Y') AS label_bulan
        FROM pengelolaan
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ) months
    LEFT JOIN (
        SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
               SUM(berat) AS total_masuk
        FROM sampah_masuk
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY bulan
    ) masuk ON months.bulan = masuk.bulan
    LEFT JOIN (
        SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
               SUM(berat_dikelola) AS total_dikelola
        FROM pengelolaan
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY bulan
    ) kelola ON months.bulan = kelola.bulan
    ORDER BY months.bulan ASC
");
$perbandingan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$perbandingan_labels = [];
$perbandingan_masuk = [];
$perbandingan_dikelola = [];
foreach ($perbandingan_data as $row) {
    $perbandingan_labels[] = $row['label_bulan'];
    $perbandingan_masuk[] = (float) $row['total_masuk'];
    $perbandingan_dikelola[] = (float) $row['total_dikelola'];
}

// ============================================================
// 3. RECENT ACTIVITY TABLE QUERY
// ============================================================
$stmt = $pdo->query("
    SELECT
        sm.id,
        sm.tanggal,
        sm.berat,
        k.nama_kategori,
        l.nama_lokasi,
        COALESCE(p.total_dikelola, 0) AS total_dikelola
    FROM sampah_masuk sm
    JOIN kategori_sampah k ON sm.kategori_id = k.id
    JOIN lokasi l ON sm.lokasi_id = l.id
    LEFT JOIN (
        SELECT sampah_id, SUM(berat_dikelola) AS total_dikelola
        FROM pengelolaan
        GROUP BY sampah_id
    ) p ON sm.id = p.sampah_id
    ORDER BY sm.created_at DESC
    LIMIT 10
");
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// INCLUDE LAYOUT COMPONENTS
// ============================================================
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Ringkasan data pengelolaan sampah</p>
    </div>
    <div>
        <span class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d F Y') ?></span>
    </div>
</div>

<!-- ============================================================ -->
<!-- STAT CARDS -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <!-- 1. Total Sampah Masuk -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
            <div class="stat-info">
                <h6>Total Sampah Masuk</h6>
                <h3><?= format_angka($total_sampah_masuk) ?> <small class="fs-6 text-muted">kg</small></h3>
            </div>
        </div>
    </div>
    <!-- 2. Total Sampah Dikelola -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft">
                <i class="bi bi-recycle"></i>
            </div>
            <div class="stat-info">
                <h6>Total Sampah Dikelola</h6>
                <h3><?= format_angka($total_sampah_dikelola) ?> <small class="fs-6 text-muted">kg</small></h3>
            </div>
        </div>
    </div>
    <!-- 3. Total Sampah Belum Dikelola -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h6>Belum Dikelola</h6>
                <h3><?= format_angka($total_belum_dikelola) ?> <small class="fs-6 text-muted">kg</small></h3>
            </div>
        </div>
    </div>
    <!-- 4. Jumlah Kategori Sampah -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft">
                <i class="bi bi-tags"></i>
            </div>
            <div class="stat-info">
                <h6>Jumlah Kategori</h6>
                <h3><?= e($jumlah_kategori) ?></h3>
            </div>
        </div>
    </div>
    <!-- 5. Jumlah Lokasi -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft">
                <i class="bi bi-geo-alt"></i>
            </div>
            <div class="stat-info">
                <h6>Jumlah Lokasi</h6>
                <h3><?= e($jumlah_lokasi) ?></h3>
            </div>
        </div>
    </div>
    <!-- 6. Jumlah Transaksi Sampah Masuk -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-secondary-soft">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-info">
                <h6>Transaksi Masuk</h6>
                <h3><?= e($jumlah_transaksi) ?></h3>
            </div>
        </div>
    </div>
    <!-- 7. Jumlah Proses Pengelolaan -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft">
                <i class="bi bi-gear"></i>
            </div>
            <div class="stat-info">
                <h6>Proses Pengelolaan</h6>
                <h3><?= e($jumlah_pengelolaan) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- PERSENTASE PENGELOLAAN PROGRESS BAR -->
<!-- ============================================================ -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Persentase Pengelolaan Sampah</h6>
            <span class="fw-bold text-<?= $persentase_pengelolaan >= 75 ? 'success' : ($persentase_pengelolaan >= 50 ? 'warning' : 'danger') ?>">
                <?= $persentase_pengelolaan ?>%
            </span>
        </div>
        <div class="progress" style="height: 12px;">
            <div class="progress-bar bg-<?= $persentase_pengelolaan >= 75 ? 'success' : ($persentase_pengelolaan >= 50 ? 'warning' : 'danger') ?>"
                 role="progressbar"
                 style="width: <?= $persentase_pengelolaan ?>%"
                 aria-valuenow="<?= $persentase_pengelolaan ?>"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>
        <small class="text-muted mt-1 d-block">
            <?= format_angka($total_sampah_dikelola) ?> kg dari <?= format_angka($total_sampah_masuk) ?> kg total sampah telah dikelola
        </small>
    </div>
</div>

<!-- ============================================================ -->
<!-- CHARTS ROW 1 -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Pie/Doughnut: Sampah berdasarkan Kategori -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-pie-chart me-2"></i>Sampah Berdasarkan Kategori</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 320px;">
                    <canvas id="chartKategori"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- Bar: Sampah Masuk per Bulan -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-bar-chart me-2"></i>Sampah Masuk per Bulan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 320px;">
                    <canvas id="chartBulanan"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- CHARTS ROW 2 -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Grouped Bar: Perbandingan Masuk vs Dikelola -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-bar-chart-line me-2"></i>Perbandingan Sampah Masuk vs Dikelola per Bulan</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 350px;">
                    <canvas id="chartPerbandingan"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RECENT ACTIVITY TABLE -->
<!-- ============================================================ -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru</h5>
        <a href="<?= BASE_URL ?>/sampah_masuk/" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Lokasi Asal</th>
                        <th class="text-end">Berat (kg)</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $i => $row):
                            $berat = (float) $row['berat'];
                            $dikelola = (float) $row['total_dikelola'];

                            if ($dikelola >= $berat) {
                                $status_label = 'Selesai';
                                $status_class = 'badge-selesai';
                            } elseif ($dikelola > 0) {
                                $status_label = 'Dalam Proses';
                                $status_class = 'badge-proses';
                            } else {
                                $status_label = 'Belum Dikelola';
                                $status_class = 'badge-belum';
                            }
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= format_tanggal($row['tanggal']) ?></td>
                            <td><?= e($row['nama_kategori']) ?></td>
                            <td><?= e($row['nama_lokasi']) ?></td>
                            <td class="text-end fw-semibold"><?= format_angka($berat) ?></td>
                            <td class="text-center">
                                <span class="badge-status <?= $status_class ?>"><?= $status_label ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data sampah masuk
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- CHART.JS SCRIPTS -->
<!-- ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Color palette
    const chartColors = [
        'rgba(78, 115, 223, 0.85)',
        'rgba(28, 200, 138, 0.85)',
        'rgba(246, 194, 62, 0.85)',
        'rgba(231, 74, 59, 0.85)',
        'rgba(54, 185, 204, 0.85)',
        'rgba(133, 135, 150, 0.85)',
        'rgba(255, 128, 66, 0.85)',
        'rgba(153, 102, 255, 0.85)',
        'rgba(255, 159, 64, 0.85)',
        'rgba(75, 192, 192, 0.85)'
    ];

    const chartBorderColors = [
        'rgba(78, 115, 223, 1)',
        'rgba(28, 200, 138, 1)',
        'rgba(246, 194, 62, 1)',
        'rgba(231, 74, 59, 1)',
        'rgba(54, 185, 204, 1)',
        'rgba(133, 135, 150, 1)',
        'rgba(255, 128, 66, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(75, 192, 192, 1)'
    ];

    // -------------------------------------------------------
    // 1. Doughnut Chart: Sampah Berdasarkan Kategori
    // -------------------------------------------------------
    const kategoriLabels = <?= json_encode($kategori_labels) ?>;
    const kategoriValues = <?= json_encode($kategori_values) ?>;

    const ctxKategori = document.getElementById('chartKategori');
    if (ctxKategori) {
        new Chart(ctxKategori.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: kategoriLabels,
                datasets: [{
                    data: kategoriValues,
                    backgroundColor: chartColors.slice(0, kategoriLabels.length),
                    borderColor: chartBorderColors.slice(0, kategoriLabels.length),
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value.toLocaleString('id-ID') + ' kg (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------
    // 2. Bar Chart: Sampah Masuk per Bulan
    // -------------------------------------------------------
    const bulananLabels = <?= json_encode($bulanan_labels) ?>;
    const bulananValues = <?= json_encode($bulanan_values) ?>;

    const ctxBulanan = document.getElementById('chartBulanan');
    if (ctxBulanan) {
        new Chart(ctxBulanan.getContext('2d'), {
            type: 'bar',
            data: {
                labels: bulananLabels,
                datasets: [{
                    label: 'Sampah Masuk (kg)',
                    data: bulananValues,
                    backgroundColor: 'rgba(78, 115, 223, 0.75)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID') + ' kg';
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString('id-ID') + ' kg';
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------
    // 3. Grouped Bar Chart: Masuk vs Dikelola per Bulan
    // -------------------------------------------------------
    const perbandinganLabels = <?= json_encode($perbandingan_labels) ?>;
    const perbandinganMasuk = <?= json_encode($perbandingan_masuk) ?>;
    const perbandinganDikelola = <?= json_encode($perbandingan_dikelola) ?>;

    const ctxPerbandingan = document.getElementById('chartPerbandingan');
    if (ctxPerbandingan) {
        new Chart(ctxPerbandingan.getContext('2d'), {
            type: 'bar',
            data: {
                labels: perbandinganLabels,
                datasets: [
                    {
                        label: 'Sampah Masuk (kg)',
                        data: perbandinganMasuk,
                        backgroundColor: 'rgba(78, 115, 223, 0.75)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        barPercentage: 0.5,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Sampah Dikelola (kg)',
                        data: perbandinganDikelola,
                        backgroundColor: 'rgba(28, 200, 138, 0.75)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        barPercentage: 0.5,
                        categoryPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID') + ' kg';
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('id-ID') + ' kg';
                            }
                        }
                    }
                }
            }
        });
    }

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
