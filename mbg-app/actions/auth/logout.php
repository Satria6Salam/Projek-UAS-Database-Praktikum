<?php
// =========================================================================
// PROSES PENGHANCURAN SESI (LOGOUT)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Mengosongkan semua variabel array global di $_SESSION
$_SESSION = array();

// 2. Menghapus cookie sesi di sisi peramban klien (Security Mitigation Best Practice)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Menghancurkan seluruh data rekaman sesi secara permanen di web server
session_destroy();

// 4. Mengarahkan kembali ke index.php dengan parameter pesan sukses yang sinkron
header("Location: ../../index.php?pesan=logout");
exit();
?>