<?php
// =========================================================================
// BACKEND REAL-TIME API: SECURE TRANSACTIONAL TIME-LOCKED GUARDRAILS
// =========================================================================
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set Timezone agar sinkron
date_default_timezone_set('Asia/Jakarta');

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Aturan Bisnis 1: Proteksi Kredensial Multi-Actor
$id_sekolah_aktif = $_SESSION['id_sekolah'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_sekolah_aktif || $role_aktif !== 'Sekolah') {
    echo json_encode(['status' => 'error', 'message' => 'Sesi kedaluwarsa atau hak akses dibekukan.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode transmisi data tidak valid.']);
    exit;
}

$id_siswa = isset($_POST['id_siswa']) ? trim($_POST['id_siswa']) : '';
$id_kirim = isset($_POST['id_kirim']) ? intval($_POST['id_kirim']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : 'insert';

// 1. Validasi kesesuaian data manifes logistik
$query_manifest = "SELECT id_sekolah, jml_porsi_diterima, status, waktu_kirim 
                   FROM pengiriman WHERE id_kirim = ? LIMIT 1";
$stmt_m = $conn->prepare($query_manifest);
$stmt_m->bind_param("i", $id_kirim);
$stmt_m->execute();
$manifest = $stmt_m->get_result()->fetch_assoc();
$stmt_m->close();

if (!$manifest || $manifest['id_sekolah'] !== $id_sekolah_aktif || $manifest['status'] !== 'Tiba') {
    echo json_encode(['status' => 'error', 'message' => 'Nota pengiriman tidak valid atau belum berstatus Tiba.']);
    exit;
}

// =========================================================================
// BYPASS TESTING: MATIKAN GUARDRAIL TANGGAL & JAM SEMENTARA
// =========================================================================
/* $hari_ini = date('Y-m-d');
$tgl_terima = date('Y-m-d', strtotime($manifest['waktu_kirim']));
$jam_sekarang = date('H:i:s');
$batas_jam_makan = "13:30:00";

if ($tgl_terima !== $hari_ini) {
    echo json_encode(['status' => 'error', 'message' => 'Akses Terkunci: Presensi hanya diizinkan pada tanggal berjalan hari pelaksanaan makan harian.']);
    exit;
}

if ($jam_sekarang > $batas_jam_makan) {
    echo json_encode(['status' => 'error', 'message' => 'Batas Waktu Habis: Sistem otomatis mengunci input karena jam makan siang telah selesai (Max 13:30).']);
    exit;
}
*/
// =========================================================================

// TRANSACTION EXECUTION MECHANISM
$conn->begin_transaction();

try {
    if ($action === 'insert') {
        $query_dup = "SELECT id_presensi FROM presensi_makan WHERE id_siswa = ? AND id_kirim = ? LIMIT 1";
        $stmt_d = $conn->prepare($query_dup);
        $stmt_d->bind_param("si", $id_siswa, $id_kirim);
        $stmt_d->execute();
        $is_duplicate = $stmt_d->get_result()->num_rows > 0;
        $stmt_d->close();

        if ($is_duplicate) {
            throw new Exception("Siswa yang bersangkutan sudah terekam mengambil porsi makan siang hari ini.");
        }

        $query_count = "SELECT COUNT(*) as total FROM presensi_makan WHERE id_kirim = ? FOR UPDATE";
        $stmt_c = $conn->prepare($query_count);
        $stmt_c->bind_param("i", $id_kirim); // FIX: Tambahkan parameter binding yang sebelumnya terlewat di kode Anda
        $stmt_c->execute();
        $current_total = $stmt_c->get_result()->fetch_assoc()['total'];
        $stmt_c->close();

        if (($current_total + 1) > $manifest['jml_porsi_diterima']) {
            throw new Exception("Kuota Habis: Akumulasi presensi secara mutlak dilarang melebihi jumlah porsi aktual yang layak konsumsi (" . $manifest['jml_porsi_diterima'] . " boks).");
        }

        $query_insert = "INSERT INTO presensi_makan (id_siswa, id_kirim, waktu_presensi) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $stmt_i = $conn->prepare($query_insert);
        $stmt_i->bind_param("si", $id_siswa, $id_kirim);
        $stmt_i->execute();
        $stmt_i->close();
        
        $final_total = $current_total + 1;

    } elseif ($action === 'delete') {
        $query_delete = "DELETE FROM presensi_makan WHERE id_siswa = ? AND id_kirim = ?";
        $stmt_del = $conn->prepare($query_delete);
        $stmt_del->bind_param("si", $id_siswa, $id_kirim);
        $stmt_del->execute();
        $stmt_del->close();

        $query_count = "SELECT COUNT(*) as total FROM presensi_makan WHERE id_kirim = ?";
        $stmt_c = $conn->prepare($query_count);
        $stmt_c->bind_param("i", $id_kirim); // FIX: Tambahkan parameter binding
        $stmt_c->execute();
        $final_total = $stmt_c->get_result()->fetch_assoc()['total'];
        $stmt_c->close();
    }

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => 'Data kehadiran berhasil diperbarui.',
        'current_total' => $final_total
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
exit;