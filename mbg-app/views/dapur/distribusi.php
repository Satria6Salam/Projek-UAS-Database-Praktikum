<?php
// =========================================================================
// UI INTERFACE: MANIFEST LOGISTICS DISTRIBUTION & SHIPMENT ROUTING OUTBOUND
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Modul Operasional (Petugas Dapur Umum)
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/fungsi_stok.php';

$id_dapur_aktif = $_SESSION['id_dapur'] ?? null;

if (!$id_dapur_aktif) {
    echo "<div class='alert alert-danger m-3'><i class='bi bi-exclamation-octagon-fill me-2'></i>Sesi Dapur tidak valid. Silakan login kembali.</div>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
    exit;
}

// Fetch 1: Mengambil daftar sekolah sasaran yang dipetakan wajib dilayani oleh dapur ini
$query_sekolah = "SELECT id_sekolah, nama_sekolah, jml_siswa FROM sekolah WHERE id_dapur = ? ORDER BY nama_sekolah ASC";
$stmt_sch = $conn->prepare($query_sekolah);
$stmt_sch->bind_param("s", $id_dapur_aktif);
$stmt_sch->execute();
$res_sekolah = $stmt_sch->get_result();

// Fetch 2: Mengambil daftar variasi resep menu digital yang diproduksi oleh dapur ini
$query_menu = "SELECT id_menu, nama_menu, kalori FROM menu WHERE id_dapur = ? ORDER BY nama_menu ASC";
$stmt_men = $conn->prepare($query_menu);
$stmt_men->bind_param("s", $id_dapur_aktif);
$stmt_men->execute();
$res_menu = $stmt_men->get_result();

// Fetch 3: Mengambil data histori manifes pengiriman logistik harian berjalan
$query_kirim = "SELECT p.*, s.nama_sekolah, m.nama_menu 
                FROM pengiriman p
                JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                JOIN menu m ON p.id_menu = m.id_menu
                WHERE p.id_dapur = ? 
                ORDER BY p.waktu_kirim DESC, p.id_kirim DESC";
$stmt_ship = $conn->prepare($query_kirim);
$stmt_ship->bind_param("s", $id_dapur_aktif);
$stmt_ship->execute();
$res_pengiriman = $stmt_ship->get_result();
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-truck shadow-sm p-1 rounded bg-light text-warning me-2"></i>Manifes Distribusi Makanan
            </h2>
            <p class="text-muted small mb-0">Manajemen Logistik Hilir: Terbitkan surat jalan pengiriman boks makanan harian ke instansi sekolah sasaran penerima manfaat.</p>
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
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-plus me-2 text-secondary"></i>Buat Surat Jalan Kurir</h6>
                </div>
                <div class="card-body pt-0">
                    <form action="/mbg-app/actions/dapur/kirim_makanan.php" method="POST" id="formManifestKirim">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Sekolah Tujuan Sasaran</label>
                            <select class="form-select form-select-sm" name="id_sekolah" id="selectSekolah" required>
                                <option value="" data-kuota="0">-- Pilih Sekolah Penerima --</option>
                                <?php while ($sch = $res_sekolah->fetch_assoc()): ?>
                                    <option value="<?= $sch['id_sekolah']; ?>" data-kuota="<?= $sch['jml_siswa']; ?>">
                                        <?= htmlspecialchars($sch['nama_sekolah']); ?> (<?= $sch['jml_siswa']; ?> Siswa)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Varian Menu Yang Dimasak</label>
                            <select class="form-select form-select-sm" name="id_menu" id="selectMenu" required>
                                <option value="">-- Pilih Menu Distribusi --</option>
                                <?php while ($men = $res_menu->fetch_assoc()): ?>
                                    <option value="<?= $men['id_menu']; ?>">
                                        <?= htmlspecialchars($men['nama_menu']); ?> (<?= $men['kalori']; ?> Kcal)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Total Porsi Dikirim (Boks)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                                <input type="number" class="form-control" name="jml_porsi" id="inputPorsi" min="1" placeholder="Masukkan total boks makanan" required>
                            </div>
                            <div id="infoKuotaBatas" class="form-text text-xs text-info mt-1 d-none">
                                <i class="bi bi-info-circle me-1"></i>Batas maksimal kuota distribusi harian sekolah: <span id="maxQuotaLabel" class="fw-bold">0</span> boks.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-sm btn-dark w-100 fw-medium" id="btnSubmitKirim" style="background-color: #ea580c; border-color: #ea580c;">
                            <i class="bi bi-send-check-fill me-1"></i>Lepas Armada Kurir
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>Log Riwayat Manifes Terkini</h6>
                </div>
                <div class="card-body p-0 rounded-bottom">
                    <div class="table-responsive">
                        <table class="table align-middle m-0">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-2.5">ID Kirim</th>
                                    <th class="py-2.5">Instansi Sekolah Tujuan</th>
                                    <th class="py-2.5">Menu Makanan</th>
                                    <th class="py-2.5 text-center">Porsi Keluar / Tiba</th>
                                    <th class="py-2.5 text-center">Waktu Keberangkatan</th>
                                    <th class="text-center pe-4 py-2.5">Status Manifest</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php if ($res_pengiriman->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-truck-flatbed fs-2 mb-2 d-block text-black-50"></i>
                                            Belum ada log catatan pengiriman komoditas logistik harian.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = $res_pengiriman->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-secondary">#TRK-<?= str_pad($row['id_kirim'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_sekolah']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_menu']); ?></td>
                                            <td class="text-center fw-medium">
                                                <span class="text-dark fw-bold"><?= $row['jml_porsi']; ?></span> 
                                                <span class="text-muted">/</span> 
                                                <span class="text-success fw-bold"><?= ($row['jml_porsi_diterima'] !== null) ? $row['jml_porsi_diterima'] : '-'; ?></span>
                                            </td>
                                            <td class="text-center text-secondary"><?= date('d/m/Y H:i', strtotime($row['waktu_kirim'])); ?> WIB</td>
                                            <td class="text-center pe-4">
                                                <?php if ($row['status'] === 'Proses'): ?>
                                                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 fw-semibold">
                                                        <i class="bi bi-cone-striped me-1"></i>Dalam Perjalanan
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fw-semibold">
                                                        <i class="bi bi-building-check me-1"></i>Telah Diterima
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
</style>


<?php
$stmt_sch->close();
$stmt_men->close();
$stmt_ship->close();
$conn->close();
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>