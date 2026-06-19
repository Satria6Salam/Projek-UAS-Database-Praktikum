<?php
// =========================================================================
// BACKEND ENGINE: TRANSACTION VALIDATION & SECURE LOG STORAGE
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Validasi Metode Pengiriman Form Data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /mbg-app/views/sekolah/verifikasi.php");
    exit;
}

// Hak Akses Guardrail Multi-Actor Check
$id_sekolah_aktif = $_SESSION['id_sekolah'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_sekolah_aktif || $role_aktif !== 'Sekolah') {
    $_SESSION['error_msg'] = "Pelanggaran Autentikasi: Hak akses modifikasi dibekukan.";
    header("Location: /mbg-app/views/sekolah/verifikasi.php");
    exit;
}

// Ekstraksi & Sanitasi Parameter POST Data Input
$id_kirim = isset($_POST['id_kirim']) ? intval($_POST['id_kirim']) : 0;
$jml_rusak = isset($_POST['jml_rusak']) ? intval($_POST['jml_rusak']) : 0;
$jml_porsi_diterima = isset($_POST['jml_porsi_diterima']) ? intval($_POST['jml_porsi_diterima']) : 0;

// =========================================================================
// DATA MASTER DATABASE GUARDRAIL CHECKS
// =========================================================================

// Ambil data manifes untuk memverifikasi kepemilikan kuota pengiriman aktif
$query_check = "SELECT id_kirim, id_sekolah, jml_porsi, status FROM pengiriman WHERE id_kirim = ? LIMIT 1";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("i", $id_kirim);
$stmt_check->execute();
$manifes = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

// 1. Validasi eksistensi manifes di database
if (!$manifes) {
    $_SESSION['error_msg'] = "Transaksi Ditolak: Nomor nota manifes pengiriman tidak terdaftar.";
    header("Location: /mbg-app/views/sekolah/verifikasi.php");
    exit;
}

// 2. Validasi Hak Kepemilikan (Mencegah Aktor Sekolah A mengubah data Sekolah B)
if ($manifes['id_sekolah'] !== $id_sekolah_aktif) {
    $_SESSION['error_msg'] = "Akses Ilegal: Anda tidak memiliki otoritas atas nota pengiriman ini.";
    header("Location: /mbg-app/views/sekolah/verifikasi.php");
    exit;
}

// 3. Validasi Status Transaksi Berjalan (Mencegah manipulasi data yang sudah 'Tiba')
if ($manifes['status'] === 'Tiba') {
    $_SESSION['error_msg'] = "Data Terkunci: Status kedatangan logistik telah dikonfirmasi sebelumnya.";
    header("Location: /mbg-app/views/sekolah/verifikasi.php");
    exit;
}

// 4. Validasi Integritas Data (Jumlah porsi diterima tidak boleh anomali)
if ($jml_porsi_diterima < 0 || ($jml_porsi_diterima + $jml_rusak) !== intval($manifes['jml_porsi'])) {
    $_SESSION['error_msg'] = "Gagal Validasi: Kalkulasi porsi rusak dan porsi selamat tidak sinkron dengan data awal.";
    header("Location: /mbg-app/views/sekolah/verifikasi.php");
    exit;
}

// =========================================================================
// EXECUTE TRANSACTION DATABASE UPDATE
// =========================================================================
$conn->begin_transaction();

try {
    // Memperbarui status menjadi 'Tiba' dan mengunci kuota porsi riil layak konsumsi harian
    $query_update = "UPDATE pengiriman 
                     SET status = 'Tiba', 
                         jml_porsi_diterima = ?, 
                         waktu_kirim = waktu_kirim 
                     WHERE id_kirim = ?";
    
    // Catatan teknis: waktu_kirim dipertahankan, penanda waktu tiba aktual mengacu pada modifikasi log record ini
    $stmt_up = $conn->prepare($query_update);
    $stmt_up->bind_param("ii", $jml_porsi_diterima, $id_kirim);
    $stmt_up->execute();
    $stmt_up->close();

    $conn->commit();
    $_SESSION['success_msg'] = "Sukses Validasi: Logistik masuk #KRM-" . str_pad($id_kirim, 5, '0', STR_PAD_LEFT) . " berhasil diterima sebanyak " . number_format($jml_porsi_diterima) . " boks aman siap konsumsi.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = "Sistem Error: Gagal mengonfirmasi manifes kedatangan. Silakan hubungi admin.";
}

$conn->close();
header("Location: /mbg-app/views/sekolah/verifikasi.php");
exit;