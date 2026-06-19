<?php
// =========================================================================
// MANAJEMEN SESI & OTORISASI AKSES (RBAC)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mendapatkan basis URL proyek secara dinamis agar fungsi pengalihan (redirect)
 * selalu akurat tanpa terpengaruh kedalaman subfolder
 */
function dapatkanBaseUrl() {
    $script = $_SERVER['SCRIPT_NAME'];
    if (strpos($script, '/actions/') !== false) {
        $bagian = explode('/actions/', $script);
        return rtrim($bagian[0], '/');
    } elseif (strpos($script, '/views/') !== false) {
        $bagian = explode('/views/', $script);
        return rtrim($bagian[0], '/');
    }
    return rtrim(dirname($script), '/\\\\');
}

/**
 * Validasi Autentikasi Utama
 * Memastikan user wajib memiliki id_user aktif untuk masuk ke sistem
 */
function cekLogin() {
    if (!isset($_SESSION['id_user'])) {
        $base = dapatkanBaseUrl();
        header("Location: " . $base . "/index.php?pesan=belum_login");
        exit();
    }
}

/**
 * Proteksi Role-Based Access Control (RBAC)
 * Membatasi hak akses halaman secara ketat berdasarkan array role yang diizinkan
 */
function cekRole($allowed_roles) {
    cekLogin();
    
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        $base = dapatkanBaseUrl();
        
        switch ($_SESSION['role']) {
            case 'Admin':
                header("Location: " . $base . "/views/admin/dashboard.php?error=unauthorized");
                break;
            case 'Dapur':
                header("Location: " . $base . "/views/dapur/dashboard.php?error=unauthorized");
                break;
            case 'Sekolah':
                header("Location: " . $base . "/views/sekolah/dashboard.php?error=unauthorized");
                break;
            case 'Supplier':
                header("Location: " . $base . "/views/supplier/dashboard.php?error=unauthorized");
                break;
            default:
                header("Location: " . $base . "/index.php?error=unauthorized");
                break;
        }
        exit();
    }
}

// =========================================================================
// FUNGSI ENKRIPSI & VERIFIKASI PASSWORD
// =========================================================================
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password_input, $password_database) {
    // Membandingkan teks murni dari form dengan hash BCRYPT dari database
    return password_verify($password_input, $password_database);
}

// =========================================================================
// FUNGSI SANITASI INPUT (MENCEGAH XSS)
// =========================================================================
function bersihkanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>