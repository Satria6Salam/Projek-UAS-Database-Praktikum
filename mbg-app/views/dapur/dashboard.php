<?php
// =========================================================================
// HEADER & SECURITY AUTHENTICATION VALIDATION
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/fungsi_stok.php';

// Validasi Hak Akses Tunggal (Single Role Restriction)
$id_dapur_aktif = $_SESSION['id_dapur'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_dapur_aktif || $role_aktif !== 'Dapur') {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-shield-lock-fill me-2'></i>Akses Ditolak: Otoritas Khusus Petugas Dapur Umum.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// =========================================================================
// DATA FETCHING BLOCK
// =========================================================================

// Profil Fisik Unit Dapur
$query_dapur = "SELECT nama_dapur, lokasi, kapasitas FROM dapur_umum WHERE id_dapur = ? LIMIT 1";
$stmt_dp = $conn->prepare($query_dapur);
$stmt_dp->bind_param("s", $id_dapur_aktif);
$stmt_dp->execute();
$dapur_info = $stmt_dp->get_result()->fetch_assoc();
$stmt_dp->close();

// Hitung Kuantitas Manifes Distribusi Hari Ini (Dalam Proses vs Tiba)
$q_status = "SELECT 
                SUM(CASE WHEN status = 'Proses' THEN jml_porsi ELSE 0 END) as porsi_proses,
                SUM(CASE WHEN status = 'Tiba' THEN jml_porsi_diterima ELSE 0 END) as porsi_tiba
             FROM pengiriman 
             WHERE id_dapur = ? AND DATE(waktu_kirim) = CURRENT_DATE";
$stmt_st = $conn->prepare($q_status);
$stmt_st->bind_param("s", $id_dapur_aktif);
$stmt_st->execute();
$res_status = $stmt_st->get_result()->fetch_assoc();
$porsi_proses = (int)($res_status['porsi_proses'] ?? 0);
$porsi_tiba = (int)($res_status['porsi_tiba'] ?? 0);
$stmt_st->close();

// List Seluruh Bahan Baku Global untuk Kalkulasi Dinamis
$query_bahan = "SELECT id_bahan, nama_bahan, satuan, stok_min FROM bahan_baku ORDER BY nama_bahan ASC";
$res_bahan = $conn->query($query_bahan);
?>

<div class="container-fluid px-3 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-primary">
                <h2 class="h4 mb-1 text-dark fw-bold">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Panel Kendali Dapur Umum
                </h2>
                <p class="text-muted small mb-0">
                    Unit: <span class="fw-bold text-dark"><?= htmlspecialchars($dapur_info['nama_dapur'] ?? 'Tidak Terdaftar'); ?></span> 
                    | Batas Memasak Harian: <span class="badge bg-dark"><?= number_format($dapur_info['kapasitas'] ?? 0); ?> Porsi</span>
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body d-flex flex-column align-items-center justify-content-center position-relative py-4">
                    <h6 class="text-muted small text-uppercase fw-bold mb-3"><i class="bi bi-pie-chart-fill me-1"></i>Rasio Distribusi Makro</h6>
                    
                    <div style="height: 160px; width: 160px;" class="position-relative">
                        <canvas id="macroDistributionChart" data-proses="<?= $porsi_proses; ?>" data-tiba="<?= $porsi_tiba; ?>"></canvas>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mt-3 w-100 px-2 small">
                        <div class="text-center">
                            <span class="d-block text-xs text-muted"><i class="bi bi-circle-fill text-primary me-1"></i>Proses</span>
                            <span class="fw-bold" id="txt-porsi-proses"><?= number_format($porsi_proses); ?></span>
                        </div>
                        <div class="text-center">
                            <span class="d-block text-xs text-muted"><i class="bi bi-circle-fill text-success me-1"></i>Tiba</span>
                            <span class="fw-bold" id="txt-porsi-tiba"><?= number_format($porsi_tiba); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history text-secondary me-2"></i>Aktivitas Manifes Distribusi Logistik Berjalan</h6>
                    <span class="badge bg-light text-secondary border px-2 py-1 small fw-normal">
                        <span class="spinner-grow spinner-grow-sm text-success me-1" style="width: 8px; height: 8px;"></span> Live
                    </span>
                </div>
                
                <div class="card-body p-0" id="realtime-log-container">
                    <div class="table-responsive" style="max-height: 220px;">
                        <table class="table align-middle m-0 table-hover">
                            <thead class="table-light text-secondary text-xs text-uppercase">
                                <tr>
                                    <th class="ps-3 py-2">Sekolah Penerima</th>
                                    <th class="py-2">Menu Olahan</th>
                                    <th class="py-2 text-center">Porsi Kirim</th>
                                    <th class="py-2 text-center">Porsi Tiba</th>
                                    <th class="pe-3 py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $q_log = "SELECT p.*, s.nama_sekolah, m.nama_menu 
                                          FROM pengiriman p 
                                          JOIN sekolah s ON p.id_sekolah = s.id_sekolah 
                                          JOIN menu m ON p.id_menu = m.id_menu 
                                          WHERE p.id_dapur = ? AND DATE(p.waktu_kirim) = CURRENT_DATE 
                                          ORDER BY p.id_kirim DESC";
                                $stmt_l = $conn->prepare($q_log);
                                $stmt_l->bind_param("s", $id_dapur_aktif);
                                $stmt_l->execute();
                                $res_log = $stmt_l->get_result();

                                if ($res_log->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada pengiriman logistik boks makanan hari ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($log = $res_log->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold"><?= htmlspecialchars($log['nama_sekolah']); ?></td>
                                            <td><?= htmlspecialchars($log['nama_menu']); ?></td>
                                            <td class="text-center fw-bold"><?= number_format($log['jml_porsi']); ?></td>
                                            <td class="text-center fw-bold text-success"><?= $log['jml_porsi_diterima'] !== null ? number_format($log['jml_porsi_diterima']) : '-'; ?></td>
                                            <td class="pe-3 text-center">
                                                <?php if ($log['status'] === 'Proses'): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill text-xs"><i class="bi bi-truck me-1"></i>Kurir Jalan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill text-xs"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; $stmt_l->close(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2 shadow-sm p-1 rounded bg-danger-subtle pulse-alert"></i>
                        Indikator Ambang Batas Minimum Bahan (Alert Flags)
                    </h6>
                </div>
                <div class="card-body pt-0" style="max-height: 320px; overflow-y: auto;">
                    <?php 
                    $flag_triggered = false;
                    if ($res_bahan && $res_bahan->num_rows > 0) {
                        $res_bahan->data_seek(0);
                        while ($b = $res_bahan->fetch_assoc()) {
                            // Menjalankan Rumus Aturan Bisnis Poin 3 via Helper fungsi_stok.php
                            $stok_riil = hitungStokRiil($conn, $id_dapur_aktif, $b['id_bahan']);
                            
                            // Peringatan dipicu jika Stok Riil berada di bawah atau sama dengan nilai stok_min
                            if ($stok_riil <= $b['stok_min']) {
                                $flag_triggered = true;
                                ?>
                                <div class="alert alert-danger border-0 shadow-sm mb-2 d-flex align-items-center justify-content-between py-2 px-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-envelope-exclamation-fill text-danger fs-5 me-2.5"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark small"><?= htmlspecialchars($b['nama_bahan']); ?></h6>
                                            <span class="text-xs text-secondary">
                                                Stok Aktif: <span class="fw-bold text-danger"><?= number_format($stok_riil, 2, ',', '.'); ?></span> / Min: <?= $b['stok_min'] . ' ' . $b['satuan']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="badge bg-danger rounded-pill fw-bold text-xs px-2 py-1">RESTOCK!</span>
                                </div>
                                <?php
                            }
                        }
                    }
                    if (!$flag_triggered): ?>
                        <div class="text-center text-muted py-5 border rounded border-dashed">
                            <i class="bi bi-shield-check text-success fs-1 mb-2 d-block"></i>
                            <span class="small fw-medium text-secondary">Logistik Aman: Seluruh volume bahan baku di atas ambang batas minimum.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-box-seam text-secondary me-2"></i>Kalkulator Live Monitoring Saldo Gudang Aktual</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 320px;">
                        <table class="table align-middle m-0 table-hover">
                            <thead class="table-light text-secondary text-xs text-uppercase">
                                <tr>
                                    <th class="ps-4 py-2">Kode</th>
                                    <th class="py-2">Nama Komoditas</th>
                                    <th class="py-2 text-center">Batas Min</th>
                                    <th class="py-2 text-end pe-4">Stok Riil Saat Ini</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php 
                                if (!$res_bahan || $res_bahan->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Data master komponen logistik dasar tidak ditemukan.</td>
                                    </tr>
                                <?php else: 
                                    $res_bahan->data_seek(0);
                                    while ($row = $res_bahan->fetch_assoc()): 
                                        $real_volume = hitungStokRiil($conn, $id_dapur_aktif, $row['id_bahan']);
                                        $is_warning = ($real_volume <= $row['stok_min']);
                                        ?>
                                        <tr class="<?= $is_warning ? 'table-danger-indicator' : ''; ?>">
                                            <td class="ps-4 fw-bold text-secondary"><?= $row['id_bahan']; ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_bahan']); ?></td>
                                            <td class="text-center text-muted"><?= $row['stok_min']; ?> <?= $row['satuan']; ?></td>
                                            <td class="text-end pe-4 fw-bold <?= $is_warning ? 'text-danger' : 'text-success'; ?>">
                                                <?= number_format($real_volume, 2, ',', '.'); ?> <span class="text-muted fw-normal text-xs"><?= $row['satuan']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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
.text-xs { font-size: 0.75rem; }
.bg-success-subtle { background-color: #d1e7dd !important; }
.bg-primary-subtle { background-color: #cfe2ff !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.border-dashed { border-style: dashed !important; }
.table-danger-indicator { background-color: #fff4f4 !important; }
.table-danger-indicator:hover { background-color: #ffe8e8 !important; }
.pulse-alert { animation: alertFlashing 1.5s infinite; }
@keyframes alertFlashing {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.06); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/mbg-app/assets/js/realtime_dashboard.js"></script>

<?php
$conn->close();
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>