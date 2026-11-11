<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Edit Lokasi';
$active_menu = 'lokasi';

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load existing data
$stmt = $pdo->prepare("SELECT * FROM lokasi WHERE id = :id");
$stmt->execute([':id' => $id]);
$lokasi = $stmt->fetch();

if (!$lokasi) {
    set_flash('error', 'Data lokasi tidak ditemukan.');
    header('Location: ' . BASE_URL . '/lokasi/index.php');
    exit;
}

$errors = [];
$nama_lokasi = $lokasi['nama_lokasi'];
$alamat = $lokasi['alamat'] ?? '';

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

    // Check for duplicate (exclude current record)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lokasi WHERE nama_lokasi = :nama AND id != :id");
        $stmt->execute([':nama' => $nama_lokasi, ':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Nama lokasi sudah ada.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE lokasi SET nama_lokasi = :nama, alamat = :alamat WHERE id = :id");
        $stmt->execute([
            ':nama' => $nama_lokasi,
            ':alamat' => $alamat !== '' ? $alamat : null,
            ':id' => $id,
        ]);

        set_flash('success', 'Lokasi berhasil diperbarui.');
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
        <h1 class="h3 mb-0">Edit Lokasi</h1>
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
            <form method="POST" action="<?= BASE_URL ?>/lokasi/edit.php?id=<?= (int)$id ?>">
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
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                    <a href="<?= BASE_URL ?>/lokasi/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
