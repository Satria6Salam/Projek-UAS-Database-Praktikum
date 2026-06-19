<?php
// =========================================================================
// UI INTERFACE: SUPPLIER INBOUND LOGISTICS RECORDING FORM
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Modul Hulu (Multi-Actor Guardrail)
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

$id_supplier_aktif = $_SESSION['id_supplier'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_supplier_aktif || $role_aktif !== 'Supplier') {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-shield-lock-fill me-2'></i>Akses Ditolak: Modul ini hanya diperuntukkan bagi entitas Vendor/Supplier sah.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Fetch 1: Mengambil Unit Dapur Umum Mitra yang terelasi di dalam sistem
$query_dapur = "SELECT id_dapur, nama_dapur, lokasi FROM dapur_umum ORDER BY nama_dapur ASC";
$res_dapur = $conn->query($query_dapur);

// Fetch 2: Mengambil Master Data Komoditas Pangan Global
$query_bahan = "SELECT id_bahan, nama_bahan, satuan FROM bahan_baku ORDER BY nama_bahan ASC";
$res_bahan = $conn->query($query_bahan);

// Fetch 3: Mengambil Data Histori Log Records Pasokan Terkini Khusus Vendor Login (Audit Internal)
$query_history = "SELECT pb.*, d.nama_dapur, b.nama_bahan, b.satuan 
                  FROM pasokan_bahan pb
                  JOIN dapur_umum d ON pb.id_dapur = d.id_dapur
                  JOIN bahan_baku b ON pb.id_bahan = b.id_bahan
                  WHERE pb.id_supplier = ? 
                  ORDER BY pb.tgl_masuk DESC, pb.id_pasokan DESC LIMIT 10";
$stmt_hist = $conn->prepare($query_history);
$stmt_hist->bind_param("s", $id_supplier_aktif);
$stmt_hist->execute();
$res_history = $stmt_hist->get_result();
?>

<div class="container-fluid px-2 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-box-seam-fill shadow-sm p-2 rounded bg-light text-success me-2"></i>Pencatatan Nota Pasokan Komoditas
            </h2>
            <p class="text-muted small mb-0">Rantai Pasok Hulu: Distribusikan bahan pangan mentah segar langsung menuju hanggar gudang unit dapur umum mitra penerima.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 small d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4 small d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-medical me-2 text-primary"></i>Penerbitan Nota Baru</h6>
                </div>
                <div class="card-body pt-0">
                    <form action="/mbg-app/actions/supplier/pasok_bahan.php" method="POST" id="formSuplaiLogistik">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Dapur Umum Mitra Penerima</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                <select class="form-select" name="id_dapur" required>
                                    <option value="">-- Pilih Dapur Mitra --</option>
                                    <?php while ($dp = $res_dapur->fetch_assoc()): ?>
                                        <option value="<?= $dp['id_dapur']; ?>"><?= htmlspecialchars($dp['nama_dapur']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Komoditas Bahan Baku</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-egg-fried"></i></span>
                                <select class="form-select" name="id_bahan" id="selectBahan" required>
                                    <option value="" data-satuan="-">-- Pilih Bahan Pangan --</option>
                                    <?php while ($bh = $res_bahan->fetch_assoc()): ?>
                                        <option value="<?= $bh['id_bahan']; ?>" data-satuan="<?= $bh['satuan']; ?>">
                                            <?= htmlspecialchars($bh['nama_bahan']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Volume Kuantitas Dikirim</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-speedometer2"></i></span>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="jumlah" placeholder="Contoh: 250.50" required>
                                <span class="input-group-text bg-light text-dark fw-bold" id="labelSatuan">-</span>
                            </div>
                            <div class="form-text text-xs text-muted"><i class="bi bi-info-circle me-1"></i>Mendukung angka desimal pecahan presisi ganda.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Penyerahan Logistik</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" class="form-statement form-control" name="tgl_masuk" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-sm btn-success w-100 fw-medium shadow-sm py-2" id="btnSubmitSuplai">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Kunci & Kirim Nota Pasokan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>10 Riwayat Pengiriman Terkini (Audit Record)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle m-0">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-2.5">ID Nota</th>
                                    <th class="py-2.5">Dapur Tujuan</th>
                                    <th class="py-2.5">Komoditas Bahan</th>
                                    <th class="py-2.5 text-end">Volume Pasokan</th>
                                    <th class="py-2.5 text-center">Tanggal Masuk</th>
                                    <th class="text-center pe-4 py-2.5">Status Gerbang</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php if ($res_history->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-clipboard-x fs-2 mb-2 d-block text-black-50"></i>
                                            Belum ada berkas nota pasokan yang diterbitkan untuk saat ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = $res_history->fetch_assoc()): ?>
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
                                                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 fw-semibold">
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
.text-xs { font-size: 0.75rem; }
.bg-success-subtle { background-color: #d1e7dd !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
</style>


<script>
document.addEventListener("DOMContentLoaded", function() {
    const selectBahan = document.getElementById('selectBahan');
    const labelSatuan = document.getElementById('labelSatuan');
    
    if(selectBahan && labelSatuan) {
        selectBahan.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const satuan = selectedOption.getAttribute('data-satuan') || '-';
            labelSatuan.textContent = satuan;
        });
    }
});
</script>

<?php
$stmt_hist->close();
$conn->close();
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>