<?php
// =========================================================================
// ACTIONS ENGINE: CODE PROCESSING MASTER DATA SUPPLIER VENDOR
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi keamanan & koneksi database absolute path
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';
cekRole(['Admin']);
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['scope_action'])) {
    header("Location: /mbg-app/views/admin/master/supplier.php");
    exit();
}

$scope = isset($_POST['scope_action']) ? $_POST['scope_action'] : (isset($_GET['scope_action']) ? $_GET['scope_action'] : '');

switch ($scope) {
    
    // -------------------------------------------------------------------------
    // CASE: PROSES SIMPAN & UPDATE DATA SUPPLIER VENDOR
    // -------------------------------------------------------------------------
    case 'supplier_process':
        $action = $_POST['form_action'];
        $id_supplier = strtoupper(trim($conn->real_escape_string($_POST['id_supplier'])));
        $nama_vendor = trim($conn->real_escape_string($_POST['nama_vendor']));
        $komoditas = trim($conn->real_escape_string($_POST['komoditas']));
        $no_telp = trim($conn->real_escape_string($_POST['no_telp']));

        // Validasi parameter input wajib
        if (empty($id_supplier) || empty($nama_vendor) || empty($komoditas)) {
            $_SESSION['gagal'] = "Gagal memproses! Seluruh kolom bertanda bintang wajib diisi.";
            header("Location: /mbg-app/views/admin/master/supplier.php");
            exit();
        }

        if ($action === 'create') {
            // Cek pencegahan duplikasi Primary Key unik
            $check_dup = $conn->query("SELECT id_supplier FROM supplier WHERE id_supplier = '$id_supplier' LIMIT 1");
            if ($check_dup && $check_dup->num_rows > 0) {
                $_SESSION['gagal'] = "Registrasi ditolak! ID Supplier [ $id_supplier ] sudah terdaftar di sistem.";
            } else {
                $query = "INSERT INTO supplier (id_supplier, nama_vendor, komoditas, no_telp) VALUES ('$id_supplier', '$nama_vendor', '$komoditas', '$no_telp')";
                if ($conn->query($query)) {
                    $_SESSION['sukses'] = "Vendor mitra [ $nama_vendor ] berhasil diregistrasikan ke sistem terintegrasi.";
                } else {
                    $_SESSION['gagal'] = "Terjadi kegagalan internal database saat menyimpan data vendor.";
                }
            }
        } elseif ($action === 'update') {
            // Proses pembaruan profil data vendor
            $query = "UPDATE supplier SET nama_vendor = '$nama_vendor', komoditas = '$komoditas', no_telp = '$no_telp' WHERE id_supplier = '$id_supplier'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Profil data kemitraan [ $id_supplier ] berhasil diperbarui.";
            } else {
                $_SESSION['gagal'] = "Gagal merubah profil rekam data supplier.";
            }
        }
        break;

    // -------------------------------------------------------------------------
    // CASE: PROSES HAPUS VENDOR DENGAN RESTRICT DELETE RULE
    // -------------------------------------------------------------------------
    case 'delete_supplier':
        $id_delete = $conn->real_escape_string($_GET['id']);
        
        // 1. Cek Relasi Akun Global (Tabel pengguna)
        $check_user = $conn->query("SELECT id_user FROM pengguna WHERE id_supplier = '$id_delete' LIMIT 1");
        
        // 2. Cek Relasi Transaksi Hulu Logistik (Tabel pasokan_bahan) - Restrict Delete Rule
        $check_pasokan = $conn->query("SELECT id_pasokan FROM pasokan_bahan WHERE id_supplier = '$id_delete' LIMIT 1");

        if ($check_user && $check_user->num_rows > 0) {
            $_SESSION['gagal'] = "Penghapusan dicegah! Identitas vendor terkunci karena memiliki kredensial akun pengguna aktif.";
        } 
        elseif ($check_pasokan && $check_pasokan->num_rows > 0) {
            $_SESSION['gagal'] = "Operasi ditolak! Vendor [ $id_delete ] memiliki riwayat rekam transaksi pasokan aktif di gudang dapur umum.";
        } 
        else {
            $query = "DELETE FROM supplier WHERE id_supplier = '$id_delete'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Rekam data kemitraan vendor berhasil dibersihkan dari sistem.";
            } else {
                $_SESSION['gagal'] = "Terjadi kendala internal saat menghapus data records supplier.";
            }
        }
        break;

    default:
        $_SESSION['gagal'] = "Ruang lingkup tindakan operasional tidak dikenali oleh sistem.";
        break;
}

header("Location: /mbg-app/views/admin/master/supplier.php");
exit();