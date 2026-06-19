<?php
// =========================================================================
// ACTIONS ENGINE: CODE PROCESSING USER ACCOUNT MANAGEMENT (RBAC CONTROL)
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';
cekRole(['Admin']);
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['scope_action'])) {
    header("Location: /mbg-app/views/admin/master/pengguna.php");
    exit();
}

$scope = isset($_POST['scope_action']) ? $_POST['scope_action'] : (isset($_GET['scope_action']) ? $_GET['scope_action'] : '');

switch ($scope) {

    // -------------------------------------------------------------------------
    // CASE: REGISTRASI / MODIFIKASI AKUN MULTI-ACTOR
    // -------------------------------------------------------------------------
    case 'user_process':
        $action = $_POST['form_action'];
        $username = trim($conn->real_escape_string($_POST['username']));
        $email = trim($conn->real_escape_string($_POST['email']));
        $role = trim($conn->real_escape_string($_POST['role']));
        
        $id_sekolah = ($role === 'Sekolah' && isset($_POST['id_sekolah'])) ? trim($conn->real_escape_string($_POST['id_sekolah'])) : null;
        $id_staf = ($role === 'Dapur' && isset($_POST['id_staf'])) ? trim($conn->real_escape_string($_POST['id_staf'])) : null;
        $id_supplier = ($role === 'Supplier' && isset($_POST['id_supplier'])) ? trim($conn->real_escape_string($_POST['id_supplier'])) : null;

        if (empty($username) || empty($email) || empty($role)) {
            $_SESSION['gagal'] = "Parameter data tidak komplit. Gagal memproses data akun.";
            header("Location: /mbg-app/views/admin/master/pengguna.php");
            exit();
        }

        if ($action === 'create') {
            $password = $_POST['password'];
            if (empty($password)) {
                $_SESSION['gagal'] = "Pembuatan akun baru mewajibkan kata sandi diisi.";
                header("Location: /mbg-app/views/admin/master/pengguna.php");
                exit();
            }

            // Validasi Unique Username global
            $check_user = $conn->query("SELECT id_user FROM pengguna WHERE username = '$username' LIMIT 1");
            if ($check_user && $check_user->num_rows > 0) {
                $_SESSION['gagal'] = "Nama pengguna [ $username ] telah digunakan oleh aktor lain.";
                header("Location: /mbg-app/views/admin/master/pengguna.php");
                exit();
            }

            // Enskripsi Kredensial (Hashed Password)
            $password_hashed = password_hash($password, PASSWORD_BCRYPT);

            // Mapping Query Null-Safe injection
            $val_sekolah = $id_sekolah ? "'$id_sekolah'" : "NULL";
            $val_staf = $id_staf ? "'$id_staf'" : "NULL";
            $val_supplier = $id_supplier ? "'$id_supplier'" : "NULL";

            $query = "INSERT INTO pengguna (username, password, email, role, id_sekolah, id_staf, id_supplier) 
                      VALUES ('$username', '$password_hashed', '$email', '$role', $val_sekolah, $val_staf, $val_supplier)";
            
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Akun operasional login [ $username ] berhasil diaktifkan ke dalam sistem.";
            } else {
                $_SESSION['gagal'] = "Terjadi kegagalan database saat meregistrasikan kredensial pengguna.";
            }

        } elseif ($action === 'update') {
            $id_user = (int)$_POST['id_user'];
            
            $val_sekolah = $id_sekolah ? "'$id_sekolah'" : "NULL";
            $val_staf = $id_staf ? "'$id_staf'" : "NULL";
            $val_supplier = $id_supplier ? "'$id_supplier'" : "NULL";

            // Update data profil dasar dan pemetaan role aktif
            $query = "UPDATE pengguna SET username = '$username', email = '$email', role = '$role', 
                      id_sekolah = $val_sekolah, id_staf = $val_staf, id_supplier = $val_supplier";

            // Jika admin melakukan pembaharuan password opsional
            if (!empty($_POST['password'])) {
                $password_hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $query .= ", password = '$password_hashed'";
            }

            $query .= " WHERE id_user = $id_user";

            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Konfigurasi kredensial pengguna [ $username ] berhasil diperbarui.";
            } else {
                $_SESSION['gagal'] = "Gagal mengubah data profil pengguna.";
            }
        }
        break;

    // -------------------------------------------------------------------------
    // CASE: TOGGLE STATUS AKTIVASI AKUN OPERASIONAL
    // -------------------------------------------------------------------------
    case 'toggle_status':
        $id_user = (int)$_GET['id'];
        $current_role = $conn->real_escape_string($_GET['role']);

        // Mencegah super admin menonaktifkan akun sendiri demi keamanan siklus sistem
        if ($current_role === 'Admin') {
            $_SESSION['gagal'] = "Proteksi Sistem: Akun Admin Utama tidak diizinkan untuk diubah status penonaktifannya.";
        } else {
            // Dalam database murni, penghapusan akun dialihkan menggunakan skema hapus permanen atau restrict rule. 
            // Karena tidak ada kolom status di spek kamus data, aksi hapus menerapkan Restrict Rule jika memiliki riwayat log transaksi di subsistem.
            $query = "DELETE FROM pengguna WHERE id_user = $id_user";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Akun akses login operasional berhasil dihapus dari sistem pusat.";
            } else {
                $_SESSION['gagal'] = "Restrict Delete: Akun terikat rekam jejak audit sistem hulu/hilir.";
            }
        }
        break;

    default:
        $_SESSION['gagal'] = "Lingkup pemrosesan manajemen akun tidak dikenali.";
        break;
}

header("Location: /mbg-app/views/admin/master/pengguna.php");
exit();