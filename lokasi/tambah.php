<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Tambah Lokasi';
$active_menu = 'lokasi';

$errors = [];
$nama_lokasi = '';
$alamat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama_lokasi = isset($_POST['nama_lokasi']) ? trim($_POST['nama_lokasi']) : '';
    $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';

    // Validation
    if ($nama_lokasi === '') {
        $errors[] = 'Nama lokasi wajib diisi.';
    } elseif (mb_strlen($nama_lokasi) > 100) {
        $errors[] = 'Nama lokasi maksimal 100 karakter.';
    }

    // Check for duplicate
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lokasi WHERE nama_lokasi = :nama");
        $stmt->execute([':nama' => $nama_lokasi]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Nama lokasi sudah ada.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO lokasi (nama_lokasi, alamat, created_at) VALUES (:nama, :alamat, NOW())");
        $stmt->execute([
            ':nama' => $nama_lokasi,
            ':alamat' => $alamat !== '' ? $alamat : null,
        ]);

        set_flash('success', 'Lokasi berhasil ditambahkan.');
        header('Location: ' . BASE_URL . '/lokasi/index.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah Lokasi</h1>
        <a href="<?= BASE_URL ?>/lokasi/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="nama_lokasi" class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_lokasi" name="nama_lokasi"
                           value="<?= e($nama_lokasi) ?>" required maxlength="100"
                           placeholder="Masukkan nama lokasi">
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat/Keterangan</label>
                    <textarea class="form-control" id="alamat" name="alamat"
                              rows="4" placeholder="Masukkan alamat atau keterangan lokasi (opsional)"><?= e($alamat) ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <a href="<?= BASE_URL ?>/lokasi/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
