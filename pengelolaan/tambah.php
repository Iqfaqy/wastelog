<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Tambah Pengelolaan Sampah';
$active_menu = 'pengelolaan';

$jenis_options = [
    'Pemilahan',
    'Daur ulang',
    'Pengomposan',
    'Pengangkutan ke pengelola',
    'Pengolahan lainnya',
];

$errors = [];
$sampah_id       = $_POST['sampah_id'] ?? $_GET['sampah_id'] ?? '';
$tanggal         = $_POST['tanggal'] ?? date('Y-m-d');
$jenis_pengolahan= $_POST['jenis_pengolahan'] ?? '';
$berat_dikelola  = $_POST['berat_dikelola'] ?? '';
$keterangan      = $_POST['keterangan'] ?? '';

// Ambil daftar sampah_masuk yang masih memiliki sisa berat
$stmtSampah = $pdo->query("
    SELECT sm.id, sm.tanggal, sm.berat, ks.nama_kategori, l.nama_lokasi,
           COALESCE(SUM(p.berat_dikelola), 0) AS total_dikelola,
           ROUND(sm.berat - COALESCE(SUM(p.berat_dikelola), 0), 2) AS sisa_berat
    FROM sampah_masuk sm
    JOIN kategori_sampah ks ON sm.kategori_id = ks.id
    JOIN lokasi l ON sm.lokasi_id = l.id
    LEFT JOIN pengelolaan p ON p.sampah_id = sm.id
    GROUP BY sm.id, sm.tanggal, sm.berat, ks.nama_kategori, l.nama_lokasi
    HAVING sisa_berat > 0
    ORDER BY sm.tanggal DESC, sm.id DESC
");
$sampahList = $stmtSampah->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $sampah_id        = (int)($_POST['sampah_id'] ?? 0);
    $tanggal          = trim($_POST['tanggal'] ?? '');
    $jenis_pengolahan = trim($_POST['jenis_pengolahan'] ?? '');
    $berat_dikelola   = str_replace(',', '.', trim($_POST['berat_dikelola'] ?? ''));
    $keterangan       = trim($_POST['keterangan'] ?? '');

    // Validasi
    if ($sampah_id <= 0) {
        $errors[] = 'Silakan pilih data sampah masuk.';
    }
    if ($tanggal === '') {
        $errors[] = 'Tanggal pengelolaan harus diisi.';
    }
    if ($jenis_pengolahan === '' || !in_array($jenis_pengolahan, $jenis_options, true)) {
        $errors[] = 'Jenis pengolahan tidak valid.';
    }
    if ($berat_dikelola === '' || !is_numeric($berat_dikelola) || (float)$berat_dikelola <= 0) {
        $errors[] = 'Berat dikelola harus berupa angka positif lebih dari 0.';
    }

    if (empty($errors)) {
        $berat_float = (float)$berat_dikelola;

        try {
            $pdo->beginTransaction();

            // Cek sisa berat yang tersedia untuk sampah_id ini (dengan lock)
            $checkStmt = $pdo->prepare("
                SELECT sm.id, sm.berat,
                       COALESCE(SUM(p.berat_dikelola), 0) AS total_dikelola,
                       ROUND(sm.berat - COALESCE(SUM(p.berat_dikelola), 0), 2) AS sisa_berat
                FROM sampah_masuk sm
                LEFT JOIN pengelolaan p ON p.sampah_id = sm.id
                WHERE sm.id = ?
                GROUP BY sm.id, sm.berat
                FOR UPDATE
            ");
            $checkStmt->execute([$sampah_id]);
            $targetSampah = $checkStmt->fetch();

            if (!$targetSampah) {
                throw new Exception('Data sampah masuk tidak ditemukan.');
            }

            $sisa_tersedia = (float)$targetSampah['sisa_berat'];

            if ($berat_float > ($sisa_tersedia + 0.0001)) {
                throw new Exception('Berat dikelola (' . format_angka($berat_float) . ' kg) melebihi sisa berat yang tersedia (' . format_angka($sisa_tersedia) . ' kg).');
            }

            // Simpan data pengelolaan
            $insertStmt = $pdo->prepare("
                INSERT INTO pengelolaan (sampah_id, tanggal, jenis_pengolahan, berat_dikelola, keterangan)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $sampah_id,
                $tanggal,
                $jenis_pengolahan,
                $berat_float,
                $keterangan !== '' ? $keterangan : null
            ]);

            $pdo->commit();

            set_flash('success', 'Data pengelolaan sampah berhasil dicatat.');
            header('Location: ' . BASE_URL . '/pengelolaan/index.php');
            exit;

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Catat Pengelolaan Sampah
                    </h5>
                    <a href="<?= BASE_URL ?>/pengelolaan/index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="sampah_id" class="form-label fw-semibold">Pilih Data Sampah Masuk <span class="text-danger">*</span></label>
                            <select name="sampah_id" id="sampah_id" class="form-select" required>
                                <option value="">-- Pilih Sampah Masuk yang Belum Selesai --</option>
                                <?php foreach ($sampahList as $s): ?>
                                    <option value="<?= $s['id'] ?>"
                                            data-total="<?= $s['berat'] ?>"
                                            data-kelola="<?= $s['total_dikelola'] ?>"
                                            data-sisa="<?= $s['sisa_berat'] ?>"
                                            <?= (string)$sampah_id === (string)$s['id'] ? 'selected' : '' ?>>
                                        [<?= format_tanggal($s['tanggal']) ?>] <?= e($s['nama_kategori']) ?> &middot; <?= e($s['nama_lokasi']) ?> - Total: <?= format_angka($s['berat']) ?> kg (Sisa: <?= format_angka($s['sisa_berat']) ?> kg)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="infoSisa" class="mt-2 text-muted small d-none p-2 bg-light rounded border">
                                <span class="me-3"><i class="bi bi-archive me-1"></i>Total: <strong id="lblTotal">-</strong> kg</span>
                                <span class="me-3"><i class="bi bi-check-circle me-1"></i>Sudah Dikelola: <strong id="lblKelola">-</strong> kg</span>
                                <span class="text-success"><i class="bi bi-hourglass-split me-1"></i>Sisa Tersedia: <strong id="lblSisa">-</strong> kg</span>
                            </div>
                            <?php if (empty($sampahList)): ?>
                                <div class="form-text text-warning mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>Semua data sampah masuk saat ini sudah selesai dikelola (tidak ada sisa berat). Silakan input sampah masuk baru terlebih dahulu jika ada.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal" class="form-label fw-semibold">Tanggal Pengelolaan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= e($tanggal) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="jenis_pengolahan" class="form-label fw-semibold">Jenis Pengolahan <span class="text-danger">*</span></label>
                                <select name="jenis_pengolahan" id="jenis_pengolahan" class="form-select" required>
                                    <option value="">-- Pilih Jenis Pengolahan --</option>
                                    <?php foreach ($jenis_options as $jenis): ?>
                                        <option value="<?= e($jenis) ?>" <?= $jenis_pengolahan === $jenis ? 'selected' : '' ?>><?= e($jenis) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                <label for="berat_dikelola" class="form-label fw-semibold mb-0">Berat Sampah yang Dikelola (kg) <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-success d-none" id="btnKelolaSemua" title="Klik untuk langsung menuntaskan seluruh sisa berat agar status menjadi Selesai">
                                    <i class="bi bi-check2-all me-1"></i>Kelola Seluruh Sisa (<span id="btnSisaText">0</span> kg) &rarr; Status Selesai
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" name="berat_dikelola" id="berat_dikelola"
                                       class="form-control" placeholder="Contoh: 15.50" value="<?= e($berat_dikelola) ?>" required>
                                <span class="input-group-text">kg</span>
                            </div>
                            <div id="statusPreview" class="mt-2 p-2 rounded small bg-light d-none border">
                                <i class="bi bi-info-circle me-1 text-primary"></i><strong>Perkiraan Status Akhir:</strong> <span id="statusPreviewText">-</span>
                            </div>
                            <div class="form-text">Tips: Jika berat dikelola sama dengan seluruh sisa berat, status sampah akan otomatis berubah menjadi <strong>Selesai</strong>.</div>
                        </div>

                        <div class="mb-4">
                            <label for="keterangan" class="form-label fw-semibold">Keterangan / Catatan Tambahan</label>
                            <textarea name="keterangan" id="keterangan" rows="3" class="form-control"
                                      placeholder="Contoh: Dikirim ke unit pengomposan kampus sektor utara"><?= e($keterangan) ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>/pengelolaan/index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary" <?= empty($sampahList) ? 'disabled' : '' ?>>
                                <i class="bi bi-save me-1"></i>Simpan Pengelolaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectSampah   = document.getElementById('sampah_id');
    const infoSisa       = document.getElementById('infoSisa');
    const lblTotal       = document.getElementById('lblTotal');
    const lblKelola      = document.getElementById('lblKelola');
    const lblSisa        = document.getElementById('lblSisa');
    const inputBerat     = document.getElementById('berat_dikelola');
    const btnKelolaSemua = document.getElementById('btnKelolaSemua');
    const btnSisaText    = document.getElementById('btnSisaText');
    const statusPreview  = document.getElementById('statusPreview');
    const statusPreviewText = document.getElementById('statusPreviewText');

    let currentSisa = 0;

    function updateInfo() {
        const opt = selectSampah.options[selectSampah.selectedIndex];
        if (opt && opt.value) {
            const total = parseFloat(opt.dataset.total || '0');
            const kelola = parseFloat(opt.dataset.kelola || '0');
            currentSisa = parseFloat(opt.dataset.sisa || '0');

            lblTotal.textContent = total.toLocaleString('id-ID', { minimumFractionDigits: 2 });
            lblKelola.textContent = kelola.toLocaleString('id-ID', { minimumFractionDigits: 2 });
            lblSisa.textContent = currentSisa.toLocaleString('id-ID', { minimumFractionDigits: 2 });
            btnSisaText.textContent = currentSisa.toLocaleString('id-ID', { minimumFractionDigits: 2 });
            inputBerat.max = currentSisa;

            infoSisa.classList.remove('d-none');
            btnKelolaSemua.classList.remove('d-none');

            updatePreview();
        } else {
            currentSisa = 0;
            infoSisa.classList.add('d-none');
            btnKelolaSemua.classList.add('d-none');
            statusPreview.classList.add('d-none');
            inputBerat.removeAttribute('max');
        }
    }

    function updatePreview() {
        const val = parseFloat(inputBerat.value || '0');
        if (currentSisa <= 0 || isNaN(val) || val <= 0) {
            statusPreview.classList.add('d-none');
            return;
        }

        statusPreview.classList.remove('d-none');
        if (Math.abs(val - currentSisa) < 0.001) {
            statusPreviewText.innerHTML = '<span class="badge-status badge-selesai fw-bold">Selesai</span> <span class="text-success ms-1"><i class="bi bi-check-circle-fill me-1"></i>Hebat! Seluruh sisa sampah tuntas dikelola. Status akan otomatis menjadi <strong>Selesai</strong>.</span>';
        } else if (val < currentSisa) {
            const sisaAkhir = (currentSisa - val).toLocaleString('id-ID', { minimumFractionDigits: 2 });
            statusPreviewText.innerHTML = '<span class="badge-status badge-proses fw-bold">Dalam Proses</span> <span class="text-warning-emphasis ms-1"><i class="bi bi-hourglass-split me-1"></i>Sebagian sampah dikelola. Masih tersisa <strong>' + sisaAkhir + ' kg</strong>.</span>';
        } else {
            statusPreviewText.innerHTML = '<span class="badge bg-danger text-white">Melebihi Sisa!</span> <span class="text-danger ms-1">Berat dikelola tidak boleh melebihi ' + currentSisa.toLocaleString('id-ID', { minimumFractionDigits: 2 }) + ' kg.</span>';
        }
    }

    btnKelolaSemua.addEventListener('click', function () {
        if (currentSisa > 0) {
            inputBerat.value = currentSisa.toFixed(2);
            updatePreview();
        }
    });

    inputBerat.addEventListener('input', updatePreview);
    selectSampah.addEventListener('change', updateInfo);
    updateInfo();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
