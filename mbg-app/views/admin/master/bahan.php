<?php
// =========================================================================
// UI INTERFACE: MANAGEMENT MASTER CATALOG GLOBAL INVENTORY (BAHAN BAKU)
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_edit = $conn->real_escape_string($_GET['id']);
    $res = $conn->query("SELECT * FROM bahan_baku WHERE id_bahan = '$id_edit' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-egg-fried me-2" style="color: #304674;"></i>Master Data Bahan Baku
            </h2>
            <p class="text-muted small mb-0">Kelola data komoditas bahan makanan global, penetapan standar satuan ukur, dan ambang batas minimum peringatan restock gudang.</p>
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
                        <?= $edit_data ? 'Modifikasi Komoditas' : 'Tambah Komoditas Baru'; ?>
                    </h6>
                </div>
                <div class="card-body p-4 bg-white rounded-bottom">
                    <form action="/mbg-app/actions/admin/master_bahan.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="scope_action" value="bahan_process">
                        <input type="hidden" name="form_action" value="<?= $edit_data ? 'update' : 'create'; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">ID BAHAN BAKU / KODE GLOBAL <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-hash"></i></span>
                                <input type="text" name="id_bahan" class="form-control bg-light fw-bold text-uppercase" 
                                       placeholder="Misal: BHN-001" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['id_bahan']) : ''; ?>" <?= $edit_data ? 'readonly' : ''; ?> required>
                                <div class="invalid-feedback">ID bahan baku wajib ditentukan!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">NAMA KOMODITAS BAHAN <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-basket3"></i></span>
                                <input type="text" name="nama_bahan" class="form-control" placeholder="Misal: Beras Premium, Daging Sapi" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['nama_bahan']) : ''; ?>" required minlength="2">
                                <div class="invalid-feedback">Nama komoditas wajib diisi!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">SATUAN UKUR <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-calculator"></i></span>
                                <select name="satuan" class="form-select" style="border-left: none;" required>
                                    <option value="" disabled selected>-- Pilih Standar Satuan --</option>
                                    <option value="kg" <?= ($edit_data && $edit_data['satuan'] == 'kg') ? 'selected' : ''; ?>>kg (Kilogram)</option>
                                    <option value="liter" <?= ($edit_data && $edit_data['satuan'] == 'liter') ? 'selected' : ''; ?>>liter (Liter)</option>
                                    <option value="butir" <?= ($edit_data && $edit_data['satuan'] == 'butir') ? 'selected' : ''; ?>>butir (Butir / Telur)</option>
                                    <option value="pack" <?= ($edit_data && $edit_data['satuan'] == 'pack') ? 'selected' : ''; ?>>pack (Paket Kemasan)</option>
                                </select>
                                <div class="invalid-feedback">Silakan tentukan standar satuan ukur!</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">STOK MINIMUM (AMBANG PERINGATAN) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-bell-fill"></i></span>
                                <input type="number" name="stok_min" class="form-control" placeholder="0" style="border-left: none;" min="0"
                                       value="<?= $edit_data ? intval($edit_data['stok_min']) : '0'; ?>" required>
                                <div class="invalid-feedback">Ambang peringatan stok tidak boleh kosong / bernilai negatif!</div>
                            </div>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">Memicu peringatan visual (alert flags) di dashboard operasional jika persediaan dapur umum di bawah nilai ini.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn text-white fw-bold py-2 shadow-sm" style="background-color: #304674;">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i><?= $edit_data ? 'Simpan Perubahan' : 'Simpan ke Katalog'; ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="/mbg-app/views/admin/master/bahan.php" class="btn btn-light border py-2 fw-medium small">
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
                    <h6 class="m-0 fw-bold" style="color: #243558;"><i class="bi bi-list-check me-2"></i>Katalog Komoditas Aktif Nasional</h6>
                </div>
                <div class="card-body p-0 bg-white rounded-bottom">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-3" style="width: 20%">ID Bahan</th>
                                    <th style="width: 40%">Nama Komoditas / Bahan Baku</th>
                                    <th style="width: 20%">Satuan Standar</th>
                                    <th style="width: 10%">Batas Min</th>
                                    <th class="text-center pe-3" style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $query_list = "SELECT * FROM bahan_baku ORDER BY id_bahan ASC";
                                $result_list = $conn->query($query_list);
                                
                                if ($result_list && $result_list->num_rows > 0):
                                    while ($row = $result_list->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><span class="badge bg-light border text-dark py-1.5 px-2.5"># <?= htmlspecialchars($row['id_bahan']); ?></span></td>
                                        <td class="fw-medium text-secondary"><?= htmlspecialchars($row['nama_bahan']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-light border text-dark px-2.5 py-1.5">
                                                <i class="bi bi-rulers text-secondary me-1"></i><?= htmlspecialchars($row['satuan']); ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-danger"><?= intval($row['stok_min']); ?></td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                <a href="/mbg-app/views/admin/master/bahan.php?action=edit&id=<?= urlencode($row['id_bahan']); ?>" 
                                                   class="btn btn-white border border-end-0 text-warning" title="Ubah Parameter">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="/mbg-app/actions/admin/master_bahan.php?scope_action=delete_bahan&id=<?= urlencode($row['id_bahan']); ?>" 
                                                   onclick="konfirmasiHapus(event)"
                                                   class="btn btn-white border text-danger" title="Hapus Komoditas">
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
                                            <i class="bi bi-collection text-secondary fs-1 d-block mb-2"></i>
                                            Katalog global bahan baku masih kosong.
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
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>