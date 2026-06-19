<?php
/**
 * Title: School Portal Dashboard & Real-time Logistics Tracker
 * Description: Memantau status pengiriman logistik boks makanan harian (Proses / Tiba),
 * akumulasi kuota porsi terpakai, serta statistik kehadiran makan siswa.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// WAJIB DITAMBAHKAN: Mengunci zona waktu server agar sinkron dengan Waktu Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// Integrasi komponen global layout app
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Aturan Bisnis 1: Proteksi Autentikasi & Batasan Hak Akses (Single Role)
$id_sekolah_aktif = $_SESSION['id_sekolah'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_sekolah_aktif || $role_aktif !== 'Sekolah') {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-shield-lock-fill me-2'></i>Akses Ditolak: Khusus Peran Pihak Sekolah yang Terautentikasi.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Inisialisasi variabel tanggal hari ini
$hari_ini = date('Y-m-d');

// Query 1: Mengambil profile ringkas sekolah & jumlah total target siswa aktif
$query_sekolah = "SELECT nama_sekolah, jml_siswa FROM sekolah WHERE id_sekolah = ? LIMIT 1";
$stmt_sch = $conn->prepare($query_sekolah);
$stmt_sch->bind_param("s", $id_sekolah_aktif);
$stmt_sch->execute();
$data_sekolah = $stmt_sch->get_result()->fetch_assoc();
$stmt_sch->close();

// =========================================================================
// KODE ASLI (PRODUKSI) - FILTER TANGGAL AKTIF (DIMATIKAN SEMENTARA)
// =========================================================================
/*
$query_logistik = "SELECT p.id_kirim, p.jml_porsi, p.jml_porsi_diterima, p.status, p.waktu_kirim, 
                          m.nama_menu, m.kalori, d.nama_dapur
                   FROM pengiriman p
                   JOIN menu m ON p.id_menu = m.id_menu
                   JOIN dapur_umum d ON p.id_dapur = d.id_dapur
                   WHERE p.id_sekolah = ? AND DATE(p.waktu_kirim) = ?
                   ORDER BY p.id_kirim DESC LIMIT 1";
$stmt_log = $conn->prepare($query_logistik);
$stmt_log->bind_param("ss", $id_sekolah_aktif, $hari_ini);
*/

// =========================================================================
// BYPASS TESTING - MENAMPILKAN DATA TERAKHIR TANPA PEDULI TANGGAL
// =========================================================================
$query_logistik = "SELECT p.id_kirim, p.jml_porsi, p.jml_porsi_diterima, p.status, p.waktu_kirim, 
                          m.nama_menu, m.kalori, d.nama_dapur
                   FROM pengiriman p
                   JOIN menu m ON p.id_menu = m.id_menu
                   JOIN dapur_umum d ON p.id_dapur = d.id_dapur
                   WHERE p.id_sekolah = ?
                   ORDER BY p.id_kirim DESC LIMIT 1";
$stmt_log = $conn->prepare($query_logistik);
$stmt_log->bind_param("s", $id_sekolah_aktif);
// =========================================================================

$stmt_log->execute();
$data_logistik = $stmt_log->get_result()->fetch_assoc();
$stmt_log->close();

// Inisialisasi data akumulasi presensi jika manifes ada
$id_kirim_hari_ini = $data_logistik['id_kirim'] ?? 0;
$total_siswa_sudah_makan = 0;

if ($id_kirim_hari_ini > 0) {
    // Query 3: Menghitung total real-time ketukan presensi makan murid berjalan
    $query_presensi = "SELECT COUNT(*) as total FROM presensi_makan WHERE id_kirim = ?";
    $stmt_pres = $conn->prepare($query_presensi);
    $stmt_pres->bind_param("i", $id_kirim_hari_ini);
    $stmt_pres->execute();
    $total_siswa_sudah_makan = $stmt_pres->get_result()->fetch_assoc()['total'];
    $stmt_pres->close();
}
?>

<div class="container-fluid px-3 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-primary text-white p-4 rounded-3 shadow-sm d-flex align-items-center justify-content-between position-relative overflow-hidden card-banner">
                <div>
                    <span class="badge bg-white text-primary rounded-pill mb-2 fw-bold px-3 py-1"><i class="bi bi-bank me-1"></i>Portal Sekolah</span>
                    <h2 class="fw-bold mb-1"><?= htmlspecialchars($data_sekolah['nama_sekolah'] ?? 'Nama Instansi Sekolah'); ?></h2>
                    <p class="mb-0 opacity-85 small"><i class="bi bi-calendar3 me-1"></i> Monitoring Hilir Logistik & Presensi Makan | <?= date('d F Y'); ?></p>
                </div>
                <i class="bi bi-building-check display-1 opacity-25 position-absolute end-0 bottom-0 me-3 mb-n2"></i>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-2">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1 text-uppercase tracking-wider fw-bold">Kuota Siswa Aktif</small>
                        <h3 class="fw-black mb-0 text-dark"><?= number_format($data_sekolah['jml_siswa'] ?? 0, 0, ',', '.'); ?></h3>
                        <span class="text-xs text-muted">Batas maksimal kuota distribusi harian</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-3 fs-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-2">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1 text-uppercase tracking-wider fw-bold">Porsi Manifest Dapur</small>
                        <h3 class="fw-black mb-0 text-primary"><?= isset($data_logistik['jml_porsi']) ? number_format($data_logistik['jml_porsi'], 0, ',', '.') . ' <span class="fs-6 fw-normal text-muted">Boks</span>' : '-'; ?></h3>
                        <span class="text-xs text-muted">Kuantitas dikirim armada kurir</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-3">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-2">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1 text-uppercase tracking-wider fw-bold">Porsi Layak Konsumsi</small>
                        <h3 class="fw-black mb-0 text-success"><?= isset($data_logistik['jml_porsi_diterima']) ? number_format($data_logistik['jml_porsi_diterima'], 0, ',', '.') . ' <span class="fs-6 fw-normal text-muted">Boks</span>' : '-'; ?></h3>
                        <span class="text-xs text-muted">Terverifikasi bebas rusak/tumpah</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-3 fs-3">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-2">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1 text-uppercase tracking-wider fw-bold">Siswa Sudah Makan</small>
                        <h3 class="fw-black mb-0 text-warning"><?= number_format($total_siswa_sudah_makan, 0, ',', '.'); ?></h3>
                        <span class="text-xs text-muted">Akumulasi ketukan presensi kelas</span>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-3 fs-3">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-truck text-primary me-2"></i>Status Pelacakan Kurir Logistik</h5>
                </div>
                <div class="card-body pt-1">
                    <?php if (!$data_logistik): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x display-4 text-black-50 mb-3 d-block"></i>
                            <h6 class="fw-bold text-dark">Belum Ada Pengiriman Hari Ini</h6>
                            <p class="small text-muted mb-0">Armada dapur umum mitra belum membuat manifes jalan pengiriman makanan untuk instansi Anda.</p>
                        </div>
                    <?php else: 
                        $status_aktif = $data_logistik['status'];
                    ?>
                        <div class="bg-light p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block text-xs">Asal Dapur Produksi</small>
                                <strong class="text-dark"><i class="bi bi-egg-fried text-warning me-1"></i><?= htmlspecialchars($data_logistik['nama_dapur']); ?></strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block text-xs">Waktu Keberangkatan</small>
                                <strong class="text-dark"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($data_logistik['waktu_kirim'])); ?> WIB</strong>
                            </div>
                        </div>

                        <div class="position-relative my-4 py-2 px-4">
                            <div class="progress position-absolute top-50 start-0 translate-middle-y w-100 mx-4" style="height: 4px; z-index: 1; max-width: calc(100% - 48px);">
                                <div class="progress-bar <?= ($status_aktif === 'Tiba') ? 'bg-success w-100' : 'bg-primary w-50 progress-bar-striped progress-bar-animated'; ?>" role="progressbar"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
                                <div class="text-center bg-white px-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center border border-4 border-white shadow-sm mb-2 mx-auto" style="width: 45px; height: 45px;">
                                        <i class="bi bi-box-seam fs-5"></i>
                                    </div>
                                    <span class="d-block fw-bold small text-dark">Dapur Dilepas</span>
                                    <small class="text-muted text-xs"><?= date('H:i', strtotime($data_logistik['waktu_kirim'])); ?></small>
                                </div>

                                <div class="text-center bg-white px-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center border border-4 border-white shadow-sm mb-2 mx-auto <?= ($status_aktif === 'Proses') ? 'bg-primary text-white animated-pulse' : 'bg-success text-white'; ?>" style="width: 45px; height: 45px;">
                                        <i class="bi bi-truck fs-5"></i>
                                    </div>
                                    <span class="d-block fw-bold small text-dark">Dalam Perjalanan</span>
                                    <small class="text-muted text-xs"><?= ($status_aktif === 'Proses') ? 'Kurir Menuju Lokasi' : 'Selesai'; ?></small>
                                </div>

                                <div class="text-center bg-white px-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center border border-4 border-white shadow-sm mb-2 mx-auto <?= ($status_aktif === 'Tiba') ? 'bg-success text-white' : 'bg-light text-secondary'; ?>" style="width: 45px; height: 45px;">
                                        <i class="bi bi-house-check fs-5"></i>
                                    </div>
                                    <span class="d-block fw-bold small <?= ($status_aktif === 'Tiba') ? 'text-success' : 'text-muted'; ?>">Tiba di Sekolah</span>
                                    <small class="text-xs <?= ($status_aktif === 'Tiba') ? 'text-success fw-bold' : 'text-muted'; ?>"><?= ($status_aktif === 'Tiba') ? 'Terverifikasi ✓' : 'Menunggu'; ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-4 text-center">
                            <?php if ($status_aktif === 'Proses'): ?>
                                <div class="alert alert-info border-0 p-3 mb-3 text-start small">
                                    <i class="bi bi-info-circle-fill me-2 fs-5 align-middle"></i>
                                    Logistik boks makanan sedang dimobilisasi. Segera lakukan <strong>Verifikasi Penerimaan</strong> sesampainya logistik fisik di gerbang sekolah.
                                </div>
                                <a href="/mbg-app/views/sekolah/verifikasi.php" class="btn btn-primary rounded-3 px-4 fw-bold">
                                    <i class="bi bi-check2-circle me-1"></i> Konfirmasi & Verifikasi Kedatangan
                                </a>
                            <?php else: ?>
                                <div class="alert alert-success border-0 p-3 mb-0 text-start small text-success">
                                    <i class="bi bi-patch-check-fill me-2 fs-5 align-middle"></i>
                                    Manifes kiriman telah terkunci permanen. Lembar kehadiran makan mandiri siswa saat ini sudah aktif dan dibuka sepenuhnya.
                                </div>
                                <div class="mt-3">
                                    <a href="/mbg-app/views/sekolah/presensi.php" class="btn btn-outline-dark rounded-3 px-4 fw-bold shadow-sm me-2">
                                        <i class="bi bi-fingerprint me-1"></i> Buka Checklist Presensi Kelas
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-light-subtle">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-journal-text text-warning me-2"></i>Komposisi Menu Nutrisi Hari Ini</h5>
                </div>
                <div class="card-body pt-1">
                    <?php if (!$data_logistik): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-egg text-black-50 fs-1 mb-2 d-block"></i>
                            <p class="small mb-0">Rincian komposisi gizi diet harian belum dirilis.</p>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-white border rounded-3 shadow-2xs">
                            <span class="badge bg-warning text-dark mb-2 fw-bold"><i class="bi bi-lightning-fill me-1"></i><?= $data_logistik['kalori']; ?> Kcal (Target Kalori)</span>
                            <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($data_logistik['nama_menu']); ?></h4>
                            
                            <small class="text-uppercase tracking-wider text-muted text-xs fw-bold d-block mb-2">Bahan Baku Utama Terkandung:</small>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tbody>
                                        <?php
                                        $query_detail_menu = "SELECT b.nama_bahan, dm.jumlah_takaran, b.satuan 
                                                              FROM detail_menu dm
                                                              JOIN bahan_baku b ON dm.id_bahan = b.id_bahan
                                                              WHERE dm.id_menu = ?";
                                        $stmt_det = $conn->prepare($query_detail_menu);
                                        $stmt_det->bind_param("s", $data_logistik['id_menu']);
                                        $stmt_det->execute();
                                        $res_det = $stmt_det->get_result();
                                        
                                        if ($res_det->num_rows === 0) {
                                            echo '<tr><td class="text-muted italic">Takaran gramasi per porsi belum diinput.</td></tr>';
                                        } else {
                                            while ($bahan = $res_det->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td class="text-dark fw-medium"><i class="bi bi-dot text-primary me-1"></i>' . htmlspecialchars($bahan['nama_bahan']) . '</td>';
                                                echo '<td class="text-end text-muted fw-bold">' . number_format($bahan['jumlah_takaran'], 2, ',', '.') . ' ' . $bahan['satuan'] . ' <span class="fw-normal text-xs">/ porsi</span></td>';
                                                echo '</tr>';
                                            }
                                        }
                                        $stmt_det->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-banner { min-height: 120px; }
.tracking-wider { letter-spacing: 0.05em; }
.fw-black { font-weight: 900 !important; }
.text-xs { font-size: 0.75rem; }
.border-white-25 { border-color: rgba(255, 255, 255, 0.25) !important; }
.shadow-2xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

/* Animasi pulse visual khusus penanda status pengiriman proses harian */
@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.5); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
}
.animated-pulse { animation: pulse 2s infinite; }
</style>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>