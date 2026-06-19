<?php
// =========================================================================
// BACKEND CONTROLLER: TRANSACTIONAL LOGISTICS SUPPLY ENTRY ENGINE
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Inisialisasi Kredensial dan Validasi Mutlak Multi-Actor Role
    $id_supplier_aktif = $_SESSION['id_supplier'] ?? null;
    $role_aktif = $_SESSION['role'] ?? '';

    if (!$id_supplier_aktif || $role_aktif !== 'Supplier') {
        $_SESSION['error_msg'] = "Pelanggaran Keamanan: Sesi Anda habis atau Anda tidak memiliki otoritas menerbitkan transaksi ini.";
        header("Location: /mbg-app/views/supplier/form_pasok.php");
        exit;
    }

    // Mengambil Parameter Form Input Nota Pengiriman
    $id_dapur   = isset($_POST['id_dapur']) ? trim($_POST['id_dapur']) : '';
    $id_bahan   = isset($_POST['id_bahan']) ? trim($_POST['id_bahan']) : '';
    $jumlah     = isset($_POST['jumlah']) ? floatval($_POST['jumlah']) : 0.00;
    $tgl_masuk  = isset($_POST['tgl_masuk']) ? trim($_POST['tgl_masuk']) : '';

    // 1. Validasi Integritas Isian Formulir
    if (empty($id_dapur) || empty($id_bahan) || $jumlah <= 0 || empty($tgl_masuk)) {
        $_SESSION['error_msg'] = "Gagal memproses nota: Seluruh field input logistik wajib diisi secara lengkap dan kuantitas harus bernilai positif.";
        header("Location: /mbg-app/views/supplier/form_pasok.php");
        exit;
    }

    // 2. Memastikan Relasi Keberadaan Hubungan Hukum Mitra Dapur Penerima
    $stmt_check_dapur = $conn->prepare("SELECT id_dapur FROM dapur_umum WHERE id_dapur = ? LIMIT 1");
    $stmt_check_dapur->bind_param("s", $id_dapur);
    $stmt_check_dapur->execute();
    $res_dapur = $stmt_check_dapur->get_result();

    if ($res_dapur->num_rows === 0) {
        $_SESSION['error_msg'] = "Aturan Bisnis Dilanggar: Unit Dapur Umum yang dipilih tidak terdaftar di dalam database pusat.";
        $stmt_check_dapur->close();
        header("Location: /mbg-app/views/supplier/form_pasok.php");
        exit;
    }
    $stmt_check_dapur->close();

    // 3. Memartisi Transaksi Masuk ke Tabel Pasokan Bahan (Mengunci Status Awal: 'Pending')
    $conn->begin_transaction();

    try {
        // Melakukan penulisan baris baru dengan status bawaan mutlak 'Pending' (Otoritas Penguncian Data Hulu)
        $query_insert = "INSERT INTO pasokan_bahan (id_supplier, id_dapur, id_bahan, tgl_masuk, jumlah, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("ssssd", $id_supplier_aktif, $id_dapur, $id_bahan, $tgl_masuk, $jumlah);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Commit transaksi data aman
        $conn->commit();
        
        $_SESSION['success_msg'] = "Nota pengiriman komoditas berhasil diterbitkan ke sistem! Status terkunci 'Pending' menunggu verifikasi fisik oleh Juru Masak Dapur Umum.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "Gagal mencatatkan transaksi logistik ke database: " . $e->getMessage();
    }

    $conn->close();
    header("Location: /mbg-app/views/supplier/form_pasok.php");
    exit;

} else {
    // Menolak Akses Direct URL Method GET secara sepihak
    header("Location: /mbg-app/views/supplier/form_pasok.php");
    exit;
}