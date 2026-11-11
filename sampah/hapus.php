<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/sampah/index.php');
    exit;
}

verify_csrf();

$id = $_POST['id'] ?? null;

if (!$id) {
    set_flash('error', 'ID data tidak ditemukan.');
    header('Location: ' . BASE_URL . '/sampah/index.php');
    exit;
}

// Cek apakah data ada
$stmt = $pdo->prepare("SELECT id FROM sampah_masuk WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Data sampah masuk tidak ditemukan.');
    header('Location: ' . BASE_URL . '/sampah/index.php');
    exit;
}

// Cek apakah sudah punya data pengelolaan
$stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pengelolaan WHERE sampah_id = ?");
$stmtCek->execute([$id]);
$jumlahPengelolaan = $stmtCek->fetchColumn();

if ($jumlahPengelolaan > 0) {
    set_flash('error', 'Data sampah masuk tidak dapat dihapus karena sudah memiliki data pengelolaan.');
    header('Location: ' . BASE_URL . '/sampah/index.php');
    exit;
}

// Hapus data
$stmtHapus = $pdo->prepare("DELETE FROM sampah_masuk WHERE id = ?");
$stmtHapus->execute([$id]);

set_flash('success', 'Data sampah masuk berhasil dihapus.');
header('Location: ' . BASE_URL . '/sampah/index.php');
exit;
