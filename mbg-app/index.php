<?php
// =========================================================================
// GATEKEEPER & ROUTER UTAMA (index.php)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika pengguna SUDAH login, langsung dialihkan ke dashboard masing-masing
if (isset($_SESSION['id_user']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    
    switch ($role) {
        case 'Admin':
            header("Location: /mbg-app/views/admin/dashboard.php");
            exit();
        case 'Dapur':
            header("Location: /mbg-app/views/dapur/dashboard.php");
            exit();
        case 'Sekolah':
            header("Location: /mbg-app/views/sekolah/dashboard.php");
            exit();
        case 'Supplier':
            header("Location: /mbg-app/views/supplier/dashboard.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Makan Bergizi Gratis (MBG)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #d8e1e8 !important; /* Latar belakang dari tema palet */
        }
        .btn-custom-theme {
            background-color: #304674 !important; /* Warna utama palet */
            border-color: #304674 !important;
            color: #ffffff !important;
            transition: all 0.2s ease-in-out;
        }
        .btn-custom-theme:hover {
            background-color: #243558 !important; /* Gelap sedikit untuk efek hover */
            border-color: #243558 !important;
        }
        .input-group-text-custom {
            background-color: #c6d3e3 !important; /* Warna aksen palet */
            border-color: #b2cbde !important;
        }
        .form-control-custom {
            border-left: none !important;
            border-color: #b2cbde !important;
        }
        .form-control-custom:focus {
            border-color: #304674 !important;
            box-shadow: 0 0 0 0.25rem rgba(48, 70, 116, 0.25) !important;
        }
    </style>
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            
            <div class="card border-0 shadow rounded-3 p-4 bg-white">
                <div class="text-center mb-4">
                    <img src="assets/images/logo.jfif" alt="Logo MBG-APP" class="img-fluid mb-2" style="max-height: 80px; width: auto;" onerror="this.style.display='none'; document.getElementById('fallback-icon').style.display='inline-block';">
                    <div id="fallback-icon" style="display:none;">
                        <i class="bi bi-egg-fried text-warning display-4"></i>
                    </div>
                    <h4 class="fw-bold mt-2 mb-1" style="color: #304674;">MBG-APP</h4>
                    <small class="text-muted text-uppercase tracking-wide" style="font-size: 0.75rem;">Makan Bergizi Gratis</small>
                </div>

                <?php if (isset($_GET['error']) || isset($_GET['pesan'])): ?>
                    <?php 
                        $alert_class = "alert-danger";
                        $pesan_teks = "Terjadi kesalahan sistem.";
                        
                        // Evaluasi Parameter Error
                        if (isset($_GET['error'])) {
                            if ($_GET['error'] == 'empty_fields') {
                                $pesan_teks = "Semua kolom form login wajib diisi!";
                            } elseif ($_GET['error'] == 'wrong_credentials') {
                                $pesan_teks = "Username atau Password salah!";
                            } elseif ($_GET['error'] == 'unauthorized') {
                                $pesan_teks = "Akses ditolak! Anda tidak memiliki otoritas pada modul tersebut.";
                            }
                        } 
                        // Evaluasi Parameter Pesan Biasa
                        elseif (isset($_GET['pesan'])) {
                            if ($_GET['pesan'] == 'belum_login') {
                                $pesan_teks = "Akses ditolak! Silakan login terlebih dahulu.";
                            } elseif ($_GET['pesan'] == 'logout') {
                                $alert_class = "alert-success"; 
                                $pesan_teks = "Anda telah berhasil keluar dari sistem.";
                            }
                        }
                    ?>
                    <div class="alert <?= $alert_class ?> alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <span class="small"><?= $pesan_teks ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="actions/auth/login_process.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold small text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-custom border-end-0"><i class="bi bi-person text-dark"></i></span>
                            <input type="text" class="form-control form-control-custom bg-light" id="username" name="username" placeholder="Masukkan Username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold small text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-custom border-end-0"><i class="bi bi-lock text-dark"></i></span>
                            <input type="password" class="form-control form-control-custom bg-light" id="password" name="password" placeholder="Masukkan Password" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-custom-theme fw-bold text-white py-2 shadow-sm rounded-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk Sistem
                        </button>
                    </div>
                </form>

            </div>
            
            <div class="text-center mt-4">
                <span class="text-muted small" style="font-size: 0.75rem;">
                    &copy; <?= date('Y'); ?> MBG-APP. Hak Cipta Dilindungi Dinas Terkait.
                </span>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>