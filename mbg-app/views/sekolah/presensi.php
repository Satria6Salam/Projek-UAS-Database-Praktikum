<?php
// =========================================================================
// HEADER, SESSION PROTECTION & CURRENT INITIALIZATION
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set Timezone ke WIB untuk mencegah masalah perbedaan waktu server
date_default_timezone_set('Asia/Jakarta');

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Aturan Bisnis 1: Validasi Single Role Hak Akses Sekolah
$id_sekolah_aktif = $_SESSION['id_sekolah'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_sekolah_aktif || $role_aktif !== 'Sekolah') {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-shield-lock-fill me-2'></i>Akses Ditolak: Khusus Peran Pihak Sekolah.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Aturan Bisnis 5: Time-Locked Guardrail (Membatasi Tanggal Berjalan)
$hari_ini = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$batas_jam_makan = "13:30:00"; 

// =========================================================================
// BYPASS TESTING: MATIKAN KUNCI WAKTU 13:30
// $is_time_locked = ($jam_sekarang > $batas_jam_makan); <-- KODE ASLI
// =========================================================================
$is_time_locked = false; 

// =========================================================================
// BYPASS TESTING: MATIKAN FILTER TANGGAL (Ambil yang paling baru tiba)
// KODE ASLI: WHERE p.id_sekolah = ? AND DATE(p.waktu_kirim) = ? AND p.status = 'Tiba'
// =========================================================================
$query_kirim = "SELECT p.id_kirim, p.jml_porsi_diterima, m.nama_menu 
                FROM pengiriman p
                JOIN menu m ON p.id_menu = m.id_menu
                WHERE p.id_sekolah = ? AND p.status = 'Tiba'
                ORDER BY p.waktu_kirim DESC
                LIMIT 1";
$stmt_kirim = $conn->prepare($query_kirim);

// KODE ASLI: $stmt_kirim->bind_param("ss", $id_sekolah_aktif, $hari_ini);
$stmt_kirim->bind_param("s", $id_sekolah_aktif); // BYPASS TESTING (hanya 1 parameter)
$stmt_kirim->execute();
$res_kirim = $stmt_kirim->get_result()->fetch_assoc();
$stmt_kirim->close();

$id_kirim = $res_kirim['id_kirim'] ?? 0;
$jml_porsi_diterima = $res_kirim['jml_porsi_diterima'] ?? 0;

// Menghitung total siswa yang sudah melakukan presensi hari ini untuk manifes tersebut
$total_presensi_saat_ini = 0;
if ($id_kirim > 0) {
    $query_calc = "SELECT COUNT(*) as total FROM presensi_makan WHERE id_kirim = ?";
    $stmt_calc = $conn->prepare($query_calc);
    $stmt_calc->bind_param("i", $id_kirim);
    $stmt_calc->execute();
    $total_presensi_saat_ini = $stmt_calc->get_result()->fetch_assoc()['total'];
    $stmt_calc->close();
}

// Membaca master data seluruh siswa terdaftar di sekolah terkait
$query_siswa = "SELECT s.nisn, s.nama_siswa, s.kelas,
                (SELECT COUNT(*) FROM presensi_makan pm WHERE pm.id_siswa = s.nisn AND pm.id_kirim = ?) as sudah_makan
                FROM siswa s 
                WHERE s.id_sekolah = ? 
                ORDER BY s.kelas ASC, s.nama_siswa ASC";
$stmt_siswa = $conn->prepare($query_siswa);
$stmt_siswa->bind_param("is", $id_kirim, $id_sekolah_aktif);
$stmt_siswa->execute();
$res_siswa = $stmt_siswa->get_result();
$stmt_siswa->close();
?>

<div class="container-fluid px-2 py-3">
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-gradient-blue text-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h1 class="h5 mb-0 fw-bold"><i class="bi bi-fingerprint me-1"></i>Presensi Makan Murid</h1>
                    <small class="opacity-75"><?= date('d M Y'); ?> • Menu: <strong><?= $res_kirim['nama_menu'] ?? 'Belum Konfirmasi Tiba'; ?></strong></small>
                </div>
                <span class="badge bg-light text-dark rounded-pill py-1.5 px-2.5 small fw-bold">
                    <i class="bi bi-clock-fill text-warning me-1"></i>Batas: 13:30 (Bypass Aktif)
                </span>
            </div>
            
            <div class="row g-2 mt-2 pt-2 border-top border-white-25">
                <div class="col-6 border-end border-white-25 text-center">
                    <small class="d-block opacity-75 text-xs">Porsi Diterima</small>
                    <span id="label-porsi-maks" class="fs-4 fw-black"><?= $jml_porsi_diterima; ?></span>
                </div>
                <div class="col-6 text-center">
                    <small class="d-block opacity-75 text-xs">Siswa Sudah Makan</small>
                    <span id="label-porsi-terpakai" class="fs-4 fw-black text-warning"><?= $total_presensi_saat_ini; ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($id_kirim == 0): ?>
        <div class="alert alert-warning border-0 shadow-sm py-3 text-center mb-3">
            <i class="bi bi-exclamation-octagon-fill fs-3 mb-1 d-block text-warning"></i>
            <h6 class="fw-bold mb-1">Manifes Logistik Belum Siap</h6>
            <p class="small text-muted mb-0">Silakan lakukan konfirmasi kedatangan boks makanan terlebih dahulu pada halaman verifikasi.</p>
        </div>
    <?php elseif ($is_time_locked): ?>
        <div class="alert alert-danger border-0 shadow-sm py-3 text-center mb-3">
            <i class="bi bi-lock-fill fs-3 mb-1 d-block text-danger"></i>
            <h6 class="fw-bold mb-1">Akses Input Terkunci (Time-Locked)</h6>
            <p class="small text-muted mb-0">Sudah melewati batas jam makan operasional yang ditentukan sistem.</p>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label small fw-bold text-secondary"><i class="bi bi-funnel-fill me-1"></i>Saring Kelas</label>
        <div class="d-flex gap-1.5 overflow-x-auto pb-2 scrollbar-hidden">
            <button class="btn btn-sm btn-dark rounded-pill px-3 filter-kelas active" data-kelas="ALL">Semua</button>
            <?php
            $classes = [];
            if ($res_siswa->num_rows > 0) {
                $res_siswa->data_seek(0);
                while($s = $res_siswa->fetch_assoc()) {
                    $classes[] = $s['kelas'];
                }
                $classes = array_unique($classes);
                sort($classes);
                foreach($classes as $kls) {
                    echo '<button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-kelas" data-kelas="'.htmlspecialchars($kls).'">Kelas '.htmlspecialchars($kls).'</button>';
                }
            }
            ?>
        </div>
    </div>

    <div class="row g-2" id="container-daftar-siswa">
        <?php if ($res_siswa->num_rows === 0): ?>
            <div class="col-12 text-center py-5 bg-white rounded shadow-sm">
                <i class="bi bi-people-fill text-black-50 fs-2 d-block"></i>
                <p class="text-muted small mb-0">Belum ada data siswa terdaftar di sekolah ini.</p>
            </div>
        <?php else: ?>
            <?php 
            $res_siswa->data_seek(0);
            while ($siswa = $res_siswa->fetch_assoc()): 
                $checked = ($siswa['sudah_makan'] > 0) ? 'checked' : '';
                $card_status = ($siswa['sudah_makan'] > 0) ? 'border-start-success bg-success-light' : 'border-start-muted bg-white';
            ?>
                <div class="col-12 col-md-6 card-item-siswa" data-kelas-siswa="<?= htmlspecialchars($siswa['kelas']); ?>">
                    <div class="card border-0 shadow-sm rounded-3 <?= $card_status; ?> transition-all">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div class="pe-2 overflow-hidden">
                                <span class="badge bg-secondary-subtle text-secondary text-xs rounded mb-1">Kelas <?= htmlspecialchars($siswa['kelas']); ?></span>
                                <h6 class="mb-0 text-dark fw-bold text-truncate"><?= htmlspecialchars($siswa['nama_siswa']); ?></h6>
                                <small class="text-muted text-xs d-block mt-0.5">NISN: <?= $siswa['nisn']; ?></small>
                            </div>
                            
                            <div class="form-check form-switch p-0 m-0">
                                <input class="form-check-input check-makan-siswa" type="checkbox" role="switch"
                                       data-nisn="<?= $siswa['nisn']; ?>" 
                                       data-id-kirim="<?= $id_kirim; ?>"
                                       <?= $checked; ?>
                                       <?= ($id_kirim == 0 || $is_time_locked) ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.bg-gradient-blue { background: linear-gradient(135deg, #0d6efd 0%, #0a4ebd 100%); }
.border-start-success { border-left: 5px solid #198754 !important; }
.border-start-muted { border-left: 5px solid #dee2e6 !important; }
.bg-success-light { background-color: #e8f5e9 !important; }
.text-xs { font-size: 0.72rem; }
.fw-black { font-weight: 850; }
.overflow-x-auto { white-space: nowrap; -webkit-overflow-scrolling: touch; }
.scrollbar-hidden::-webkit-scrollbar { display: none; }
.scrollbar-hidden { -ms-overflow-style: none; scrollbar-width: none; }
.gap-1\.5 { gap: 0.375rem !important; }
.transition-all { transition: all 0.2s ease-in-out; }
.form-switch .form-check-input { width: 2.5em; height: 1.25em; cursor: pointer; }
</style>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>