<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Metode request tidak diizinkan.');
    header('Location: ' . BASE_URL . '/pengelolaan/index.php');
    exit;
}

verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID data pengelolaan tidak valid.');
    header('Location: ' . BASE_URL . '/pengelolaan/index.php');
    exit;
}

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM pengelolaan WHERE id = ?");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        set_flash('error', 'Data pengelolaan tidak ditemukan.');
        header('Location: ' . BASE_URL . '/pengelolaan/index.php');
        exit;
    }

    $stmtDelete = $pdo->prepare("DELETE FROM pengelolaan WHERE id = ?");
    $stmtDelete->execute([$id]);

    set_flash('success', 'Data pengelolaan berhasil dihapus. Sisa berat sampah telah disesuaikan kembali secara otomatis.');
} catch (Exception $e) {
    set_flash('error', 'Gagal menghapus data pengelolaan: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/pengelolaan/index.php');
exit;
