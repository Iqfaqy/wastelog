<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/dashboard.php" class="sidebar-brand">
            <i class="bi bi-recycle"></i>
            <span><?= APP_NAME ?></span>
        </a>
        <button class="sidebar-close d-lg-none" id="sidebarClose">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-body">
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/dashboard.php" class="sidebar-nav-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'kategori' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/kategori/index.php" class="sidebar-nav-link">
                    <i class="bi bi-tags"></i>
                    <span>Data Kategori Sampah</span>
                </a>
            </li>
            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'lokasi' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/lokasi/index.php" class="sidebar-nav-link">
                    <i class="bi bi-geo-alt"></i>
                    <span>Data Lokasi</span>
                </a>
            </li>
            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'sampah' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/sampah/index.php" class="sidebar-nav-link">
                    <i class="bi bi-inbox"></i>
                    <span>Data Sampah Masuk</span>
                </a>
            </li>
            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'pengelolaan' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pengelolaan/index.php" class="sidebar-nav-link">
                    <i class="bi bi-gear"></i>
                    <span>Pengelolaan Sampah</span>
                </a>
            </li>
            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'laporan' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/laporan/index.php" class="sidebar-nav-link">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <li class="sidebar-nav-divider"></li>

            <li class="sidebar-nav-item <?= ($active_menu ?? '') === 'pengaturan' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pengaturan/akun.php" class="sidebar-nav-link">
                    <i class="bi bi-person-gear"></i>
                    <span>Pengaturan Akun</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-nav-link text-danger">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <small class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?></small>
    </div>
</aside>
