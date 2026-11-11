<?php
/**
 * WasteLog - Konfigurasi Database & Helper Functions
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'wastelog_db');
define('DB_USER', 'root');
define('DB_PASS', '');

if (!defined('BASE_URL')) {
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
    $appDir  = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
    $subDir  = ($docRoot !== '' && str_starts_with($appDir, $docRoot)) ? trim(substr($appDir, strlen($docRoot)), '/') : 'wastelog';
    define('BASE_URL', $subDir !== '' ? '/' . $subDir : '');
}
define('APP_NAME', 'WasteLog');

// Koneksi PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Menghasilkan hidden input CSRF token
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Verifikasi CSRF token dari POST request
 */
function verify_csrf(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/dashboard.php'));
        exit;
    }
}

/**
 * Escape output untuk mencegah XSS
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message ke session
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Ambil dan hapus flash message dari session
 */
function get_flash(string $type): ?string {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Render alert flash messages
 */
function render_flash(): string {
    $html = '';
    $types = ['success' => 'success', 'error' => 'danger', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    foreach ($types as $key => $class) {
        $msg = get_flash($key);
        if ($msg) {
            $html .= '<div class="alert alert-' . $class . ' alert-dismissible fade show" role="alert">'
                    . e($msg)
                    . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
    return $html;
}

/**
 * Format angka dengan desimal Indonesia
 */
function format_angka(float $num, int $decimals = 2): string {
    return number_format($num, $decimals, ',', '.');
}

/**
 * Format tanggal ke format Indonesia
 */
function format_tanggal(string $date): string {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date_create($date);
    if (!$d) return $date;
    return date_format($d, 'd') . ' ' . $bulan[(int)date_format($d, 'n')] . ' ' . date_format($d, 'Y');
}

/**
 * Pagination helper
 */
function paginate(PDO $pdo, string $query, array $params, int $page, int $perPage = 10): array {
    // Count total
    $countQuery = "SELECT COUNT(*) FROM (" . $query . ") AS count_table";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    // Fetch data
    $dataQuery = $query . " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    return [
        'data'        => $data,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * Render pagination HTML
 */
function render_pagination(array $pagination, string $baseUrl): string {
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';

    // Previous
    $prevDisabled = $pagination['page'] <= 1 ? ' disabled' : '';
    $prevPage = $pagination['page'] - 1;
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $baseUrl . '&page=' . $prevPage . '">&laquo;</a></li>';

    // Pages
    $start = max(1, $pagination['page'] - 2);
    $end = min($pagination['total_pages'], $pagination['page'] + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }

    // Next
    $nextDisabled = $pagination['page'] >= $pagination['total_pages'] ? ' disabled' : '';
    $nextPage = $pagination['page'] + 1;
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $baseUrl . '&page=' . $nextPage . '">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}
