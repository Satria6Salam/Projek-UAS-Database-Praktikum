<?php
// =========================================================================
// UI INTERFACE: CENTRAL EXECUTIVE SUMMARY CONTROL PANEL (ADMIN DASHBOARD)
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Autentikasi Modul Multi-Actor Admin Dinas
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// -------------------------------------------------------------------------
// COMPONENT: DATABASE METRIC AGGREGATION QUERIES
// -------------------------------------------------------------------------

// 1. Total Sekolah Penerima Manfaat
$q_sekolah = $conn->query("SELECT COUNT(id_sekolah) as total FROM sekolah");
$data_sekolah = $q_sekolah->fetch_assoc();

// 2. Total Siswa Aktif Sasaran Nasional
$q_siswa = $conn->query("SELECT COUNT(nisn) as total FROM siswa");
$data_siswa = $q_siswa->fetch_assoc();

// 3. Total Kapasitas Masak Agregat Dapur Regional
$q_dapur = $conn->query("SELECT COUNT(id_dapur) as total_unit, SUM(kapasitas) as total_kapasitas FROM dapur_umum");
$data_dapur = $q_dapur->fetch_assoc();

// 4. Jumlah Supplier Mitra yang Aktif
$q_supplier = $conn->query("SELECT COUNT(id_supplier) as total FROM supplier");
$data_supplier = $q_supplier->fetch_assoc();

// 5. Status Pengiriman Hari Berjalan (Makro Real-time)
$hari_ini = date('Y-m-d');
$q_proses = $conn->query("SELECT SUM(jml_porsi) as total FROM pengiriman WHERE DATE(waktu_kirim) = '$hari_ini' AND status = 'Proses'");
$porsi_proses = $q_proses->fetch_assoc()['total'] ?? 0;

$q_tiba = $conn->query("SELECT SUM(jml_porsi_diterima) as total FROM pengiriman WHERE DATE(waktu_kirim) = '$hari_ini' AND status = 'Tiba'");
$porsi_tiba = $q_tiba->fetch_assoc()['total'] ?? 0;
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-speedometer2 me-2" style="color: #ea580c;"></i>Pusat Pengawasan Eksekutif Global
            </h2>
            <p class="text-muted small mb-0">Rangkuman data agregat hulu ke hilir serta pemantauan kuota logistik harian program Makan Bergizi Gratis (MBG).</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Sekolah Penerima</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($data_sekolah['total']); ?></h3>
                            <span class="text-xs text-primary small"><i class="bi bi-building-check me-1"></i>Instansi Sasaran</span>
                        </div>
                        <div class="rounded-circle p-3 text-primary" style="background-color: #eff6ff;">
                            <i class="bi bi-mortarboard fs-3 d-flex"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Siswa Aktif</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($data_siswa['total']); ?></h3>
                            <span class="text-xs text-success small"><i class="bi bi-people-fill me-1"></i>Penerima Manfaat</span>
                        </div>
                        <div class="rounded-circle p-3 text-success" style="background-color: #f0fdf4;">
                            <i class="bi bi-person-check fs-3 d-flex"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Kapasitas Produksi</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($data_dapur['total_kapasitas'] ?? 0); ?></h3>
                            <span class="text-xs text-info small"><i class="bi bi-egg-fried me-1"></i><?= $data_dapur['total_unit']; ?> Dapur Regional</span>
                        </div>
                        <div class="rounded-circle p-3 text-info" style="background-color: #ecfeff;">
                            <i class="bi bi-fire fs-3 d-flex"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Supplier Mitra</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($data_supplier['total']); ?></h3>
                            <span class="text-xs text-warning small"><i class="bi bi-patch-check me-1"></i>Vendor Logistik Hulu</span>
                        </div>
                        <div class="rounded-circle p-3 text-warning" style="background-color: #fffbeb;">
                            <i class="bi bi-truck fs-3 d-flex"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5 col-lg-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-pie-chart-fill me-2 text-secondary"></i>Manifestasi Distribusi Hari Ini</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                    <div style="position: relative; height: 220px; width: 220px;">
                        <canvas id="macroDistributionChart" data-proses="<?= $porsi_proses; ?>" data-tiba="<?= $porsi_tiba; ?>"></canvas>
                    </div>
                    <div class="d-flex justify-content-center gap-4 mt-4 w-100 small">
                        <div class="text-center">
                            <span class="badge bg-primary px-2 py-1 mb-1 d-inline-block"><i class="bi bi-box-seam me-1"></i>Proses</span>
                            <div class="fw-bold text-dark" id="txt-porsi-proses"><?= number_format($porsi_proses); ?> Porsi</div>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-success px-2 py-1 mb-1 d-inline-block"><i class="bi bi-check-all me-1"></i>Tiba di Lokasi</span>
                            <div class="fw-bold text-dark" id="txt-porsi-tiba"><?= number_format($porsi_tiba); ?> Porsi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7 col-lg-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-activity me-2 text-secondary"></i>Log Status Pengiriman Aktual</h6>
                    <span class="badge text-white px-2 py-1 small" style="background-color: #ea580c;">
                        <i class="bi bi-broadcast pin-flash me-1"></i>Live Stream
                    </span>
                </div>
                <div class="card-body p-0 rounded-bottom">
                    <div class="table-responsive" style="max-height: 310px; overflow-y: auto;">
                        <table class="table table-hover align-middle m-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase style-sticky">
                                <tr>
                                    <th class="ps-3 py-2.5">Waktu / Tujuan</th>
                                    <th class="py-2.5">Dapur Asal</th>
                                    <th class="py-2.5">Jumlah Kuota</th>
                                    <th class="text-center pe-3 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="small" id="realtime-log-container">
                                <?php
                                $sql_log = "SELECT p.*, d.nama_dapur, s.nama_sekolah 
                                            FROM pengiriman p
                                            JOIN dapur_umum d ON p.id_dapur = d.id_dapur
                                            JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                                            ORDER BY p.waktu_kirim DESC LIMIT 5";
                                $res_log = $conn->query($sql_log);

                                if ($res_log && $res_log->num_rows > 0):
                                    while ($log = $res_log->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($log['nama_sekolah']); ?></div>
                                            <div class="text-muted text-xs" style="font-size: 0.75rem;"><?= date('H:i:s d/M', strtotime($log['waktu_kirim'])); ?></div>
                                        </td>
                                        <td><i class="bi bi-egg-fried text-muted me-1"></i><?= htmlspecialchars($log['nama_dapur']); ?></td>
                                        <td>
                                            <span class="fw-medium text-dark"><?= $log['jml_porsi']; ?> Porsi</span>
                                            <?php if($log['status'] === 'Tiba'): ?>
                                                <div class="text-success text-xs" style="font-size: 0.75rem;"><i class="bi bi-shield-check"></i> Ditrima: <?= $log['jml_porsi_diterima']; ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-3">
                                            <?php if($log['status'] === 'Proses'): ?>
                                                <span class="badge bg-light-primary text-primary px-2 py-1"><i class="bi bi-truck-flatbed me-1"></i>Kurir Jalan</span>
                                            <?php else: ?>
                                                <span class="badge bg-light-success text-success px-2 py-1"><i class="bi bi-house-check-fill me-1"></i>Konfirmasi Tiba</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted bg-light">
                                            <i class="bi bi-box-seam fs-2 d-block mb-2"></i> Belum mendeteksi rekam jejak aktivitas sirkulasi pengiriman hari ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-primary { background-color: #e0f2fe !important; }
    .bg-light-success { background-color: #dcfce7 !important; }
    .style-sticky { position: sticky; top: 0; z-index: 10; }
    .pin-flash { animation: blinker 1.5s linear infinite; }
    @keyframes blinker { 50% { opacity: 0; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>