<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Tambah Sampah Masuk';
$active_menu = 'sampah';

// Ambil data kategori & lokasi untuk dropdown
$kategoriList = $pdo->query("SELECT id, nama_kategori FROM kategori_sampah ORDER BY nama_kategori")->fetchAll();
$lokasiList = $pdo->query("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi")->fetchAll();

$errors = [];
$old = [
    'tanggal' => date('Y-m-d'),
    'kategori_id' => '',
    'lokasi_id' => '',
    'berat' => '',
    'keterangan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['tanggal'] = trim($_POST['tanggal'] ?? '');
    $old['kategori_id'] = $_POST['kategori_id'] ?? '';
    $old['lokasi_id'] = $_POST['lokasi_id'] ?? '';
    $old['berat'] = trim($_POST['berat'] ?? '');
    $old['keterangan'] = trim($_POST['keterangan'] ?? '');

    // Validasi
    if ($old['tanggal'] === '') {
        $errors[] = 'Tanggal wajib diisi.';
    }
    if ($old['kategori_id'] === '') {
        $errors[] = 'Kategori wajib dipilih.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM kategori_sampah WHERE id = ?");
        $stmtCek->execute([$old['kategori_id']]);
        if ($stmtCek->fetchColumn() == 0) {
            $errors[] = 'Kategori tidak valid.';
        }
    }
    if ($old['lokasi_id'] === '') {
        $errors[] = 'Lokasi wajib dipilih.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM lokasi WHERE id = ?");
        $stmtCek->execute([$old['lokasi_id']]);
        if ($stmtCek->fetchColumn() == 0) {
            $errors[] = 'Lokasi tidak valid.';
        }
    }
    if ($old['berat'] === '' || !is_numeric($old['berat']) || floatval($old['berat']) <= 0) {
        $errors[] = 'Berat harus berupa angka dan lebih dari 0.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO sampah_masuk (kategori_id, lokasi_id, tanggal, berat, keterangan, created_at)
                               VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $old['kategori_id'],
            $old['lokasi_id'],
            $old['tanggal'],
            floatval($old['berat']),
            $old['keterangan'],
        ]);

        set_flash('success', 'Data sampah masuk berhasil ditambahkan.');
        header('Location: ' . BASE_URL . '/sampah/index.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tambah Sampah Masuk</h4>
        <a href="<?= BASE_URL ?>/sampah/index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= e($old['tanggal']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori_id" id="kategori_id" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= $old['kategori_id'] == $kat['id'] ? 'selected' : '' ?>>
                                <?= e($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="lokasi_id" class="form-label">Lokasi <span class="text-danger">*</span></label>
                    <select name="lokasi_id" id="lokasi_id" class="form-select" required>
                        <option value="">-- Pilih Lokasi --</option>
                        <?php foreach ($lokasiList as $lok): ?>
                            <option value="<?= $lok['id'] ?>" <?= $old['lokasi_id'] == $lok['id'] ? 'selected' : '' ?>>
                                <?= e($lok['nama_lokasi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="berat" class="form-label">Berat (kg) <span class="text-danger">*</span></label>
                    <input type="number" name="berat" id="berat" class="form-control" step="0.01" min="0.01" value="<?= e($old['berat']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="3"><?= e($old['keterangan']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
