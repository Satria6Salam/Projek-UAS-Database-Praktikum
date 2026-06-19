<?php
// =========================================================================
// VIEWS: MANAGEMENT DATA DAPUR UMUM & STAF KEPEGAWAIAN
// =========================================================================

// Mengambil file security menggunakan DOCUMENT_ROOT agar anti-gagal pathing
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';

// Memastikan hanya pengguna dengan role 'Admin' yang bisa membuka halaman ini
cekRole(['Admin']);

// Memanggil koneksi database utama berbasis absolut path server
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Memanggil Header dan Sidebar Layout 
include_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/header.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/sidebar.php';

// -------------------------------------------------------------------------
// DATA FETCHING: DAPUR UMUM & CEK TRANSAKSI ACTIVE
// -------------------------------------------------------------------------
$dapur_data = [];
$query_dapur = "SELECT * FROM dapur_umum ORDER BY id_dapur ASC";
$result_dapur = $conn->query($query_dapur);
if ($result_dapur) {
    while ($row = $result_dapur->fetch_assoc()) {
        $id_dapur_safe = $conn->real_escape_string($row['id_dapur']);
        
        // Restrict Delete Guard Rule Check 
        $check_pasokan = $conn->query("SELECT id_pasokan FROM pasokan_bahan WHERE id_dapur = '$id_dapur_safe' LIMIT 1");
        $check_kirim = $conn->query("SELECT id_kirim FROM pengiriman WHERE id_dapur = '$id_dapur_safe' LIMIT 1");
        
        $row['has_transaction'] = (($check_pasokan && $check_pasokan->num_rows > 0) || ($check_kirim && $check_kirim->num_rows > 0)) ? true : false;
        $dapur_data[] = $row;
    }
}

// -------------------------------------------------------------------------
// DATA FETCHING: STAF DAPUR & CEK TRANSAKSI ACCOUNT USERS
// -------------------------------------------------------------------------
$staf_data = [];
$query_staf = "SELECT sd.*, du.nama_dapur FROM staf_dapur sd 
               INNER JOIN dapur_umum du ON sd.id_dapur = du.id_dapur 
               ORDER BY sd.id_staf ASC";
$result_staf = $conn->query($query_staf);
if ($result_staf) {
    while ($row = $result_staf->fetch_assoc()) {
        $id_staf_safe = $conn->real_escape_string($row['id_staf']);
        
        // Restrict Delete Guard Rule Check (Staf Terikat Akun Login Pengguna)
        $check_user = $conn->query("SELECT id_user FROM pengguna WHERE id_staf = '$id_staf_safe' LIMIT 1");
        
        $row['has_transaction'] = ($check_user && $check_user->num_rows > 0) ? true : false;
        $staf_data[] = $row;
    }
}

// -------------------------------------------------------------------------
// TANGKAP MODE EDIT (DAPUR / STAF) VIA PARAMETER URL
// -------------------------------------------------------------------------
$edit_dapur_mode = false;
$edit_staf_mode = false;

$edit_dapur = ['id_dapur' => '', 'nama_dapur' => '', 'lokasi' => '', 'kapasitas' => 0];
$edit_staf = ['id_staf' => '', 'id_dapur' => '', 'nama_staf' => '', 'peran' => '', 'no_telp' => ''];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $edit_id = $conn->real_escape_string($_GET['id']);
    
    if ($_GET['action'] == 'edit_dapur') {
        $query_edit = "SELECT * FROM dapur_umum WHERE id_dapur = '$edit_id'";
        $res = $conn->query($query_edit);
        if ($res && $res->num_rows > 0) {
            $edit_dapur_mode = true;
            $edit_dapur = $res->fetch_assoc();
        }
    } elseif ($_GET['action'] == 'edit_staf') {
        $query_edit = "SELECT * FROM staf_dapur WHERE id_staf = '$edit_id'";
        $res = $conn->query($query_edit);
        if ($res && $res->num_rows > 0) {
            $edit_staf_mode = true;
            $edit_staf = $res->fetch_assoc();
        }
    }
}
?>

<div class="container-fluid px-0">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-fire me-2" style="color: #304674;"></i>Manajemen Produksi Dapur & Staf
            </h2>
            <p class="text-muted small mb-0">Kelola prasarana operasional dapur umum regional, kuota batas kapasitas porsi masak, dan pemetaan SDM kepegawaian.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['sukses']; unset($_SESSION['sukses']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['gagal'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['gagal']; unset($_SESSION['gagal']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-lg-5 col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white px-3 py-2.5" style="background-color: #304674;">
                    <h5 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi <?= $edit_dapur_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"></i>
                        <?= $edit_dapur_mode ? 'Ubah Prasarana Dapur' : 'Registrasi Dapur Umum Baru' ?>
                    </h5>
                </div>
                <div class="card-body p-3">
                    <form action="/mbg-app/actions/admin/master_dapur.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="scope_action" value="dapur_process">
                        <input type="hidden" name="form_action" value="<?= $edit_dapur_mode ? 'update' : 'create' ?>">
                        
                        <div class="mb-2.5">
                            <label for="id_dapur" class="form-label text-dark small fw-bold mb-1">ID Unit Dapur Umum</label>
                            <input type="text" class="form-control form-control-sm" id="id_dapur" name="id_dapur" 
                                   value="<?= htmlspecialchars($edit_dapur['id_dapur']) ?>" 
                                   <?= $edit_dapur_mode ? 'readonly style="background-color: #e9ecef;"' : '' ?> 
                                   maxlength="20" required placeholder="Contoh: DPR-TUREN01">
                            <div class="invalid-feedback">ID Dapur Umum wajib diisi (Maksimal 20 Karakter).</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="nama_dapur" class="form-label text-dark small fw-bold mb-1">Nama Unit Dapur</label>
                            <input type="text" class="form-control form-control-sm" id="nama_dapur" name="nama_dapur" 
                                   value="<?= htmlspecialchars($edit_dapur['nama_dapur']) ?>" 
                                   maxlength="100" required placeholder="Contoh: Dapur Umum Turen Pusat">
                            <div class="invalid-feedback">Nama unit dapur wajib diisi.</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="kapasitas" class="form-label text-dark small fw-bold mb-1">Kapasitas Produksi Masak Harian</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                       value="<?= htmlspecialchars($edit_dapur['kapasitas']) ?>" 
                                       min="0" required placeholder="0">
                                <span class="input-group-text bg-light text-muted small">Porsi / Boks</span>
                            </div>
                            <div class="invalid-feedback">Kapasitas masak maksimal harus diisi berupa nominal valid.</div>
                        </div>

                        <div class="mb-3">
                            <label for="lokasi" class="form-label text-dark small fw-bold mb-1">Lokasi Titik Koordinat / Alamat Fisik</label>
                            <textarea class="form-control form-control-sm" id="lokasi" name="lokasi" rows="2" placeholder="Tuliskan alamat lengkap area operasional dapur..."><?= htmlspecialchars($edit_dapur['lokasi']) ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm text-white fw-bold d-flex align-items-center justify-content-center" style="background-color: #304674;">
                                <i class="bi bi-cursor-fill me-1.5"></i> Simpan Data Dapur
                            </button>
                            <?php if ($edit_dapur_mode): ?>
                                <a href="dapur.php" class="btn btn-sm btn-outline-secondary fw-bold d-flex align-items-center justify-content-center">
                                    <i class="bi bi-x-circle me-1.5"></i> Batalkan Perubahan
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7 col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2.5">
                    <h5 class="card-title text-dark mb-0 fs-6 fw-bold">
                        <i class="bi bi-table me-2" style="color: #304674;"></i>Daftar Prasarana Dapur Regional
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 small">
                            <thead class="text-white" style="background-color: #243558; font-size: 0.82rem;">
                                <tr>
                                    <th class="px-3 py-2.5" style="width: 20%">ID Dapur</th>
                                    <th class="py-2.5" style="width: 35%">Nama Dapur & Alamat</th>
                                    <th class="py-2.5" style="width: 25%">Kapasitas Masak Max</th>
                                    <th class="py-2.5 text-center" style="width: 20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dapur_data)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> Belum ada rekaman prasarana dapur umum.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dapur_data as $row): ?>
                                        <tr>
                                            <td class="px-3 fw-bold text-secondary"><?= htmlspecialchars($row['id_dapur']) ?></td>
                                            <td>
                                                <span class="d-block fw-bold text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($row['nama_dapur']) ?></span>
                                                <small class="text-muted d-inline-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['lokasi']) ?>">
                                                    <?= !empty($row['lokasi']) ? htmlspecialchars($row['lokasi']) : '-' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-2.5 py-1.5 fw-bold" style="background-color: #e2f0d9; color: #385723;">
                                                    <i class="bi bi-egg-fried me-1"></i><?= number_format($row['kapasitas'], 0, ',', '.') ?> Porsi / Hari
                                                </span>
                                            </td>
                                            <td class="text-center px-2">
                                                <div class="btn-group" role="group">
                                                    <a href="dapur.php?action=edit_dapur&id=<?= urlencode($row['id_dapur']) ?>" 
                                                       class="btn btn-xs btn-outline-primary py-1 px-2 text-decoration-none" style="font-size: 0.78rem;" title="Ubah Data Dapur">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($row['has_transaction']): ?>
                                                        <button class="btn btn-xs btn-light text-muted border py-1 px-2" style="font-size: 0.78rem;" disabled 
                                                                title="Data terkunci! Terikat manifes distribusi makanan atau mutasi logistik hulu.">
                                                            <i class="bi bi-lock-fill text-warning"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="/mbg-app/actions/admin/master_dapur.php?scope_action=delete_dapur&id=<?= urlencode($row['id_dapur']) ?>" 
                                                           class="btn btn-xs btn-outline-danger py-1 px-2 text-decoration-none" style="font-size: 0.78rem;" 
                                                           onclick="return konfirmasiHapus(event);" title="Hapus Data Dapur">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4 col-lg-5 col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white px-3 py-2.5" style="background-color: #243558;">
                    <h5 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi <?= $edit_staf_mode ? 'bi-pencil-square' : 'bi-person-plus-fill' ?> me-2"></i>
                        <?= $edit_staf_mode ? 'Ubah Penempatan Staf' : 'Registrasi Pegawai Dapur Baru' ?>
                    </h5>
                </div>
                <div class="card-body p-3">
                    <form action="/mbg-app/actions/admin/master_dapur.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="scope_action" value="staf_process">
                        <input type="hidden" name="form_action" value="<?= $edit_staf_mode ? 'update' : 'create' ?>">
                        
                        <div class="mb-2.5">
                            <label for="id_staf" class="form-label text-dark small fw-bold mb-1">ID Staf / NIP Pegawai</label>
                            <input type="text" class="form-control form-control-sm" id="id_staf" name="id_staf" 
                                   value="<?= htmlspecialchars($edit_staf['id_staf']) ?>" 
                                   <?= $edit_staf_mode ? 'readonly style="background-color: #e9ecef;"' : '' ?> 
                                   maxlength="20" required placeholder="Contoh: STF-DPR001">
                            <div class="invalid-feedback">ID Identitas Staf wajib diisi (Maksimal 20 Karakter).</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="id_dapur_staf" class="form-label text-dark small fw-bold mb-1">
                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i>Penempatan Unit Kerja Dapur
                            </label>
                            <select class="form-select form-select-sm" id="id_dapur_staf" name="id_dapur" required>
                                <option value="">-- Pilih Penempatan Dapur --</option>
                                <?php foreach ($dapur_data as $dp): ?>
                                    <option value="<?= $dp['id_dapur'] ?>" <?= ($edit_staf['id_dapur'] == $dp['id_dapur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dp['nama_dapur']) ?> (<?= htmlspecialchars($dp['id_dapur']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Silakan pilih lokasi unit dapur penugasan.</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="nama_staf" class="form-label text-dark small fw-bold mb-1">Nama Lengkap Pegawai</label>
                            <input type="text" class="form-control form-control-sm" id="nama_staf" name="nama_staf" 
                                   value="<?= htmlspecialchars($edit_staf['nama_staf']) ?>" 
                                   maxlength="100" required placeholder="Masukkan nama lengkap staf">
                            <div class="invalid-feedback">Nama lengkap pegawai wajib diisi.</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="peran" class="form-label text-dark small fw-bold mb-1">Peran Struktural / Jabatan</label>
                            <select class="form-select form-select-sm" id="peran" name="peran" required>
                                <option value="">-- Pilih Struktur Peran --</option>
                                <option value="Kepala Dapur" <?= ($edit_staf['peran'] == 'Kepala Dapur') ? 'selected' : '' ?>>Kepala Dapur (Manajerial)</option>
                                <option value="Juru Masak" <?= ($edit_staf['peran'] == 'Juru Masak') ? 'selected' : '' ?>>Juru Masak (Produksi Sisi Hulu)</option>
                                <option value="Petugas Logistik" <?= ($edit_staf['peran'] == 'Petugas Logistik') ? 'selected' : '' ?>>Petugas Logistik & Stok</option>
                                <option value="Kurir Distribusi" <?= ($edit_staf['peran'] == 'Kurir Distribusi') ? 'selected' : '' ?>>Kurir Distribusi Armada</option>
                            </select>
                            <div class="invalid-feedback">Penentuan peran struktural wajib dipilih.</div>
                        </div>

                        <div class="mb-3">
                            <label for="no_telp" class="form-label text-dark small fw-bold mb-1">Nomor Kontak / WhatsApp Aktif</label>
                            <input type="text" class="form-control form-control-sm" id="no_telp" name="no_telp" 
                                   value="<?= htmlspecialchars($edit_staf['no_telp']) ?>" 
                                   maxlength="15" placeholder="Contoh: 0812345678xx">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm text-white fw-bold d-flex align-items-center justify-content-center" style="background-color: #243558;">
                                <i class="bi bi-person-check-fill me-1.5"></i> Simpan Penempatan Staf
                            </button>
                            <?php if ($edit_staf_mode): ?>
                                <a href="dapur.php" class="btn btn-sm btn-outline-secondary fw-bold d-flex align-items-center justify-content-center">
                                    <i class="bi bi-x-circle me-1.5"></i> Batalkan Perubahan
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7 col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2.5">
                    <h5 class="card-title text-dark mb-0 fs-6 fw-bold">
                        <i class="bi bi-people me-2" style="color: #243558;"></i>Struktur Kepegawaian Internal Dapur Aktif
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 small">
                            <thead class="text-white" style="background-color: #304674; font-size: 0.82rem;">
                                <tr>
                                    <th class="px-3 py-2.5" style="width: 20%">ID Staf</th>
                                    <th class="py-2.5" style="width: 30%">Nama Pegawai</th>
                                    <th class="py-2.5" style="width: 20%">Jabatan</th>
                                    <th class="py-2.5" style="width: 15%">Unit Kerja</th>
                                    <th class="py-2.5 text-center" style="width: 15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($staf_data)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-people-fill fs-4 d-block mb-2"></i> Belum ada rekaman pegawai staf dapur.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($staf_data as $st): ?>
                                        <tr>
                                            <td class="px-3 fw-bold text-secondary"><?= htmlspecialchars($st['id_staf']) ?></td>
                                            <td>
                                                <span class="d-block fw-bold text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($st['nama_staf']) ?></span>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= !empty($st['no_telp']) ? htmlspecialchars($st['no_telp']) : '-' ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-custom-role">
                                                    <?= htmlspecialchars($st['peran']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-dark fw-semibold"><i class="bi bi-house-door text-primary me-1"></i><?= htmlspecialchars($st['nama_dapur']) ?></span>
                                            </td>
                                            <td class="text-center px-2">
                                                <div class="btn-group" role="group">
                                                    <a href="dapur.php?action=edit_staf&id=<?= urlencode($st['id_staf']) ?>" 
                                                       class="btn btn-xs btn-outline-primary py-1 px-2 text-decoration-none" style="font-size: 0.78rem;" title="Ubah Data Staf">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($st['has_transaction']): ?>
                                                        <button class="btn btn-xs btn-light text-muted border py-1 px-2" style="font-size: 0.78rem;" disabled 
                                                                title="Data Terkunci! Kredensial staf terikat aktif pada akun sistem master_pengguna.">
                                                            <i class="bi bi-lock-fill text-warning"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="/mbg-app/actions/admin/master_dapur.php?scope_action=delete_staf&id=<?= urlencode($st['id_staf']) ?>" 
                                                           class="btn btn-xs btn-outline-danger py-1 px-2 text-decoration-none" style="font-size: 0.78rem;" 
                                                           onclick="return konfirmasiHapus(event);" title="Hapus Data Staf">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
// Memanggil komponen footer layouting secara aman
include_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>