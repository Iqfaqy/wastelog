<?php
/**
 * WasteLog - Entry Point
 * Redirect ke dashboard jika sudah login, atau ke login jika belum.
 */
require_once __DIR__ . '/config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;
