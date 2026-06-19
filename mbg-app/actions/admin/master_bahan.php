<?php
// =========================================================================
// ACTIONS ENGINE: CODE PROCESSING MASTER DATA BAHAN BAKU
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';
cekRole(['Admin']);
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['scope_action'])) {
    header("Location: /mbg-app/views/admin/master/bahan.php");
    exit();
}

$scope = isset($_POST['scope_action']) ? $_POST['scope_action'] : (isset($_GET['scope_action']) ? $_GET['scope_action'] : '');

switch ($scope) {
    
    // -------------------------------------------------------------------------
    // CASE: PROSES SIMPAN & UPDATE DATA BAHAN BAKU
    // -------------------------------------------------------------------------
    case 'bahan_process':
        $action = $_POST['form_action'];
        $id_bahan = strtoupper(trim($conn->real_escape_string($_POST['id_bahan'])));
        $nama_bahan = trim($conn->real_escape_string($_POST['nama_bahan']));
        $satuan = trim($conn->real_escape_string($_POST['satuan']));
        $stok_min = intval($_POST['stok_min']);

        if (empty($id_bahan) || empty($nama_bahan) || empty($satuan) || $stok_min < 0) {
            $_SESSION['gagal'] = "Gagal memproses! Lengkapi parameter input dengan valid.";
            header("Location: /mbg-app/views/admin/master/bahan.php");
            exit();
        }

        if ($action === 'create') {
            $check_dup = $conn->query("SELECT id_bahan FROM bahan_baku WHERE id_bahan = '$id_bahan' LIMIT 1");
            if ($check_dup && $check_dup->num_rows > 0) {
                $_SESSION['gagal'] = "Registrasi gagal! ID Bahan [ $id_bahan ] sudah ada di database.";
            } else {
                $query = "INSERT INTO bahan_baku (id_bahan, nama_bahan, satuan, stok_min) VALUES ('$id_bahan', '$nama_bahan', '$satuan', $stok_min)";
                if ($conn->query($query)) {
                    $_SESSION['sukses'] = "Komoditas [ $nama_bahan ] berhasil ditambahkan ke katalog global.";
                } else {
                    $_SESSION['gagal'] = "Terjadi kegagalan sistem saat menyimpan data komoditas.";
                }
            }
        } elseif ($action === 'update') {
            $query = "UPDATE bahan_baku SET nama_bahan = '$nama_bahan', satuan = '$satuan', stok_min = $stok_min WHERE id_bahan = '$id_bahan'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Katalog bahan baku [ $id_bahan ] berhasil diperbarui.";
            } else {
                $_SESSION['gagal'] = "Gagal memperbarui rekam katalog bahan baku.";
            }
        }
        break;

    // -------------------------------------------------------------------------
    // CASE: PROSES HAPUS BAHAN BAKU DENGAN RESTRICT DELETE RULE
    // -------------------------------------------------------------------------
    case 'delete_bahan':
        $id_delete = $conn->real_escape_string($_GET['id']);
        
        // Cek riwayat penggunaan pada formulir resep (detail_menu)
        $check_menu = $conn->query("SELECT id_detail FROM detail_menu WHERE id_bahan = '$id_delete' LIMIT 1");
        
        // Cek riwayat transaksi pasokan logistik hulu (pasokan_bahan)
        $check_pasokan = $conn->query("SELECT id_pasokan FROM pasokan_bahan WHERE id_bahan = '$id_delete' LIMIT 1");

        if ($check_menu && $check_menu->num_rows > 0) {
            $_SESSION['gagal'] = "Restrict Delete Rule: Bahan baku terkunci karena menjadi bagian dari formula resep menu aktif.";
        } 
        elseif ($check_pasokan && $check_pasokan->num_rows > 0) {
            $_SESSION['gagal'] = "Restrict Delete Rule: Bahan baku tidak boleh dihapus karena memiliki rekam histori pasokan logistik.";
        } 
        else {
            $query = "DELETE FROM bahan_baku WHERE id_bahan = '$id_delete'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Komoditas berhasil dihapus dari master katalog global.";
            } else {
                $_SESSION['gagal'] = "Gagal membersihkan data bahan baku dari database.";
            }
        }
        break;

    default:
        $_SESSION['gagal'] = "Ruang lingkup aksi pemrosesan tidak valid.";
        break;
}

header("Location: /mbg-app/views/admin/master/bahan.php");
exit();