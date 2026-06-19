<?php
// =========================================================================
// BACKEND CONTROLLER: TRANSACTION SIMULTANEOUS INJECTION (ACID COMPLIANT)
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitisasi data payload form utama resep makanan
    $id_menu        = mysqli_real_escape_string($conn, trim($_POST['id_menu']));
    $id_dapur       = mysqli_real_escape_string($conn, trim($_POST['id_dapur']));
    $nama_menu      = mysqli_real_escape_string($conn, trim($_POST['nama_menu']));
    $kalori         = intval($_POST['kalori']);
    $deskripsi      = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));

    // Array Komposisi Bahan
    $array_bahan    = $_POST['id_bahan'] ?? [];
    $array_takaran  = $_POST['jumlah_takaran'] ?? [];

    // Validasi Dasar Integritas Struktur Payload Form
    if (empty($id_menu) || empty($nama_menu) || empty($id_dapur) || $kalori <= 0 || empty($array_bahan)) {
        $_SESSION['error_msg'] = "Gagal memproses resep. Isian parameter utama tidak valid atau array komposisi kosong.";
        header("Location: /mbg-app/views/dapur/menu.php");
        exit;
    }

    // -------------------------------------------------------------------------
    // CRITICAL: DATABASE ACID TRANSACTION ENFORCEMENT
    // -------------------------------------------------------------------------
    $conn->begin_transaction();

    try {
        // 1. Cek Duplikasi Kode Unik ID Menu Utama
        $stmt_check = $conn->prepare("SELECT id_menu FROM menu WHERE id_menu = ?");
        $stmt_check->bind_param("s", $id_menu);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            throw new Exception("Kode ID Menu '$id_menu' telah terdaftar di pangkalan data dapur. Gunakan kode identifikasi unik lain.");
        }
        $stmt_check->close();

        // 2. Injeksi Data ke Induk Tabel: menu
        $stmt_menu = $conn->prepare("INSERT INTO menu (id_menu, id_dapur, nama_menu, kalori, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt_menu->bind_param("sssis", $id_menu, $id_dapur, $nama_menu, $kalori, $deskripsi);
        $stmt_menu->execute();
        $stmt_menu->close();

        // 3. Injeksi Kolektif Dinamis ke Anak Tabel Relasi: detail_menu
        $stmt_detail = $conn->prepare("INSERT INTO detail_menu (id_menu, id_bahan, jumlah_takaran) VALUES (?, ?, ?)");
        
        // Melakukan penelusuran array ganda (id_bahan[] dan jumlah_takaran[]) secara simultan
        for ($i = 0; $i < count($array_bahan); $i++) {
            $id_bahan       = mysqli_real_escape_string($conn, trim($array_bahan[$i]));
            $jumlah_takaran = floatval($array_takaran[$i]); // Mendukung gramasi berformat desimal

            if (!empty($id_bahan) && $jumlah_takaran > 0) {
                $stmt_detail->bind_param("ssd", $id_menu, $id_bahan, $jumlah_takaran);
                $stmt_detail->execute();
            } else {
                throw new Exception("Nilai takaran gramasi desimal komponen tidak boleh bernilai $\le$ 0 atau kosong.");
            }
        }
        $stmt_detail->close();

        // Jika seluruh baris eksekusi berhasil tanpa ada kendala, kunci data secara permanen
        $conn->commit();
        $_SESSION['success_msg'] = "Formula variasi menu resep digital '$nama_menu' sukses disimpan dua arah secara terintegrasi.";
        
    } catch (Exception $e) {
        // Gagalkan seluruh perubahan data jika terjadi salah satu eror interupsi integritas resep
        $conn->rollback();
        $_SESSION['error_msg'] = "Gagal Mengunci Transaksi Relasi Tabel: " . $e->getMessage();
    }

    // Mengalihkan alur kembali menuju halaman manajemen antarmuka digital kartu resep dapur
    header("Location: /mbg-app/views/dapur/menu.php");
    exit;
}