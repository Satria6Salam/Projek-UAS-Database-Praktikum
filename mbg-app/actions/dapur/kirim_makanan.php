<?php
// =========================================================================
// BACKEND CONTROLLER: TRANSACTIONAL MANIFEST DISTRIBUTION EXECUTION ENGINE
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/fungsi_stok.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Inisialisasi parameter transaksi input
    $id_dapur_aktif = $_SESSION['id_dapur'] ?? null;
    $id_sekolah     = isset($_POST['id_sekolah']) ? trim($_POST['id_sekolah']) : '';
    $id_menu        = isset($_POST['id_menu']) ? trim($_POST['id_menu']) : '';
    $jml_porsi      = isset($_POST['jml_porsi']) ? intval($_POST['jml_porsi']) : 0;

    // 1. Validasi Parameter Prasyarat Keamanan Aturan Bisnis
    if (!$id_dapur_aktif || empty($id_sekolah) || empty($id_menu) || $jml_porsi <= 0) {
        $_SESSION['error_msg'] = "Gagal memproses pengiriman. Seluruh field formulir manifest wajib diisi secara valid.";
        header("Location: /mbg-app/views/dapur/distribusi.php");
        exit;
    }

    // 2. Ambil Aturan Batasan Maksimal (Kapasitas & Kuota Target Distribusi Sekolah)
    $stmt_quota = $conn->prepare("SELECT jml_siswa FROM sekolah WHERE id_sekolah = ? AND id_dapur = ? LIMIT 1");
    $stmt_quota->bind_param("ss", $id_sekolah, $id_dapur_aktif);
    $stmt_quota->execute();
    $res_quota = $stmt_quota->get_result();

    if ($res_quota->num_rows === 0) {
        $_SESSION['error_msg'] = "Pelanggaran Otoritas: Sekolah sasaran tidak ditemukan atau tidak masuk dalam pemetaan wilayah pelayanan dapur Anda.";
        $stmt_quota->close();
        header("Location: /mbg-app/views/dapur/distribusi.php");
        exit;
    }

    $sekolah = $res_quota->fetch_assoc();
    $stmt_quota->close();

    // Validasi Hilir: Melindungi agar pengiriman logistik tidak melebih batasan jumlah siswa aktif
    if ($jml_porsi > $sekolah['jml_siswa']) {
        $_SESSION['error_msg'] = "Penerbitan Surat Jalan Ditolak: Jumlah porsi boks (" . $jml_porsi . ") melebihi kuota siswa aktif (" . $sekolah['jml_siswa'] . ").";
        header("Location: /mbg-app/views/dapur/distribusi.php");
        exit;
    }

    // -------------------------------------------------------------------------
    // SYSTEM GUARDRAIL LAYER: MEMERIKSA KECUKUPAN SISA STOK SEBELUM DISPATCH
    // -------------------------------------------------------------------------
    // Mengambil seluruh rincian gramasi komposisi per porsi dari kartu resep menu terkait
    $stmt_recipe = $conn->prepare("SELECT id_bahan, jumlah_takaran FROM detail_menu WHERE id_menu = ?");
    $stmt_recipe->bind_param("s", $id_menu);
    $stmt_recipe->execute();
    $res_recipe = $stmt_recipe->get_result();

    if ($res_recipe->num_rows === 0) {
        $_SESSION['error_msg'] = "Gagal memproses: Struktur komposisi detail_menu untuk ID Menu tersebut belum diracik.";
        $stmt_recipe->close();
        header("Location: /mbg-app/views/dapur/distribusi.php");
        exit;
    }

    // Menghimpun Array Kebutuhan Total Komposisi Bahan Produksi
    $kebutuhan_bahan = [];
    while ($item = $res_recipe->fetch_assoc()) {
        // Total Kebutuhan Bahan = Jumlah Porsi Box x Takaran Gramasi per Porsi
        $total_kebutuhan = floatval($jml_porsi) * floatval($item['jumlah_takaran']);
        $kebutuhan_bahan[$item['id_bahan']] = $total_kebutuhan;
    }
    $stmt_recipe->close();

    // Melakukan Pengecekan Ketersediaan Riil Gudang Satu-Per-Satu (Block Execution Rule)
    foreach ($kebutuhan_bahan as $id_bahan => $total_butuh) {
        // PERBAIKAN: Memanggil nama fungsi yang benar dan menyertakan variabel $conn
        $stok_riil_gudang = hitungStokRiil($conn, $id_dapur_aktif, $id_bahan); 

        if ($total_butuh > $stok_riil_gudang) {
            $_SESSION['error_msg'] = "GUARDRAIL BLOCK SYSTEM: Proses pembuatan data pengiriman DIBLOKIR otomatis! Sisa saldo gudang untuk kode bahan '" . $id_bahan . "' tidak mencukupi untuk memproduksi total " . $jml_porsi . " porsi.";
            header("Location: /mbg-app/views/dapur/distribusi.php");
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // TRANSACTION LOCK EXECUTION: WRITING COMPLIANT LOGISTICS SURAT JALAN RECORD
    // -------------------------------------------------------------------------
    $conn->begin_transaction();

    try {
        // Melakukan penulisan data manifes distribusi harian baru dengan status awal: 'Proses'
        $stmt_insert = $conn->prepare("INSERT INTO pengiriman (id_dapur, id_sekolah, id_menu, waktu_kirim, jml_porsi, status) VALUES (?, ?, ?, NOW(), ?, 'Proses')");
        $stmt_insert->bind_param("sssi", $id_dapur_aktif, $id_sekolah, $id_menu, $jml_porsi);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Seluruh tahapan validasi dan prasyarat guardrail dipenuhi secara aman
        $conn->commit();
        
        $_SESSION['success_msg'] = "Surat jalan kurir berhasil diterbitkan secara sah! Manifes pengiriman terkunci dengan status 'Proses' (Dalam Perjalanan).";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "Gagal memproses penulisan manifes distribusi database: " . $e->getMessage();
    }

    header("Location: /mbg-app/views/dapur/distribusi.php");
    exit;
} else {
    header("Location: /mbg-app/views/dapur/distribusi.php");
    exit;
}