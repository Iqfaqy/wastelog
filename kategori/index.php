<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Kategori Sampah';
$active_menu = 'kategori';

// Search & pagination parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;

// Build query
$query = "SELECT * FROM kategori_sampah";
$params = [];

if ($search !== '') {
    $query .= " WHERE nama_kategori LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY created_at DESC";

$pagination = paginate($pdo, $query, $params, $page, $per_page);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Kategori Sampah</h1>
        <a href="<?= BASE_URL ?>/kategori/tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Kategori
        </a>
    </div>

    <div class="filter-bar mb-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Cari Kategori</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="<?= e($search) ?>" placeholder="Nama kategori...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
            <?php if ($search !== ''): ?>
                <div class="col-auto">
                    <a href="<?= BASE_URL ?>/kategori/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th style="width: 160px;">Tanggal Dibuat</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pagination['data']) > 0): ?>
                    <?php foreach ($pagination['data'] as $i => $row): ?>
                        <tr>
                            <td><?= ($pagination['page'] - 1) * $pagination['per_page'] + $i + 1 ?></td>
                            <td><?= e($row['nama_kategori']) ?></td>
                            <td><?= e($row['deskripsi'] ?? '-') ?></td>
                            <td><?= format_tanggal($row['created_at']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>/kategori/edit.php?id=<?= (int)$row['id'] ?>"
                                       class="btn btn-warning btn-action" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/kategori/hapus.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-action btn-delete" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <?php if ($search !== ''): ?>
                                Tidak ada kategori yang cocok dengan pencarian "<?= e($search) ?>".
                            <?php else: ?>
                                Belum ada data kategori sampah.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $baseUrl = BASE_URL . '/kategori/index.php?'
        . ($search !== '' ? 'search=' . urlencode($search) . '&' : '');
    render_pagination($pagination, $baseUrl);
    ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
