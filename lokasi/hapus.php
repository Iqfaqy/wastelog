<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Metode request tidak valid.');
    header('Location: ' . BASE_URL . '/lokasi/index.php');
    exit;
}

verify_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'ID lokasi tidak valid.');
    header('Location: ' . BASE_URL . '/lokasi/index.php');
    exit;
}

// Check if location exists
$stmt = $pdo->prepare("SELECT id FROM lokasi WHERE id = :id");
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Data lokasi tidak ditemukan.');
    header('Location: ' . BASE_URL . '/lokasi/index.php');
    exit;
}

// Check if location is used in sampah_masuk
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sampah_masuk WHERE lokasi_id = :id");
$stmt->execute([':id' => $id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    set_flash('error', 'Lokasi tidak dapat dihapus karena sudah digunakan dalam data sampah masuk.');
    header('Location: ' . BASE_URL . '/lokasi/index.php');
    exit;
}

// Delete the location
$stmt = $pdo->prepare("DELETE FROM lokasi WHERE id = :id");
$stmt->execute([':id' => $id]);

set_flash('success', 'Lokasi berhasil dihapus.');
header('Location: ' . BASE_URL . '/lokasi/index.php');
exit;
