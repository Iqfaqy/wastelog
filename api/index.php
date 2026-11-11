<?php
/**
 * WasteLog - Vercel Serverless Entrypoint / Router
 * Menghubungkan request URL Vercel ke file-file aplikasi WasteLog
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = ltrim($path, '/');

$baseDir = realpath(__DIR__ . '/..');
$targetFile = null;

if ($path === '' || $path === '/') {
    $targetFile = $baseDir . '/index.php';
} else {
    // 1. Cek file langsung
    $candidate = realpath($baseDir . '/' . $path);
    if ($candidate && str_starts_with($candidate, $baseDir) && is_file($candidate)) {
        $targetFile = $candidate;
    } elseif (is_dir($baseDir . '/' . $path)) {
        // 2. Jika direktori, cari index.php di dalamnya
        $indexCandidate = realpath($baseDir . '/' . $path . '/index.php');
        if ($indexCandidate && is_file($indexCandidate)) {
            $targetFile = $indexCandidate;
        }
    } else {
        // 3. Coba tambahkan ekstensi .php
        $phpCandidate = realpath($baseDir . '/' . $path . '.php');
        if ($phpCandidate && str_starts_with($phpCandidate, $baseDir) && is_file($phpCandidate)) {
            $targetFile = $phpCandidate;
        }
    }
}

// Eksekusi file target jika ditemukan
if ($targetFile && file_exists($targetFile)) {
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// 404 jika tidak ditemukan
http_response_code(404);
echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>404 Not Found</title></head>";
echo "<body style='font-family:sans-serif;text-align:center;padding:50px;'>";
echo "<h2>404 - Halaman Tidak Ditemukan</h2>";
echo "<p>File atau rute <code>" . htmlspecialchars($path) . "</code> tidak ditemukan di server WasteLog.</p>";
echo "<a href='/'>Kembali ke Halaman Utama</a>";
echo "</body></html>";
exit;
