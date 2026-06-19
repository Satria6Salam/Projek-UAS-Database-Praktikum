<?php
// =========================================================================
// INISIALISASI & PROTEKSI ROUTING TEMPLATE TERPUSAT
// =========================================================================

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';

// Menjalankan fungsi proteksi login terpusat
cekLogin();

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

$teks_konteks = "Aplikasi MBG";
$icon_konteks = "bi-app-indicator";

switch ($role) {
    case 'Admin':
        $teks_konteks = "Kelola Data Master";
        $icon_konteks = "bi-shield-lock-fill";
        break;
    case 'Dapur':
        $teks_konteks = "Panel Dapur Umum";
        $icon_konteks = "bi-shop";
        break;
    case 'Sekolah':
        $teks_konteks = "Monitoring Sekolah";
        $icon_konteks = "bi-building-gear";
        break;
    case 'Supplier':
        $teks_konteks = "Portal Suplai Logistik";
        $icon_konteks = "bi-truck";
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $teks_konteks ?> - MBG APP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/mbg-app/assets/css/style.css">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-custom-navbar fixed-top shadow-sm px-3">
        <div class="container-fluid px-0">
            <button class="btn btn-outline-light d-lg-none me-2" type="button" id="sidebarCollapse">
                <i class="bi bi-list fs-4"></i>
            </button>

            <span class="navbar-brand fw-bold text-uppercase d-flex align-items-center tracking-wide m-0 fs-6 fs-sm-5">
                <img src="/mbg-app/assets/images/logo.jfif" alt="Logo" class="me-2" style="max-height: 32px; width: auto;" onerror="this.style.display='none';">
                <i class="bi <?= $icon_konteks ?> me-2 d-none d-sm-inline"></i> 
                <span><?= $teks_konteks ?></span>
            </span>

            <button class="navbar-toggler border-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#topbarContent" aria-controls="topbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-person-gear fs-4 text-white"></i>
            </button>

            <div class="collapse navbar-collapse" id="topbarContent">
                <ul class="navbar-nav ms-auto align-items-lg-center mt-2 mt-lg-0">
                    <li class="nav-item me-lg-3 mb-2 mb-lg-0">
                        <div class="text-white d-flex align-items-center bg-dark bg-opacity-25 px-3 py-1.5 rounded-pill">
                            <i class="bi bi-person-circle me-2"></i> 
                            <span class="text-capitalize small fw-semibold"><?= htmlspecialchars($username) ?></span> 
                            <span class="badge badge-custom-role ms-2 rounded-pill">Role: <?= $role ?></span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="/mbg-app/actions/auth/logout.php" class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm w-100 w-lg-auto" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                            <i class="bi bi-box-arrow-right me-1"></i> Keluar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="wrapper">