<?php
/**
 * WasteLog - Cek Autentikasi
 * File ini harus di-include setelah koneksi.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
