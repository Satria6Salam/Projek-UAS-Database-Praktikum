<?php
// =========================================================================
// HEADER, SECURITY AUTHENTICATION & DATA FETCHING
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Guardrail Hak Akses: Hanya untuk Aktor Sekolah
$id_sekolah_aktif = $_SESSION['id_sekolah'] ?? null;
$role_aktif = $_SESSION['role'] ?? '';

if (!$id_sekolah_aktif || $role_aktif !== 'Sekolah') {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-shield-lock-fill me-2'></i>Akses Ditolak: Khusus Verifikator Lapangan Pihak Sekolah.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Ambil data manifes pengiriman harian berjalan yang berstatus 'Proses'
$query_aktif = "SELECT p.*, d.nama_dapur, m.nama_menu, m.kalori 
                FROM pengiriman p
                JOIN dapur_umum d ON p.id_dapur = d.id_dapur
                JOIN menu m ON p.id_menu = m.id_menu
                WHERE p.id_sekolah = ? AND p.status = 'Proses' 
                ORDER BY p.waktu_kirim DESC";
$stmt = $conn->prepare($query_aktif);
$stmt->bind_param("s", $id_sekolah_aktif);
$stmt->execute();
$res_aktif = $stmt->get_result();
?>

<div class="container-fluid px-3 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-primary">
                <h2 class="h4 mb-1 text-dark fw-bold">
                    <i class="bi bi-truck-flatbed me-2 text-primary"></i>Verifikasi Logistik Masuk
                </h2>
                <p class="text-muted small mb-0">
                    Konfirmasikan fisik boks makanan harian, periksa kelayakan konsumsi, dan laporkan kerusakan demi mitigasi tumpah di jalan.
                </p>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="bi bi-hourglass-split text-warning me-2"></i>Antrean Manifes Distribusi Dalam Perjalanan
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle m-0 table-hover">
                            <thead class="table-light text-secondary text-xs text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">ID Jalan</th>
                                    <th class="py-3">Dapur Pengirim</th>
                                    <th class="py-3">Menu Makan Hari Ini</th>
                                    <th class="py-3 text-center">Porsi Berangkat</th>
                                    <th class="py-3 text-center">Waktu Kirim</th>
                                    <th class="pe-4 py-3 text-end">Aksi Validasi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php if ($res_aktif->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-scooter fs-2 mb-2 d-block text-black-50"></i>
                                            Tidak ada armada kurir pengiriman aktif menuju sekolah Anda saat ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = $res_aktif->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-secondary">#KRM-<?= str_pad($row['id_kirim'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_dapur']); ?></td>
                                            <td>
                                                <span class="fw-medium d-block text-dark"><?= htmlspecialchars($row['nama_menu']); ?></span>
                                                <span class="text-xs text-muted"><i class="bi bi-fire text-danger me-0.5"></i> <?= $row['kalori']; ?> Kcal</span>
                                            </td>
                                            <td class="text-center fw-bold text-primary fs-6"><?= number_format($row['jml_porsi']); ?> <span class="fs-6 text-muted fw-normal">Boks</span></td>
                                            <td class="text-center text-secondary"><?= date('d/m/Y H:i', strtotime($row['waktu_kirim'])); ?></td>
                                            <td class="pe-4 text-end">
                                                <button type="button" class="btn btn-sm btn-primary fw-medium px-3 shadow-sm btn-verifikasi" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalVerifikasi"
                                                        data-id="<?= $row['id_kirim']; ?>" 
                                                        data-porsi="<?= $row['jml_porsi']; ?>"
                                                        data-menu="<?= htmlspecialchars($row['nama_menu']); ?>">
                                                    <i class="bi bi-box-seam-fill me-1"></i>Konfirmasi Tiba
                                                </button>
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

<div class="modal fade" id="modalVerifikasi" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="/mbg-app/actions/sekolah/terima_kiriman.php" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_kirim" id="modal-id-kirim">
            <input type="hidden" id="modal-porsi-awal">

            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-patch-check-fill text-primary me-2"></i>Form Konfirmasi Kedatangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="auto" data-bs-with-disabled="false" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body py-4">
                <div class="bg-primary-subtle p-3 rounded mb-3 border border-primary-subtle">
                    <span class="text-xs text-uppercase text-secondary d-block fw-semibold">Menu yang Diterima</span>
                    <span id="modal-nama-menu" class="fw-bold text-dark fs-6">-</span>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-medium">Porsi Dikirim Dapur</label>
                        <input type="text" id="modal-display-porsi" class="form-control bg-light fw-bold border-0 fs-5 text-center" readonly>
                    </div>
                    <div class="col-6">
                        <label for="input-rusak" class="form-label text-muted small fw-semibold text-danger">Jumlah Rusak / Tumpah</label>
                        <div class="input-group">
                            <input type="number" name="jml_rusak" id="input-rusak" class="form-control fw-bold border-danger text-center fs-5" min="0" value="0" required>
                            <span class="input-group-text bg-danger-subtle text-danger border-danger fw-medium text-xs">Boks</span>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-secondary opacity-25">

                <div class="p-3 bg-light rounded text-center">
                    <span class="text-xs text-uppercase text-muted d-block fw-semibold">Total Porsi Aman Layak Konsumsi</span>
                    <h3 class="fw-black text-success m-0" id="display-porsi-bersih">0 <span class="fs-6 text-muted fw-normal">Boks</span></h3>
                    <input type="hidden" name="jml_porsi_diterima" id="input-porsi-diterima">
                </div>
                <div id="error-validation-msg" class="text-danger text-center small mt-2 d-none fw-semibold">
                    <i class="bi bi-x-circle-fill me-1"></i>Kuantitas porsi rusak tidak boleh melebihi porsi awal!
                </div>
            </div>

            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary text-xs fw-medium px-3" data-bs-with-disabled="false" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btn-submit-verifikasi" class="btn btn-primary text-xs fw-semibold px-4 shadow-sm">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>Simpan Log Penerimaan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.text-xs { font-size: 0.75rem; }
.bg-primary-subtle { background-color: #cfe2ff !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.fw-black { font-weight: 900; }
</style>

<?php
$stmt->close();
$conn->close();
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>

