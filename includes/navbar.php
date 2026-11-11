<!-- Main Content Wrapper -->
<div class="main-content" id="mainContent">

    <!-- Navbar -->
    <nav class="top-navbar">
        <div class="d-flex align-items-center">
            <button class="btn btn-link sidebar-toggle d-lg-none me-2" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h5 class="mb-0 page-title"><?= e($page_title ?? 'Dashboard') ?></h5>
        </div>
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle user-dropdown" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['user_nama'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/pengaturan/akun.php"><i class="bi bi-person-gear me-2"></i>Pengaturan Akun</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <div class="content-area">
        <?= render_flash() ?>
