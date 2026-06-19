<?php
// =========================================================================
// UI INTERFACE: INTERACTIVE NUTRIENT RECIPE FORM (KARTU MENU DIGITAL)
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Autentikasi Modul Multi-Actor Petugas Dapur Umum
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Simulasi ID Dapur dari sesi aktor login (Staf Dapur)
$id_dapur_aktif = $_SESSION['id_dapur'] ?? 'DPR-01'; 

// Mengambil master katalog opsi pilihan bahan baku pangan secara terpusat
$query_bahan = $conn->query("SELECT id_bahan, nama_bahan, satuan FROM bahan_baku ORDER BY nama_bahan ASC");
$opsi_bahan = [];
while ($row = $query_bahan->fetch_assoc()) {
    $opsi_bahan[] = $row;
}
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-journal-plus me-2" style="color: #ea580c;"></i>Form Racik Formula Variasi Menu
            </h2>
            <p class="text-muted small mb-0">Manajemen Nutrisi: Tentukan target komposisi takaran gramasi bahan baku desimal per porsi boks makanan.</p>
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

    <form action="/mbg-app/actions/dapur/kelola_menu.php" method="POST" id="form-racik-menu" class="needs-validation" novalidate>
        <input type="hidden" name="id_dapur" value="<?= htmlspecialchars($id_dapur_aktif); ?>">
        
        <div class="row g-4">
            <div class="col-xl-4 col-lg-5">
                <div class="card border-0 shadow-sm h-100 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-card-list me-2 text-secondary"></i>Informasi Nutrisi Utama</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="id_menu" class="form-label small fw-bold text-secondary">ID Menu Resep</label>
                            <input type="text" class="form-control" id="id_menu" name="id_menu" placeholder="Contoh: MNU-001" required>
                            <div class="invalid-feedback text-xs">Kode unik identifikasi ID Menu wajib diinput.</div>
                        </div>
                        <div class="mb-3">
                            <label for="nama_menu" class="form-label small fw-bold text-secondary">Nama Menu Variasi</label>
                            <input type="text" class="form-control" id="nama_menu" name="nama_menu" placeholder="Contoh: Nasi Goreng Telur Sayur" required>
                            <div class="invalid-feedback text-xs">Nama ragam makanan tidak boleh dikosongkan.</div>
                        </div>
                        <div class="mb-3">
                            <label for="kalori" class="form-label small fw-bold text-secondary">Target Akumulasi Kalori (Kkal)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lightning-charge"></i></span>
                                <input type="number" class="form-control" id="kalori" name="kalori" min="1" placeholder="Contoh: 650" required>
                                <div class="invalid-feedback text-xs">Jumlah takaran kalori minimal bernilai angka 1.</div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label for="deskripsi" class="form-label small fw-bold text-secondary">Deskripsi / Catatan Alergi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Rincian informasi bahan tambahan atau pantangan alergi..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card border-0 shadow-sm h-100 bg-white">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-egg-fried me-2 text-secondary"></i>Rincian Komposisi Komoditas per Porsi</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1 small fw-medium" id="btn-tambah-baris">
                            <i class="bi bi-plus-circle-fill me-1"></i>Tambah Baris Komposisi
                        </button>
                    </div>
                    <div class="card-body p-0 rounded-bottom">
                        <div class="table-responsive">
                            <table class="table align-middle m-0" id="tabel-komposisi">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-4 py-2.5" style="width: 50%;">Nama Bahan Baku Gudang</th>
                                        <th class="py-2.5" style="width: 35%;">Takaran Gramasi (Per Porsi)</th>
                                        <th class="text-center pe-4 py-2.5" style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <tr class="baris-bahan">
                                        <td class="ps-4">
                                            <select class="form-select select-bahan" name="id_bahan[]" required>
                                                <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                                                <?php foreach ($opsi_bahan as $b): ?>
                                                    <option value="<?= htmlspecialchars($b['id_bahan']); ?>" data-satuan="<?= htmlspecialchars($b['satuan']); ?>">
                                                        <?= htmlspecialchars($b['nama_bahan']); ?> (<?= htmlspecialchars($b['satuan']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback text-xs">Pilih salah satu item katalog.</div>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0.01" class="form-control input-takaran" name="jumlah_takaran[]" placeholder="0.00" required>
                                                <span class="input-group-text bg-light text-muted label-satuan" style="min-width: 65px; font-size: 0.8rem;">Unit</span>
                                                <div class="invalid-feedback text-xs">Nilai desimal harus > 0.00.</div>
                                            </div>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button type="button" class="btn btn-sm btn-link text-danger disabled btn-hapus-baris" title="Minimal menyisakan 1 baris komposisi">
                                                <i class="bi bi-trash3-fill fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-4 text-end">
                        <button type="reset" class="btn btn-sm btn-light border px-3 py-2 text-secondary fw-medium me-2"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset Form</button>
                        <button type="submit" class="btn btn-sm btn-dark px-4 py-2 fw-medium" style="background-color: #ea580c; border-color: #ea580c;"><i class="bi bi-shield-lock-fill me-1"></i>Simpan Kartu Resep</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div id="blueprint-data-container" data-opsi='<?= json_encode($opsi_bahan); ?>' class="d-none"></div>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>