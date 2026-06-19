<?php
// =========================================================================
// SIDEBAR NAVIGATION DYNAMIC FILTER
// =========================================================================
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Ambil jalur URL saat ini untuk menentukan class active secara dinamis
$current_page = $_SERVER['REQUEST_URI'];
?>

<nav id="sidebar" class="shadow text-white">
    <div class="sidebar-header text-center">
        <img src="/mbg-app/assets/images/logo.jfif" alt="Logo" class="img-fluid mb-2 rounded-circle shadow-sm" onerror="this.style.display='none'; document.getElementById('sidebar-icon-fallback').style.display='inline-block';">
        <div id="sidebar-icon-fallback" style="display:none;">
            <i class="bi bi-egg-fried fs-2 text-warning"></i>
        </div>
        <h5 class="fw-bold mt-2 text-uppercase tracking-wide m-0" style="color: #c6d3e3; font-size: 0.95rem;">MBG-APP</h5>
        <span class="badge rounded-pill fw-light px-2.5 mt-1" style="background-color: #98bad5; color: #243558; font-size: 0.75rem;">Sistem Terintegrasi</span>
    </div>

    <div class="p-2">
        <ul class="nav flex-column gap-1">
            
            <?php if ($role === 'Admin'): ?>
                <li class="nav-item">
                    <a href="/mbg-app/views/admin/dashboard.php" class="nav-link <?= (strpos($current_page, 'dashboard.php') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                    </a>
                </li >
                
                <li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#menuMaster" role="button" aria-expanded="true">
                        <span><i class="bi bi-database-fill-gear"></i> <span>Master Data</span></span>
                        <i class="bi bi-chevron-down toggle-arrow small"></i>
                    </a>
                    <div class="collapse show" id="menuMaster">
                        <ul class="nav flex-column ms-3 ps-2 mt-1 gap-1 border-start" style="border-color: rgba(178,203,222,0.3) !important;">
                            <li class="nav-item">
                                <a href="/mbg-app/views/admin/master/pengguna.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'master/pengguna.php') !== false) ? 'active' : ''; ?>">
                                    <i class="bi bi-person-badge me-2"></i>Data Pengguna
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/mbg-app/views/admin/master/sekolah.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'master/sekolah.php') !== false) ? 'active' : ''; ?>">
                                    <i class="bi bi-building me-2"></i>Data Sekolah
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/mbg-app/views/admin/master/siswa.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'master/siswa.php') !== false) ? 'active' : ''; ?>">
                                    <i class="bi bi-people-fill me-2"></i>Data Siswa
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/mbg-app/views/admin/master/dapur.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'master/dapur.php') !== false) ? 'active' : ''; ?>">
                                    <i class="bi bi-shop-window me-2"></i>Data Dapur
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/mbg-app/views/admin/master/supplier.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'master/supplier.php') !== false) ? 'active' : ''; ?>">
                                    <i class="bi bi-truck me-2"></i>Data Supplier
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/mbg-app/views/admin/master/bahan.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'master/bahan.php') !== false) ? 'active' : ''; ?>">
                                    <i class="bi bi-box-seam me-2"></i>Data Bahan
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            <?php elseif ($role === 'Dapur'): ?>
                <li class="nav-item"><a href="/mbg-app/views/dapur/dashboard.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'dashboard.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="/mbg-app/views/dapur/menu.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'menu.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-journal-richtext"></i> Kelola Menu</a></li>
                <li class="nav-item"><a href="/mbg-app/views/dapur/verifikasi_bahan.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'verifikasi_bahan.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-clipboard-check-fill"></i> Verifikasi Pasokan</a></li>
                <li class="nav-item"><a href="/mbg-app/views/dapur/distribusi.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'distribusi.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-box-arrow-up-right"></i> Distribusi Makanan</a></li>

            <?php elseif ($role === 'Sekolah'): ?>
                <li class="nav-item"><a href="/mbg-app/views/sekolah/dashboard.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'dashboard.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="/mbg-app/views/sekolah/verifikasi.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'verifikasi.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-box2-heart-fill"></i> Terima Kiriman</a></li>
                <li class="nav-item"><a href="/mbg-app/views/sekolah/presensi.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'presensi.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-ui-checks-grid"></i> Presensi Makan</a></li>

            <?php elseif ($role === 'Supplier'): ?>
                <li class="nav-item"><a href="/mbg-app/views/supplier/dashboard.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'dashboard.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard Suplai</a></li>
                <li class="nav-item"><a href="/mbg-app/views/supplier/form_pasok.php" class="nav-link submenu-link py-1.5 <?= (strpos($current_page, 'form_pasok.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-cart-plus-fill"></i> Input Pasokan</a></li>

            <?php else: ?>
                <li class="nav-item">
                    <span class="nav-link text-danger disabled"><i class="bi bi-exclamation-triangle-fill"></i> Akses Ditolak</span>
                </li >
            <?php endif; ?>

        </ul>
    </div>
</nav>

<main id="content">