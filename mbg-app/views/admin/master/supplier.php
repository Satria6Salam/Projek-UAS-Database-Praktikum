<?php
// =========================================================================
// UI INTERFACE: MANAGEMENT MASTER DATA SUPPLIER VENDOR
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memanggil template struktural aplikasi global
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Ambil data untuk parsing edit via link GET jika ada
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_edit = $conn->real_escape_string($_GET['id']);
    $res = $conn->query("SELECT * FROM supplier WHERE id_supplier = '$id_edit' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}
?>

<div class="container-fluid px-1 py-3">
    <!-- <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #304674 !important;">
        <div class="card-body d-flex justify-content-between align-items-center bg-white rounded-end py-3">
            <div>
                <h4 class="fw-bold mb-1" style="color: #304674;"><i class="bi bi-truck me-2"></i>Manajemen Kemitraan Supplier</h4>
                <p class="text-muted small m-0">Registrasi, validasi, dan pengawasan badan usaha/vendor lokal penyedia logistik bahan baku hulu program MBG.</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-inline">
                <ol class="breadcrumb m-0 small fw-medium">
                    <li class="breadcrumb-item"><a href="/mbg-app/views/admin/dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">Master Data</li>
                    <li class="breadcrumb-item active" aria-current="page">Supplier</li>
                </ol>
            </nav>
        </div>
    </div> -->
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-truck me-2" style="color: #304674;"></i>Master Data Supplier
            </h2>
            <p class="text-muted small mb-0">Kelola data badan usaha/vendor lokal penyedia logistik pangan hulu, komoditas utama, dan validasi legalitas kemitraan.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div><?= $_SESSION['sukses']; unset($_SESSION['sukses']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['gagal'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?= $_SESSION['gagal']; unset($_SESSION['gagal']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header text-white py-3" style="background-color: #304674;">
                    <h6 class="m-0 fw-bold">
                        <i class="bi <?= $edit_data ? 'bi-pencil-square' : 'bi-plus-circle-fill'; ?> me-2"></i>
                        <?= $edit_data ? 'Modifikasi Profil Vendor' : 'Registrasi Vendor Baru'; ?>
                    </h6>
                </div>
                <div class="card-body p-4 bg-white rounded-bottom">
                    <form action="/mbg-app/actions/admin/master_supplier.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="scope_action" value="supplier_process">
                        <input type="hidden" name="form_action" value="<?= $edit_data ? 'update' : 'create'; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">ID SUPPLIER / KODE MITRA <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-qr-code"></i></span>
                                <input type="text" name="id_supplier" class="form-control bg-light fw-bold text-uppercase" 
                                       placeholder="Misal: SPL-01" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['id_supplier']) : ''; ?>" <?= $edit_data ? 'readonly' : ''; ?> required>
                                <div class="invalid-feedback">ID supplier wajib diisi!</div>
                            </div>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">ID bersifat unik, permanen, dan tidak dapat diubah setelah disimpan.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">NAMA BADAN USAHA / VENDOR <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-building-gear"></i></span>
                                <input type="text" name="nama_vendor" class="form-control" placeholder="PT / CV / Kelompok Tani" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['nama_vendor']) : ''; ?>" required minlength="3">
                                <div class="invalid-feedback">Nama vendor wajib diisi (Minimal 3 karakter)!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">KLASIFIKASI KOMODITAS UTAMA <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-box-seam-fill"></i></span>
                                <select name="komoditas" class="form-select" style="border-left: none;" required>
                                    <option value="" disabled selected>-- Pilih Kategori Pasokan --</option>
                                    <option value="Karbohidrat / Beras" <?= ($edit_data && $edit_data['komoditas'] == 'Karbohidrat / Beras') ? 'selected' : ''; ?>>Karbohidrat (Beras, Jagung, Kentang)</option>
                                    <option value="Protein / Daging & Ikan" <?= ($edit_data && $edit_data['komoditas'] == 'Protein / Daging & Ikan') ? 'selected' : ''; ?>>Protein (Daging, Ayam, Telur, Ikan)</option>
                                    <option value="Sayur & Hortikultura" <?= ($edit_data && $edit_data['komoditas'] == 'Sayur & Hortikultura') ? 'selected' : ''; ?>>Sayuran & Hortikultura</option>
                                    <option value="Bumbu & Logistik Pelengkap" <?= ($edit_data && $edit_data['komoditas'] == 'Bumbu & Logistik Pelengkap') ? 'selected' : ''; ?>>Bumbu & Logistik Pelengkap</option>
                                </select>
                                <div class="invalid-feedback">Silakan pilih klasifikasi komoditas!</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">NOMOR TELEPON OPERASIONAL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['no_telp']) : ''; ?>">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn text-white fw-bold py-2 shadow-sm" style="background-color: #304674;">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i><?= $edit_data ? 'Simpan Pembaruan' : 'Daftarkan Vendor'; ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="/mbg-app/views/admin/master/supplier.php" class="btn btn-light border py-2 fw-medium small">
                                    <i class="bi bi-arrow-left-short"></i> Batalkan Perubahan
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold" style="color: #243558;"><i class="bi bi-list-stars me-2"></i>Daftar Sah Rekanan Ring 1</h6>
                </div>
                <div class="card-body p-0 bg-white rounded-bottom">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-3" style="width: 15%">ID Vendor</th>
                                    <th style="width: 35%">Nama Perusahaan / Kelompok</th>
                                    <th style="width: 25%">Komoditas Pasokan</th>
                                    <th style="width: 15%">Kontak Telp</th>
                                    <th class="text-center pe-3" style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $query_list = "SELECT * FROM supplier ORDER BY id_supplier ASC";
                                $result_list = $conn->query($query_list);
                                
                                if ($result_list && $result_list->num_rows > 0):
                                    while ($row = $result_list->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><span class="badge badge-custom-role py-1.5 px-2.5"><?= htmlspecialchars($row['id_supplier']); ?></span></td>
                                        <td class="fw-medium text-secondary"><?= htmlspecialchars($row['nama_vendor']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-light border text-dark px-2.5 py-1.5">
                                                <i class="bi bi-tags-fill text-primary me-1"></i><?= htmlspecialchars($row['komoditas']); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= !empty($row['no_telp']) ? htmlspecialchars($row['no_telp']) : '-'; ?></td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                <a href="/mbg-app/views/admin/master/supplier.php?action=edit&id=<?= urlencode($row['id_supplier']); ?>" 
                                                   class="btn btn-white border border-end-0 text-warning" title="Edit Profil">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="/mbg-app/actions/admin/master_supplier.php?scope_action=delete_supplier&id=<?= urlencode($row['id_supplier']); ?>" 
                                                   onclick="konfirmasiHapus(event)"
                                                   class="btn btn-white border text-danger" title="Hapus Dari Sistem">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 bg-light text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                            Belum ada mitra supplier lokal yang terverifikasi di database global.
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

<script src="/mbg-app/assets/js/form_validation.js"></script>

<?php
// Memanggil footer penutup template
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>