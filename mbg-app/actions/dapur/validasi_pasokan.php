<?php
// =========================================================================
// BACKEND CONTROLLER: SECURE LOGISTICS SUPPLY VALIDATION CONTROL ENGINE
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Mengambil dan memastikan parameter input aman dari manipulasi
    $id_pasokan     = isset($_POST['id_pasokan']) ? intval($_POST['id_pasokan']) : 0;
    $status_request = isset($_POST['status']) ? trim($_POST['status']) : '';
    $id_dapur_aktif = $_SESSION['id_dapur'] ?? null;

    // Validasi parameter wajib operasi backend
    if ($id_pasokan <= 0 || !in_array($status_request, ['Disetujui', 'Ditolak']) || !$id_dapur_aktif) {
        $_SESSION['error_msg'] = "Gagal memproses validasi. Parameter input tidak valid atau hak akses ditolak.";
        header("Location: /mbg-app/views/dapur/verifikasi_bahan.php");
        exit;
    }

    // -------------------------------------------------------------------------
    // ENFORCEMENT: ACID TRANSACTION FOR PERMANENT DATA MUTATION LOCKING
    // -------------------------------------------------------------------------
    $conn->begin_transaction();

    try {
        // 1. Ambil data pasokan saat ini dan verifikasi kepemilikan wilayah otoritas dapur
        $stmt_check = $conn->prepare("SELECT status, id_dapur, id_bahan, jumlah FROM pasokan_bahan WHERE id_pasokan = ? FOR UPDATE");
        $stmt_check->bind_param("i", $id_pasokan);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows === 0) {
            throw new Exception("Rekam data transaksi nota pasokan tidak ditemukan di database.");
        }

        $pasokan = $res_check->fetch_assoc();
        $stmt_check->close();

        // Keamanan Tambahan: Cegah petugas dapur memvalidasi pasokan milik dapur wilayah lain
        if ($pasokan['id_dapur'] !== $id_dapur_aktif) {
            throw new Exception("Otoritas ditolak. Anda tidak berwenang mengelola logistik unit dapur lain.");
        }

        // Rule Transaksi Hulu: Data berstatus selain 'Pending' dikunci secara permanen (tidak bisa diubah sepihak)
        if ($pasokan['status'] !== 'Pending') {
            throw new Exception("Data transaksi nota ini telah dibekukan dan terkunci secara permanen sebelumnya.");
        }

        // 2. Eksekusi Mutasi Perubahan Status Nota Pasokan Bahan
        $stmt_update = $conn->prepare("UPDATE pasokan_bahan SET status = ? WHERE id_pasokan = ?");
        $stmt_update->bind_param("si", $status_request, $id_pasokan);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. Aturan Bisnis Stok Otomatis: Nilai akumulasi stok bertambah di gudang HANYA jika status = 'Disetujui'
        // Catatan: Karena penentuan nilai stok riil bersifat dinamis melalui rumus agregat di 'fungsi_stok.php':
        // Stok Riil = Total Pasokan Terverifikasi - Akumulasi Kebutuhan Bahan Menu Terkirim,
        // Maka dengan diubahnya status nota pasokan menjadi 'Disetujui', secara otomatis hitungan rumus dinamis 
        // stok riil dapur penerima akan langsung bertambah valid pada pembacaan berikutnya.
        
        // Komit seluruh rangkaian transaksi terintegrasi jika seluruh langkah di atas sukses
        $conn->commit();
        
        if ($status_request === 'Disetujui') {
            $_SESSION['success_msg'] = "Nota #PSK-" . str_pad($id_pasokan, 5, '0', STR_PAD_LEFT) . " berhasil DISETUJUI. Pasokan terkunci permanen dan stok riil gudang otomatis bertambah.";
        } else {
            $_SESSION['success_msg'] = "Nota #PSK-" . str_pad($id_pasokan, 5, '0', STR_PAD_LEFT) . " telah DITOLAK secara permanen oleh sistem dapur.";
        }

    } catch (Exception $e) {
        // Gagalkan seluruh perubahan mutasi status ke database apabila interupsi sistem/validasi dilanggar
        $conn->rollback();
        $_SESSION['error_msg'] = "Gagal memproses keputusan logistik: " . $e->getMessage();
    }

    // Refresh antrean mengalihkan halaman kembali ke antarmuka kontrol kualitas hulu dapur
    header("Location: /mbg-app/views/dapur/verifikasi_bahan.php");
    exit;
} else {
    header("Location: /mbg-app/views/dapur/verifikasi_bahan.php");
    exit;
}