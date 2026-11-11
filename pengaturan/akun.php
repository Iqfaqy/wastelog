<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Pengaturan Akun';
$active_menu = 'pengaturan';

$user_id = $_SESSION['user_id'];

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        verify_csrf();

        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($nama === '' || $username === '') {
            set_flash('danger', 'Nama dan username tidak boleh kosong.');
        } else {
            // Check username uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->execute([':username' => $username, ':id' => $user_id]);

            if ($stmt->fetch()) {
                set_flash('danger', 'Username sudah digunakan oleh pengguna lain.');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama = :nama, username = :username WHERE id = :id");
                $stmt->execute([':nama' => $nama, ':username' => $username, ':id' => $user_id]);

                // Update session
                $_SESSION['user_nama']     = $nama;
                $_SESSION['username']      = $username;
                $_SESSION['user_username'] = $username;

                set_flash('success', 'Profil berhasil diperbarui.');
            }
        }

        header('Location: ' . BASE_URL . '/pengaturan/akun.php');
        exit;
    }

    if ($action === 'update_password') {
        verify_csrf();

        $password_lama     = $_POST['password_lama'] ?? '';
        $password_baru     = $_POST['password_baru'] ?? '';
        $konfirmasi        = $_POST['konfirmasi_password'] ?? '';

        // Fetch current hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password_lama, $user['password'])) {
            set_flash('danger', 'Password lama tidak sesuai.');
        } elseif (strlen($password_baru) < 6) {
            set_flash('danger', 'Password baru minimal 6 karakter.');
        } elseif ($password_baru !== $konfirmasi) {
            set_flash('danger', 'Konfirmasi password tidak cocok.');
        } else {
            $hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([':password' => $hash, ':id' => $user_id]);

            set_flash('success', 'Password berhasil diubah.');
        }

        header('Location: ' . BASE_URL . '/pengaturan/akun.php');
        exit;
    }
}

// --- Fetch current user data ---
$stmt = $pdo->prepare("SELECT nama, username, created_at FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'Data pengguna tidak ditemukan.');
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-4">
    <!-- User Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Informasi Akun</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-muted"></i>
                </div>
                <h5 class="mb-1"><?= e($user['nama']) ?></h5>
                <p class="text-muted mb-2">@<?= e($user['username']) ?></p>
                <hr>
                <div class="text-start">
                    <p class="mb-1">
                        <strong><i class="fas fa-user me-2"></i>Nama:</strong><br>
                        <?= e($user['nama']) ?>
                    </p>
                    <p class="mb-1">
                        <strong><i class="fas fa-at me-2"></i>Username:</strong><br>
                        <?= e($user['username']) ?>
                    </p>
                    <p class="mb-0">
                        <strong><i class="fas fa-calendar me-2"></i>Tanggal Registrasi:</strong><br>
                        <?= e(format_tanggal($user['created_at'])) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Column -->
    <div class="col-md-8">
        <!-- Update Profile -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Ubah Profil</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama"
                               value="<?= e($user['nama']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= e($user['username']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Update Password -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Ubah Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_password">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="password_lama" class="form-label">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                    </div>

                    <div class="mb-3">
                        <label for="password_baru" class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru"
                               required minlength="6">
                        <div class="form-text">Minimal 6 karakter.</div>
                    </div>

                    <div class="mb-3">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
