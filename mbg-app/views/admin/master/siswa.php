<?php
// =========================================================================
// UI INTERFACE: MANAGEMENT BIODATA SISWA & TARGET SASARAN BENEFICIARY
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
    $res = $conn->query("SELECT * FROM siswa WHERE nisn = '$id_edit' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

// Filter Instansi Sekolah untuk visualisasi tabel output
$filter_sekolah = isset($_GET['filter_sekolah']) ? $conn->real_escape_string($_GET['filter_sekolah']) : '';
?>

<div class="container-fluid px-1 py-3">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-people-fill me-2" style="color: #304674;"></i>Master Data Siswa
            </h2>
            <p class="text-muted small mb-0">Kelola data biodata siswa penerima manfaat, klasifikasi kelas, pemetaan instansi belajar, serta kendali impor massal data.</p>
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
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header text-white py-3" style="background-color: #304674;">
                    <h6 class="m-0 fw-bold">
                        <i class="bi <?= $edit_data ? 'bi-pencil-square' : 'bi-person-plus-fill'; ?> me-2"></i>
                        <?= $edit_data ? 'Modifikasi Biodata Siswa' : 'Registrasi Siswa Individu'; ?>
                    </h6>
                </div>
                <div class="card-body p-4 bg-white rounded-bottom">
                    <form action="/mbg-app/actions/admin/master_siswa.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="scope_action" value="siswa_process">
                        <input type="hidden" name="form_action" value="<?= $edit_data ? 'update' : 'create'; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">NISN SISWA (NOMOR INDUK) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="nisn" class="form-control bg-light fw-bold" 
                                       placeholder="Misal: 0123456789" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['nisn']) : ''; ?>" <?= $edit_data ? 'readonly' : ''; ?> required pattern="[0-9]{5,20}">
                                <div class="invalid-feedback">NISN wajib diisi berupa angka unik (5-20 digit)!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">NAMA LENGKAP SISWA <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                                <input type="text" name="nama_siswa" class="form-control" placeholder="Nama sesuai dapodik" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['nama_siswa']) : ''; ?>" required>
                                <div class="invalid-feedback">Nama lengkap wajib diisi!</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">KELAS / PARALEL <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-door-closed-fill"></i></span>
                                <input type="text" name="kelas" class="form-control" placeholder="Misal: 1-A, XII-RPL" style="border-left: none;"
                                       value="<?= $edit_data ? htmlspecialchars($edit_data['kelas']) : ''; ?>" required>
                                <div class="invalid-feedback">Kelas wajib diisi!</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">SEKOLAH INDUK / AFILIASI <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-building"></i></span>
                                <select name="id_sekolah" class="form-select" style="border-left: none;" required>
                                    <option value="" disabled selected>-- Pilih Sekolah Induk --</option>
                                    <?php
                                    $sch_res = $conn->query("SELECT id_sekolah, nama_sekolah FROM sekolah ORDER BY nama_sekolah ASC");
                                    while ($sch = $sch_res->fetch_assoc()):
                                    ?>
                                        <option value="<?= $sch['id_sekolah']; ?>" <?= ($edit_data && $edit_data['id_sekolah'] == $sch['id_sekolah']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($sch['nama_sekolah']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Silakan tentukan penempatan instansi sekolah!</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn text-white fw-bold py-2 shadow-sm" style="background-color: #304674;">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i><?= $edit_data ? 'Simpan Pembaruan' : 'Registrasi Siswa'; ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="/mbg-app/views/admin/master/siswa.php" class="btn btn-light border py-2 fw-medium small">
                                    <i class="bi bi-arrow-left-short"></i> Batalkan Perubahan
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!$edit_data): ?>
                <div class="card border-0 shadow-sm border-top border-secondary">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-spreadsheet-fill me-2 text-success"></i>Impor Massal (.CSV)</h6>
                    </div>
                    <div class="card-body p-4 bg-white rounded-bottom">
                        <form action="/mbg-app/actions/admin/master_siswa.php" method="POST" enctype="multipart/form-data" id="form-import-siswa">
                            <input type="hidden" name="scope_action" value="siswa_import">
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted">Pilih dokumen ekstensi CSV hasil ekspor Dapodik:</label>
                                <input type="file" name="file_excel" id="file_excel" class="form-control form-control-sm" accept=".csv" required>
                            </div>
                            
                            <div class="progress mb-3 d-none" id="import-progress-container" style="height: 6px;">
                                <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                            </div>

                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-in-down text-white me-2"></i>Eksekusi Unggah Massal
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3 bg-white rounded">
                    <form method="GET" action="/mbg-app/views/admin/master/siswa.php" class="row g-2 align-items-center">
                        <div class="col-sm-9">
                            <select name="filter_sekolah" class="form-select form-select-sm">
                                <option value="">-- Tampilkan Seluruh Data Siswa Nasional --</option>
                                <?php
                                $sch_res = $conn->query("SELECT id_sekolah, nama_sekolah FROM sekolah ORDER BY nama_sekolah ASC");
                                while ($sch = $sch_res->fetch_assoc()):
                                ?>
                                    <option value="<?= $sch['id_sekolah']; ?>" <?= ($filter_sekolah === $sch['id_sekolah']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($sch['nama_sekolah']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-sm-3 d-grid">
                            <button type="submit" class="btn btn-secondary btn-sm fw-bold"><i class="bi bi-funnel-fill me-1"></i> Saring Data</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold" style="color: #243558;"><i class="bi bi-mortarboard-fill me-2"></i>Daftar Siswa Terdaftar</h6>
                </div>
                <div class="card-body p-0 bg-white rounded-bottom">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-3" style="width: 20%">NISN</th>
                                    <th style="width: 35%">Nama Lengkap</th>
                                    <th style="width: 15%">Kelas</th>
                                    <th style="width: 20%">Instansi Sekolah</th>
                                    <th class="text-center pe-3" style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $sql_list = "SELECT siswa.*, sekolah.nama_sekolah FROM siswa 
                                             JOIN sekolah ON siswa.id_sekolah = sekolah.id_sekolah";
                                if (!empty($filter_sekolah)) {
                                    $sql_list .= " WHERE siswa.id_sekolah = '$filter_sekolah'";
                                }
                                $sql_list .= " ORDER BY siswa.id_sekolah ASC, siswa.nama_siswa ASC";
                                $result_list = $conn->query($sql_list);
                                
                                if ($result_list && $result_list->num_rows > 0):
                                    while ($row = $result_list->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><i class="bi bi-identification text-muted me-1"></i><?= htmlspecialchars($row['nisn']); ?></td>
                                        <td class="fw-medium text-secondary"><?= htmlspecialchars($row['nama_siswa']); ?></td>
                                        <td><span class="badge bg-light border text-secondary px-2.5 py-1.5"><?= htmlspecialchars($row['kelas']); ?></span></td>
                                        <td class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($row['nama_sekolah']); ?></td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                <a href="/mbg-app/views/admin/master/siswa.php?action=edit&id=<?= urlencode($row['nisn']); ?><?= !empty($filter_sekolah) ? '&filter_sekolah='.$filter_sekolah : ''; ?>" 
                                                   class="btn btn-white border border-end-0 text-warning" title="Ubah Profil">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="/mbg-app/actions/admin/master_siswa.php?scope_action=delete_siswa&id=<?= urlencode($row['nisn']); ?>" 
                                                   onclick="konfirmasiHapus(event)"
                                                   class="btn btn-white border text-danger" title="Hapus Siswa">
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
                                            <i class="bi bi-emoji-neutral fs-2 d-block mb-2"></i>
                                            Tidak ditemukan data siswa aktif untuk parameter saringan instansi ini.
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