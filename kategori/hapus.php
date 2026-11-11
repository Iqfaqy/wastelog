<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Metode request tidak valid.');
    header('Location: ' . BASE_URL . '/kategori/index.php');
    exit;
}

verify_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'ID kategori tidak valid.');
    header('Location: ' . BASE_URL . '/kategori/index.php');
    exit;
}

// Check if category exists
$stmt = $pdo->prepare("SELECT id FROM kategori_sampah WHERE id = :id");
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Data kategori tidak ditemukan.');
    header('Location: ' . BASE_URL . '/kategori/index.php');
    exit;
}

// Check if category is used in sampah_masuk
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sampah_masuk WHERE kategori_id = :id");
$stmt->execute([':id' => $id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    set_flash('error', 'Kategori tidak dapat dihapus karena sudah digunakan dalam data sampah masuk.');
    header('Location: ' . BASE_URL . '/kategori/index.php');
    exit;
}

// Delete the category
$stmt = $pdo->prepare("DELETE FROM kategori_sampah WHERE id = :id");
$stmt->execute([':id' => $id]);

set_flash('success', 'Kategori sampah berhasil dihapus.');
header('Location: ' . BASE_URL . '/kategori/index.php');
exit;
