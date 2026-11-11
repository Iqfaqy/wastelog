<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Edit Kategori';
$active_menu = 'kategori';

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load existing data
$stmt = $pdo->prepare("SELECT * FROM kategori_sampah WHERE id = :id");
$stmt->execute([':id' => $id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    set_flash('error', 'Data kategori tidak ditemukan.');
    header('Location: ' . BASE_URL . '/kategori/index.php');
    exit;
}

$errors = [];
$nama_kategori = $kategori['nama_kategori'];
$deskripsi = $kategori['deskripsi'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama_kategori = isset($_POST['nama_kategori']) ? trim($_POST['nama_kategori']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';

    // Validation
    if ($nama_kategori === '') {
        $errors[] = 'Nama kategori wajib diisi.';
    } elseif (mb_strlen($nama_kategori) > 100) {
        $errors[] = 'Nama kategori maksimal 100 karakter.';
    }

    // Check for duplicate (exclude current record)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kategori_sampah WHERE nama_kategori = :nama AND id != :id");
        $stmt->execute([':nama' => $nama_kategori, ':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Nama kategori sudah ada.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE kategori_sampah SET nama_kategori = :nama, deskripsi = :deskripsi WHERE id = :id");
        $stmt->execute([
            ':nama' => $nama_kategori,
            ':deskripsi' => $deskripsi !== '' ? $deskripsi : null,
            ':id' => $id,
        ]);

        set_flash('success', 'Kategori sampah berhasil diperbarui.');
        header('Location: ' . BASE_URL . '/kategori/index.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Kategori Sampah</h1>
        <a href="<?= BASE_URL ?>/kategori/index.php" class="btn btn-outline-secondary">
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
            <form method="POST" action="<?= BASE_URL ?>/kategori/edit.php?id=<?= (int)$id ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                           value="<?= e($nama_kategori) ?>" required maxlength="100"
                           placeholder="Masukkan nama kategori">
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi"
                              rows="4" placeholder="Masukkan deskripsi kategori (opsional)"><?= e($deskripsi) ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                    <a href="<?= BASE_URL ?>/kategori/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
