<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Edit Pengelolaan Sampah';
$active_menu = 'pengelolaan';

$jenis_options = [
    'Pemilahan',
    'Daur ulang',
    'Pengomposan',
    'Pengangkutan ke pengelola',
    'Pengolahan lainnya',
];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID pengelolaan tidak valid.');
    header('Location: ' . BASE_URL . '/pengelolaan/index.php');
    exit;
}

// Ambil data pengelolaan saat ini beserta data sampah masuk
$stmt = $pdo->prepare("
    SELECT p.*, sm.tanggal AS tanggal_masuk, sm.berat AS berat_masuk,
           ks.nama_kategori, l.nama_lokasi,
           (SELECT COALESCE(SUM(p2.berat_dikelola), 0) FROM pengelolaan p2 WHERE p2.sampah_id = sm.id AND p2.id != p.id) AS kelola_lainnya
    FROM pengelolaan p
    JOIN sampah_masuk sm ON p.sampah_id = sm.id
    JOIN kategori_sampah ks ON sm.kategori_id = ks.id
    JOIN lokasi l ON sm.lokasi_id = l.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    set_flash('error', 'Data pengelolaan tidak ditemukan.');
    header('Location: ' . BASE_URL . '/pengelolaan/index.php');
    exit;
}

// Sisa maksimal yang dapat dikelola untuk data ini (berat masuk dikurangi kelola lainnya)
$maksimal_bisa_dikelola = round((float)$data['berat_masuk'] - (float)$data['kelola_lainnya'], 2);

$errors = [];
$tanggal          = $_POST['tanggal'] ?? $data['tanggal'];
$jenis_pengolahan = $_POST['jenis_pengolahan'] ?? $data['jenis_pengolahan'];
$berat_dikelola   = $_POST['berat_dikelola'] ?? $data['berat_dikelola'];
$keterangan       = $_POST['keterangan'] ?? $data['keterangan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $tanggal          = trim($_POST['tanggal'] ?? '');
    $jenis_pengolahan = trim($_POST['jenis_pengolahan'] ?? '');
    $berat_dikelola   = str_replace(',', '.', trim($_POST['berat_dikelola'] ?? ''));
    $keterangan       = trim($_POST['keterangan'] ?? '');

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

            // Hitung ulang dengan lock
            $lockStmt = $pdo->prepare("
                SELECT sm.berat,
                       COALESCE((SELECT SUM(p2.berat_dikelola) FROM pengelolaan p2 WHERE p2.sampah_id = sm.id AND p2.id != ?), 0) AS kelola_lain
                FROM sampah_masuk sm
                WHERE sm.id = ?
                FOR UPDATE
            ");
            $lockStmt->execute([$id, $data['sampah_id']]);
            $lockData = $lockStmt->fetch();

            if (!$lockData) {
                throw new Exception('Data sampah masuk tidak ditemukan.');
            }

            $sisa_tersedia = round((float)$lockData['berat'] - (float)$lockData['kelola_lain'], 2);

            if ($berat_float > ($sisa_tersedia + 0.0001)) {
                throw new Exception('Berat dikelola (' . format_angka($berat_float) . ' kg) melebihi batas sisa berat sampah (' . format_angka($sisa_tersedia) . ' kg).');
            }

            // Update data pengelolaan
            $updateStmt = $pdo->prepare("
                UPDATE pengelolaan
                SET tanggal = ?, jenis_pengolahan = ?, berat_dikelola = ?, keterangan = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $tanggal,
                $jenis_pengolahan,
                $berat_float,
                $keterangan !== '' ? $keterangan : null,
                $id
            ]);

            $pdo->commit();

            set_flash('success', 'Data pengelolaan sampah berhasil diperbarui.');
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
                        <i class="bi bi-pencil-square me-2"></i>Edit Pengelolaan Sampah
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

                    <!-- Informasi Sampah Masuk Terkait -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <h6 class="fw-bold mb-2 text-secondary">
                            <i class="bi bi-info-circle me-1"></i>Informasi Data Sampah Terkait
                        </h6>
                        <div class="row g-2 small">
                            <div class="col-sm-6">
                                <strong>Kategori:</strong> <?= e($data['nama_kategori']) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Lokasi:</strong> <?= e($data['nama_lokasi']) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Tanggal Masuk:</strong> <?= format_tanggal($data['tanggal_masuk']) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Total Berat Masuk:</strong> <?= format_angka($data['berat_masuk']) ?> kg
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="text-success fw-bold">
                                    <i class="bi bi-check-circle me-1"></i>Batas Maksimal Pengelolaan (Tersedia): <?= format_angka($maksimal_bisa_dikelola) ?> kg
                                </span>
                                <?php if ((float)$data['kelola_lainnya'] > 0): ?>
                                    <span class="text-muted ms-2">(Telah dikelola transaksi lain: <?= format_angka($data['kelola_lainnya']) ?> kg)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <?= csrf_field() ?>

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
                            <label for="berat_dikelola" class="form-label fw-semibold">Berat Sampah yang Dikelola (kg) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" max="<?= $maksimal_bisa_dikelola ?>"
                                       name="berat_dikelola" id="berat_dikelola" class="form-control"
                                       value="<?= e($berat_dikelola) ?>" required>
                                <span class="input-group-text">kg</span>
                            </div>
                            <div class="form-text">Maksimal berat yang dapat diisi adalah <strong><?= format_angka($maksimal_bisa_dikelola) ?> kg</strong>.</div>
                        </div>

                        <div class="mb-4">
                            <label for="keterangan" class="form-label fw-semibold">Keterangan / Catatan Tambahan</label>
                            <textarea name="keterangan" id="keterangan" rows="3" class="form-control"
                                      placeholder="Keterangan pengelolaan..."><?= e($keterangan) ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>/pengelolaan/index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
