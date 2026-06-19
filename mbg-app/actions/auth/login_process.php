<?php
// =========================================================================
// PROSES VALIDASI LOGIN MULTI-ACTOR - VERSI SINKRONISASI HASH & RELASI SESSION
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memanggil koneksi database secara aman
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil data POST dan bersihkan spasi di ujung input saja
    $username_raw = isset($_POST['username']) ? $_POST['username'] : '';
    $password_raw = isset($_POST['password']) ? $_POST['password'] : '';

    $username = trim($username_raw);
    $password = trim($password_raw);

    if (empty($username) || empty($password)) {
        header("Location: ../../index.php?error=empty_fields");
        exit();
    }

    // 2. Prepared Statement (Mencegah SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE username = ? LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // 3. Proses Verifikasi Password (Mendukung Teks Polos & Hash BCRYPT)
            // Catatan: Menggunakan password_verify jika password sudah di-hash, atau string comparison jika masih uji coba polos
            $is_password_valid = false;
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                $is_password_valid = true;
            }

            if ($is_password_valid) {
                
                // Registrasi Session Aktif Dasar
                $_SESSION['id_user']     = $user['id_user'];
                $_SESSION['username']    = $user['username'];
                $_SESSION['role']        = $user['role'];

                $_SESSION['id_sekolah']  = !empty($user['id_sekolah']) ? $user['id_sekolah'] : null;
                $_SESSION['id_staf']     = !empty($user['id_staf']) ? $user['id_staf'] : null;
                $_SESSION['id_supplier'] = !empty($user['id_supplier']) ? $user['id_supplier'] : null;

                // -------------------------------------------------------------------------
                // OPTIMISASI: MENARIK ID_DAPUR UTK AKTOR STAF DAPUR (MENCEGAH FK CONSTRAINT ERROR)
                // -------------------------------------------------------------------------
                $_SESSION['id_dapur'] = null; // Default awal kosong

                if ($user['role'] === 'Dapur' && !empty($user['id_staf'])) {
                    $stmt_dapur = $conn->prepare("SELECT id_dapur FROM staf_dapur WHERE id_staf = ? LIMIT 1");
                    if ($stmt_dapur) {
                        $stmt_dapur->bind_param("s", $user['id_staf']);
                        $stmt_dapur->execute();
                        $res_dapur = $stmt_dapur->get_result();
                        if ($res_dapur->num_rows > 0) {
                            $staf = $res_dapur->fetch_assoc();
                            $_SESSION['id_dapur'] = $staf['id_dapur']; // ID Dapur riil berhasil dikunci ke session
                        }
                        $stmt_dapur->close();
                    }
                }

                $stmt->close();
                $conn->close();

                // 4. Pengalihan halaman berdasarkan role sesuai hak akses (RBAC)
                switch ($_SESSION['role']) {
                    case 'Admin':
                        header("Location: ../../views/admin/dashboard.php");
                        break;
                    case 'Dapur':
                        header("Location: ../../views/dapur/dashboard.php");
                        break;
                    case 'Sekolah':
                        header("Location: ../../views/sekolah/dashboard.php");
                        break;
                    case 'Supplier':
                        header("Location: ../../views/supplier/dashboard.php");
                        break;
                    default:
                        header("Location: ../../index.php?error=unauthorized");
                        break;
                }
                exit();
                
            } else {
                // Password salah
                $stmt->close();
                $conn->close();
                header("Location: ../../index.php?error=wrong_credentials");
                exit();
            }
        } else {
            // Username tidak ditemukan
            $stmt->close();
            $conn->close();
            header("Location: ../../index.php?error=wrong_credentials");
            exit();
        }
    } else {
        die("Gagal memproses query keamanan database: " . $conn->error);
    }
} else {
    header("Location: ../../index.php");
    exit();
}
?>