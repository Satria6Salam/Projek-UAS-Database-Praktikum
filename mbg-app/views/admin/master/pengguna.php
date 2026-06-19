<?php
// =========================================================================
// UI INTERFACE: CENTRAL USER ACCOUNT MANAGEMENT & ACTIVATION CENTER
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_edit = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM pengguna WHERE id_user = $id_edit LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $edit_user = $res->fetch_assoc();
    }
}
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-shield-lock-fill me-2" style="color: #ea580c;"></i>Kontrol Akun Terpusat
            </h2>
            <p class="text-muted small mb-0">Manajemen otentikasi multi-aktor untuk menegakkan standarisasi Single Role Restriction berdasarkan data entitas fisik lapangan.</p>
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
                <div class="card-header text-white py-3" style="background-color: #ea580c;">
                    <h6 class="m-0 fw-bold">
                        <i class="bi <?= $edit_user ? 'bi-pencil-square' : 'bi-person-plus-fill'; ?> me-2"></i>
                        <?= $edit_user ? 'Ubah Hak Akses Pengguna' : 'Buat Kredensial Baru'; ?>
                    </h6>
                </div>
                <div class="card-body p-4 bg-white rounded-bottom">
                    <form action="/mbg-app/actions/admin/master_pengguna.php" method="POST" class="needs-validation" novalidate id="form-pengguna">
                        <input type="hidden" name="scope_action" value="user_process">
                        <input type="hidden" name="form_action" value="<?= $edit_user ? 'update' : 'create'; ?>">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="id_user" value="<?= $edit_user['id_user']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">NAMA PENGGUNA (USERNAME) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-badge"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Misal: bango_dapur" style="border-left: none;"
                                       value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                                <div class="invalid-feedback">Nama pengguna tidak boleh kosong!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">ALAMAT EMAIL AKTIF <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="admin@domain.com" style="border-left: none;"
                                       value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                                <div class="invalid-feedback">Isi format alamat surel email dengan benar!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">KATA SANDI (PASSWORD) <?= $edit_user ? '<span class="text-muted fw-normal">(Kosongkan jika tak diubah)</span>' : '<span class="text-danger">*</span>'; ?></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key-fill"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="<?= $edit_user ? '••••••••' : 'Minimal 6 karakter'; ?>" style="border-left: none;" <?= $edit_user ? '' : 'required'; ?> minlength="6">
                                <div class="invalid-feedback">Kata sandi minimal berisi 6 karakter!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">AKTOR ROLE UTAMA <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-sliders"></i></span>
                                <select name="role" id="select-role" class="form-select" style="border-left: none;" required>
                                    <option value="" disabled selected>-- Pilih Role Hak Akses --</option>
                                    <option value="Admin" <?= ($edit_user && $edit_user['role'] === 'Admin') ? 'selected' : ''; ?>>Admin (Super User)</option>
                                    <option value="Dapur" <?= ($edit_user && $edit_user['role'] === 'Dapur') ? 'selected' : ''; ?>>Dapur (Petugas Regional)</option>
                                    <option value="Sekolah" <?= ($edit_user && $edit_user['role'] === 'Sekolah') ? 'selected' : ''; ?>>Sekolah (Verifikator Lapangan)</option>
                                    <option value="Supplier" <?= ($edit_user && $edit_user['role'] === 'Supplier') ? 'selected' : ''; ?>>Supplier (Mitra Vendor Pemasok)</option>
                                </select>
                                <div class="invalid-feedback">Wajib memetakan satu hak akses aktif!</div>
                            </div>
                        </div>

                        <div id="mapping-sekolah-container" class="mb-4 d-none">
                            <label class="form-label small fw-bold text-secondary text-primary"><i class="bi bi-arrow-return-right me-1"></i>IKAT KE ENTITAS SEKOLAH SASARAN <span class="text-danger">*</span></label>
                            <select name="id_sekolah" id="id_sekolah" class="form-select bg-light border-primary">
                                <option value="">-- Pilih Instansi Sekolah Sasaran --</option>
                                <?php
                                $sch_res = $conn->query("SELECT id_sekolah, nama_sekolah FROM sekolah ORDER BY nama_sekolah ASC");
                                while($s = $sch_res->fetch_assoc()) {
                                    $sel = ($edit_user && $edit_user['id_sekolah'] === $s['id_sekolah']) ? 'selected' : '';
                                    echo "<option value='{$s['id_sekolah']}' {$sel}>[{$s['id_sekolah']}] {$s['nama_sekolah']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="mapping-dapur-container" class="mb-4 d-none">
                            <label class="form-label small fw-bold text-secondary text-success"><i class="bi bi-arrow-return-right me-1"></i>IKAT KE ENTITAS PERSONEL STAF DAPUR <span class="text-danger">*</span></label>
                            <select name="id_staf" id="id_staf" class="form-select bg-light border-success">
                                <option value="">-- Pilih Nama Staf Produksi --</option>
                                <?php
                                $stf_res = $conn->query("SELECT id_staf, nama_staf, peran FROM staf_dapur ORDER BY nama_staf ASC");
                                while($st = $stf_res->fetch_assoc()) {
                                    $sel = ($edit_user && $edit_user['id_staf'] === $st['id_staf']) ? 'selected' : '';
                                    echo "<option value='{$st['id_staf']}' {$sel}>[{$st['peran']}] {$st['nama_staf']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="mapping-supplier-container" class="mb-4 d-none">
                            <label class="form-label small fw-bold text-secondary text-warning"><i class="bi bi-arrow-return-right me-1"></i>IKAT KE ENTITAS SUPPLIER MITRA <span class="text-danger">*</span></label>
                            <select name="id_supplier" id="id_supplier" class="form-select bg-light border-warning">
                                <option value="">-- Pilih Badan Vendor Supplier --</option>
                                <?php
                                $spl_res = $conn->query("SELECT id_supplier, nama_vendor FROM supplier ORDER BY nama_vendor ASC");
                                while($sp = $spl_res->fetch_assoc()) {
                                    $sel = ($edit_user && $edit_user['id_supplier'] === $sp['id_supplier']) ? 'selected' : '';
                                    echo "<option value='{$sp['id_supplier']}' {$sel}>{$sp['nama_vendor']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn text-white fw-bold py-2 shadow-sm" style="background-color: #ea580c;">
                                <i class="bi bi-shield-check me-2"></i><?= $edit_user ? 'Perbarui Hak Akses' : 'Simpan & Daftarkan Akun'; ?>
                            </button>
                            <?php if ($edit_user): ?>
                                <a href="/mbg-app/views/admin/master/pengguna.php" class="btn btn-light border py-2 fw-medium small">
                                    <i class="bi bi-x-circle"></i> Batal
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
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-person-lines-fill me-2 text-secondary"></i>Daftar Manajemen Pengguna Terdaftar</h6>
                </div>
                <div class="card-body p-0 bg-white rounded-bottom">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-3" style="width: 25%">Username / Surel</th>
                                    <th style="width: 15%">Role Akses</th>
                                    <th style="width: 45%">Ikatan Fisik (Zonasi Pemetaan Akun)</th>
                                    <th class="text-center pe-3" style="width: 15%">Opsi Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $sql = "SELECT p.*, s.nama_sekolah, sd.nama_staf, sd.peran as peran_staf, sp.nama_vendor 
                                        FROM pengguna p
                                        LEFT JOIN sekolah s ON p.id_sekolah = s.id_sekolah
                                        LEFT JOIN staf_dapur sd ON p.id_staf = sd.id_staf
                                        LEFT JOIN supplier sp ON p.id_supplier = sp.id_supplier
                                        ORDER BY p.id_user DESC";
                                $res_list = $conn->query($sql);

                                if ($res_list && $res_list->num_rows > 0):
                                    while ($u = $res_list->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['username']); ?></div>
                                            <div class="text-muted text-xs small" style="font-size: 0.75rem;"><?= htmlspecialchars($u['email']); ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-dark';
                                            if ($u['role'] === 'Admin') $badge_class = 'bg-danger';
                                            elseif ($u['role'] === 'Dapur') $badge_class = 'bg-success';
                                            elseif ($u['role'] === 'Sekolah') $badge_class = 'bg-primary';
                                            elseif ($u['role'] === 'Supplier') $badge_class = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?= $badge_class; ?> px-2 py-1.5 fw-semibold"><?= $u['role']; ?></span>
                                        </td>
                                        <td class="text-secondary fw-medium">
                                            <?php if ($u['role'] === 'Admin'): ?>
                                                <span class="text-muted small"><i class="bi bi-globe me-1"></i>Otoritas Akses Global Nasional</span>
                                            <?php elseif ($u['role'] === 'Sekolah'): ?>
                                                <span class="text-primary small"><i class="bi bi-building me-1"></i><?= $u['nama_sekolah'] ? htmlspecialchars($u['nama_sekolah']) : '<span class="text-danger">⚠️ Pemetaan Hilang</span>'; ?></span>
                                            <?php elseif ($u['role'] === 'Dapur'): ?>
                                                <span class="text-success small"><i class="bi bi-person-workspace me-1"></i><?= $u['nama_staf'] ? htmlspecialchars($u['nama_staf'] . ' ('.$u['peran_staf'].')') : '<span class="text-danger">⚠️ Pemetaan Hilang</span>'; ?></span>
                                            <?php elseif ($u['role'] === 'Supplier'): ?>
                                                <span class="text-warning text-dark small"><i class="bi bi-truck me-1"></i><?= $u['nama_vendor'] ? htmlspecialchars($u['nama_vendor']) : '<span class="text-danger">⚠️ Pemetaan Hilang</span>'; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                <a href="/mbg-app/views/admin/master/pengguna.php?action=edit&id=<?= $u['id_user']; ?>" 
                                                   class="btn btn-white border border-end-0 text-warning" title="Ubah Parameter">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="/mbg-app/actions/admin/master_pengguna.php?scope_action=toggle_status&id=<?= $u['id_user']; ?>&role=<?= $u['role']; ?>" 
                                                   onclick="konfirmasiHapus(event)"
                                                   class="btn btn-white border text-danger" title="Cabut Akses Akun">
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
                                        <td colspan="4" class="text-center py-5 text-muted bg-light">
                                            <i class="bi bi-shield-slash fs-1 d-block mb-2"></i>
                                            Belum ada rekam data login pengguna terdaftar dalam sistem data master.
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

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>