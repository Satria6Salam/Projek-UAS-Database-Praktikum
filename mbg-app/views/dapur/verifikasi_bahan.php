<?php
// =========================================================================
// UI INTERFACE: SUPPLIER SUPPLY QUEUE & QUALITY CONTROL VERIFICATION
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Modul Berbasis Hak Akses Multi-Actor (Petugas Dapur Umum)
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Memastikan variabel session id_dapur hasil sinkronisasi login tersedia
$id_dapur_aktif = $_SESSION['id_dapur'] ?? null;

if (!$id_dapur_aktif) {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-exclamation-octagon-fill me-2'></i>Sesi Dapur tidak terdeteksi. Silakan lakukan login ulang.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Mengambil parameter filter tanggal jika diaplikasikan oleh petugas
$filter_tgl = isset($_GET['tgl_masuk']) ? $_GET['tgl_masuk'] : '';

// Menyusun struktur query dasar untuk antrean pasokan spesifik unit dapur terkait
$sql = "SELECT pb.*, sb.nama_vendor, bb.nama_bahan, bb.satuan 
        FROM pasokan_bahan pb
        JOIN supplier sb ON pb.id_supplier = sb.id_supplier
        JOIN bahan_baku bb ON pb.id_bahan = bb.id_bahan
        WHERE pb.id_dapur = ? ";

if (!empty($filter_tgl)) {
    $sql .= "AND pb.tgl_masuk = ? ";
}
$sql .= "ORDER BY pb.status DESC, pb.tgl_masuk DESC, pb.id_pasokan DESC";

$stmt = $conn->prepare($sql);
if (!empty($filter_tgl)) {
    $stmt->bind_param("ss", $id_dapur_aktif, $filter_tgl);
} else {
    $stmt->bind_param("s", $id_dapur_aktif);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-patch-check-fill me-2" style="color: #ea580c;"></i>Verifikasi Logistik Masuk
            </h2>
            <p class="text-muted small mb-0">Kontrol Kualitas Hulu: Periksa kesesuaian fisik komoditas mentah sebelum melakukan approval penambahan stok gudang.</p>
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

    <div class="card border-0 shadow-sm mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-sm-auto">
                    <label class="col-form-label small fw-bold text-secondary px-1">Saring Tanggal Masuk:</label>
                </div>
                <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" class="form-control" name="tgl_masuk" value="<?= htmlspecialchars($filter_tgl); ?>">
                    </div>
                </div>
                <div class="col-sm-auto">
                    <button type="submit" class="btn btn-sm btn-dark px-3 fw-medium" style="background-color: #ea580c; border-color: #ea580c;">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <?php if (!empty($filter_tgl)): ?>
                        <a href="verifikasi_bahan.php" class="btn btn-sm btn-light border px-3 text-secondary fw-medium">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm bg-white">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-hourglass-split me-2 text-secondary"></i>Log Antrean Nota Masuk Logistik</h6>
        </div>
        <div class="card-body p-0 rounded-bottom">
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-2.5">ID Nota</th>
                            <th class="py-2.5">Supplier / Vendor</th>
                            <th class="py-2.5">Komoditas Bahan</th>
                            <th class="py-2.5 text-end">Jumlah Volume</th>
                            <th class="py-2.5 text-center">Tanggal Serah</th>
                            <th class="py-2.5 text-center">Status Verifikasi</th>
                            <th class="text-center pe-4 py-2.5" style="width: 220px;">Aksi Keputusan</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 mb-2 d-block text-black-50"></i>
                                    Tidak ditemukan antrean data pasokan bahan baku pangan untuk saat ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-secondary">#PSK-<?= str_pad($row['id_pasokan'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_vendor']); ?></div>
                                        <span class="text-xs text-muted">ID: <?= htmlspecialchars($row['id_supplier']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_bahan']); ?></div>
                                        <span class="badge bg-light text-secondary border text-xs">ID: <?= htmlspecialchars($row['id_bahan']); ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        <?= number_format($row['jumlah'], 2, ',', '.'); ?> 
                                        <span class="text-muted fw-normal small"><?= htmlspecialchars($row['satuan']); ?></span>
                                    </td>
                                    <td class="text-center text-secondary"><?= date('d/m/Y', strtotime($row['tgl_masuk'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 fw-semibold">
                                                <i class="bi bi-clock-history me-1"></i>Pending
                                            </span>
                                        <?php elseif ($row['status'] === 'Disetujui'): ?>
                                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i>Disetujui
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fw-semibold">
                                                <i class="bi bi-x-circle-fill me-1"></i>Ditolak
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <form action="/mbg-app/actions/dapur/validasi_pasokan.php" method="POST" class="d-inline-block form-konfirmasi-mutasi">
                                                <input type="hidden" name="id_pasokan" value="<?= $row['id_pasokan']; ?>">
                                                
                                                <button type="submit" name="status" value="Disetujui" class="btn btn-xs btn-success fw-medium me-1 btn-aksi-validasi">
                                                    <i class="bi bi-file-earmark-check-fill me-1"></i>Approve
                                                </button>
                                                <button type="submit" name="status" value="Ditolak" class="btn btn-xs btn-outline-danger fw-medium btn-aksi-validasi">
                                                    <i class="bi bi-file-earmark-x-fill me-1"></i>Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-xs btn-light text-muted border border-dashed px-3" disabled title="Data transaksi terkunci permanen">
                                                <i class="bi bi-lock-fill me-1"></i>Terkunci Permanent
                                            </button>
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    var forms = document.querySelectorAll('.form-konfirmasi-mutasi');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var tombolAksi = e.submitter;
            var nilaiKeputusan = tombolAksi.value;
            var teksPeringatan = nilaiKeputusan === 'Disetujui' 
                ? 'Apakah Anda yakin berkas fisik logistik ini LAYAK dan ingin APPROVE pasokan? Tindakan ini akan mengunci transaksi dan menambah stok riil gudang.' 
                : 'Apakah Anda yakin ingin REJECT pasokan bahan baku ini?';
                
            if (!confirm(teksPeringatan)) {
                e.preventDefault();
            }
        });
    });
});
</script>

<style>
/* Custom mini components button styling */
.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}
.text-xs {
    font-size: 0.75rem;
}
.bg-success-subtle { background-color: #d1e7dd !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.border-dashed { border-style: dashed !important; }
</style>

<?php
$stmt->close();
$conn->close();
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>