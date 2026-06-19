<?php
// =========================================================================
// UI INTERFACE: SUPPLIER HISTORICAL SUPPLY & AUDIT RECORD DASHBOARD
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Multi-Actor Hak Akses Guardrail
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

$id_supplier_aktif = $_SESSION['id_supplier'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_supplier_aktif || $role_aktif !== 'Supplier') {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-shield-lock-fill me-2'></i>Akses Ditolak: Hak akses dibatasi hanya untuk aktor Supplier.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Fetch 1: Mengambil Data Profil Vendor/Badan Usaha Lokal
$query_vendor = "SELECT nama_vendor, komoditas, no_telp FROM supplier WHERE id_supplier = ? LIMIT 1";
$stmt_vd = $conn->prepare($query_vendor);
$stmt_vd->bind_param("s", $id_supplier_aktif);
$stmt_vd->execute();
$vendor_info = $stmt_vd->get_result()->fetch_assoc();
$stmt_vd->close();

// Fetch 2: Menghitung Akumulasi Volume Total Tonase Pasokan yang Telah Disetujui Dapur (Disetujui)
$query_total = "SELECT SUM(jumlah) as total_volume FROM pasokan_bahan WHERE id_supplier = ? AND status = 'Disetujui'";
$stmt_tot = $conn->prepare($query_total);
$stmt_tot->bind_param("s", $id_supplier_aktif);
$stmt_tot->execute();
$total_volume = $stmt_tot->get_result()->fetch_assoc()['total_volume'] ?? 0.00;
$stmt_tot->close();

// Fetch 3: Menarik Seluruh Riwayat Rekam Transaksi Historis Pasokan Bahan Pangan Vendor
$query_log = "SELECT pb.*, d.nama_dapur, b.nama_bahan, b.satuan 
              FROM pasokan_bahan pb
              JOIN dapur_umum d ON pb.id_dapur = d.id_dapur
              JOIN bahan_baku b ON pb.id_bahan = b.id_bahan
              WHERE pb.id_supplier = ? 
              ORDER BY pb.tgl_masuk DESC, pb.id_pasokan DESC";
$stmt_log = $conn->prepare($query_log);
$stmt_log->bind_param("s", $id_supplier_aktif);
$stmt_log->execute();
$res_log = $stmt_log->get_result();
?>

<div class="container-fluid px-2 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-success">
                <h2 class="h4 mb-1 text-dark fw-bold">
                    <i class="bi bi-grid-1x2-fill me-2 text-success"></i>Dashboard Vendor Logistik
                </h2>
                <p class="text-muted small mb-0">
                    Nama Perusahaan: <span class="fw-bold text-dark"><?= htmlspecialchars($vendor_info['nama_vendor'] ?? 'Vendor Tidak Terdaftar'); ?></span> 
                    | Komoditas Utama: <span class="badge bg-success-subtle text-success border border-success-subtle"><?= htmlspecialchars($vendor_info['komoditas'] ?? '-'); ?></span> 
                    | Hotline Telp: <span class="text-secondary"><?= htmlspecialchars($vendor_info['no_telp'] ?? '-'); ?></span>
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted small text-uppercase mb-1 fw-semibold">Volume Terdistribusi Sah</h6>
                        <h3 class="fw-bold mb-0 text-dark">
                            <span id="widget-total-pasokan"><?= number_format($total_volume, 2, ',', '.'); ?></span>
                            <span class="fs-6 text-muted fw-normal">Satuan Baku</span>
                        </h3>
                    </div>
                    <div class="bg-success-subtle p-3 rounded-circle text-success">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 small text-muted">
                    <i class="bi bi-info-circle me-1"></i>Hanya menghitung transaksi berstatus 'Disetujui'.
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 h-100 justify-content-between">
                <div class="d-flex align-items-stretch">
                    <div class="bg-primary-subtle p-3 rounded text-primary d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-file-earmark-plus-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-dark fw-bold mb-0 small">Kirim Logistik Baru</h6>
                        <p class="text-muted text-xs mb-0">Input nota transaksi pengiriman bahan pangan mentah ke gudang dapur mitra.</p>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="/mbg-app/views/supplier/form_pasok.php" class="btn btn-xs btn-primary w-100 fw-medium text-xs py-1.5 shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i>Buka Formulir Digital
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="bi bi-journal-text me-2 text-secondary"></i>Log Records Transaksi Pasokan Historis Vendor
                    </h6>
                    <span class="badge bg-light text-secondary border small fw-normal d-flex align-items-center py-1.5 px-2">
                        <span class="spinner-grow spinner-grow-sm text-success me-2" role="status" style="width: 8px; height: 8px;"></span> Live Interaktif
                    </span>
                </div>
                
                <div class="card-body p-0 rounded-bottom" id="realtime-supplier-container">
                    <div class="table-responsive">
                        <table class="table align-middle m-0 table-hover">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-2.5">ID Nota</th>
                                    <th class="py-2.5">Dapur Tujuan</th>
                                    <th class="py-2.5">Komoditas Bahan</th>
                                    <th class="py-2.5 text-end">Volume Pasokan</th>
                                    <th class="py-2.5 text-center">Tanggal Logistik</th>
                                    <th class="text-center pe-4 py-2.5">Status Gerbang Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php if ($res_log->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-folder-x fs-2 mb-2 d-block text-black-50"></i>
                                            Belum ada riwayat aktivitas transaksi pasokan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = $res_log->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-secondary">#NOT-<?= str_pad($row['id_pasokan'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_dapur']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_bahan']); ?></td>
                                            <td class="text-end fw-bold text-dark">
                                                <?= number_format($row['jumlah'], 2, ',', '.'); ?> <span class="text-muted fw-normal small"><?= $row['satuan']; ?></span>
                                            </td>
                                            <td class="text-center text-secondary"><?= date('d/m/Y', strtotime($row['tgl_masuk'])); ?></td>
                                            <td class="text-center pe-4">
                                                <?php if ($row['status'] === 'Pending'): ?>
                                                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 fw-semibold animate-pulse-badge">
                                                        <i class="bi bi-hourglass-split me-1"></i>Pending
                                                    </span>
                                                <?php elseif ($row['status'] === 'Disetujui'): ?>
                                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fw-semibold">
                                                        <i class="bi bi-patch-check-fill me-1"></i>Disetujui
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fw-semibold">
                                                        <i class="bi bi-x-octagon-fill me-1"></i>Ditolak
                                                    </span>
                                                <?php endif; ?>
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
/* SEKTOR STYLE ISOLASI SUPPLIER */
.text-xs { font-size: 0.75rem; }
.btn-xs { padding: 0.25rem 0.4rem; font-size: 0.75rem; border-radius: 0.2rem; }
.bg-success-subtle { background-color: #d1e7dd !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.bg-primary-subtle { background-color: #cfe2ff !important; }
.animate-pulse-badge { animation: pulseWarning 2s infinite; }
@keyframes pulseWarning {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}
</style>

<script src="/mbg-app/assets/js/realtime_dashboard.js"></script>

<?php
$stmt_log->close();
$conn->close();
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>