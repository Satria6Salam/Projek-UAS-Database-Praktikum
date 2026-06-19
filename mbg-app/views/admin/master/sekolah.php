<?php
// =========================================================================
// VIEWS: MANAGEMENT DATA SEKOLAH & MAPPING ZONASI DISTRIBUSI
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

// Ambil data dapur umum untuk dropdown mapping zonasi
$dapur_options = [];
$query_dapur = "SELECT id_dapur, nama_dapur FROM dapur_umum ORDER BY nama_dapur ASC";
$result_dapur = $conn->query($query_dapur);
if ($result_dapur) {
    while ($row = $result_dapur->fetch_assoc()) {
        $dapur_options[] = $row;
    }
}

// Ambil data sekolah beserta relasi nama dapurnya untuk tabel master
$sekolah_data = [];
$query_sekolah = "SELECT s.*, d.nama_dapur 
                  FROM sekolah s 
                  LEFT JOIN dapur_umum d ON s.id_dapur = d.id_dapur 
                  ORDER BY s.id_sekolah ASC";
$result_sekolah = $conn->query($query_sekolah);
if ($result_sekolah) {
    while ($row = $result_sekolah->fetch_assoc()) {
        // Cek Restrict Delete Rule
        $id_sekolah_safe = $conn->real_escape_string($row['id_sekolah']);
        $check_transaksi = $conn->query("SELECT id_kirim FROM pengiriman WHERE id_sekolah = '$id_sekolah_safe' LIMIT 1");
        $row['has_transaction'] = ($check_transaksi && $check_transaksi->num_rows > 0) ? true : false;
        
        $sekolah_data[] = $row;
    }
}

// Tangkap mode edit jika ada parameter 'edit' pada URL
$edit_mode = false;
$edit_data = ['id_sekolah' => '', 'nama_sekolah' => '', 'alamat' => '', 'jml_siswa' => 0, 'id_dapur' => ''];
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = $conn->real_escape_string($_GET['id']);
    $query_edit = "SELECT * FROM sekolah WHERE id_sekolah = '$edit_id'";
    $result_edit = $conn->query($query_edit);
    if ($result_edit && $result_edit->num_rows > 0) {
        $edit_mode = true;
        $edit_data = $result_edit->fetch_assoc();
    }
}
?>

<div class="container-fluid px-0">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="h4 mb-1 text-dark fw-bold">
                <i class="bi bi-building-gear me-2" style="color: #304674;"></i>Master Data Sekolah
            </h2>
            <p class="text-muted small mb-0">Kelola data instansi sasaran penerima manfaat, batasan kuota porsi harian, dan pemetaan zonasi dapur umum.</p>
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

    <div class="row g-3">
        <div class="col-xl-4 col-lg-5 col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white px-3 py-2.5" style="background-color: #304674;">
                    <h5 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi <?= $edit_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"></i>
                        <?= $edit_mode ? 'Ubah Data Sekolah' : 'Registrasi Sekolah Baru' ?>
                    </h5>
                </div>
                <div class="card-body p-3">
                    <form action="/mbg-app/actions/admin/master_sekolah.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="form_action" value="<?= $edit_mode ? 'update' : 'create' ?>">
                        
                        <div class="mb-2.5">
                            <label for="id_sekolah" class="form-label text-dark small fw-bold mb-1">NPSN / ID Sekolah</label>
                            <input type="text" class="form-control form-control-sm" id="id_sekolah" name="id_sekolah" 
                                   value="<?= htmlspecialchars($edit_data['id_sekolah']) ?>" 
                                   <?= $edit_mode ? 'readonly style="background-color: #e9ecef;"' : '' ?> 
                                   maxlength="20" required placeholder="Masukkan NPSN sekolah">
                            <div class="invalid-feedback">NPSN / ID Sekolah wajib diisi (Maksimal 20 Karakter).</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="nama_sekolah" class="form-label text-dark small fw-bold mb-1">Nama Instansi Sekolah</label>
                            <input type="text" class="form-control form-control-sm" id="nama_sekolah" name="nama_sekolah" 
                                   value="<?= htmlspecialchars($edit_data['nama_sekolah']) ?>" 
                                   maxlength="100" required placeholder="Contoh: SDN 01 Turen">
                            <div class="invalid-feedback">Nama sekolah wajib diisi.</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="jml_siswa" class="form-label text-dark small fw-bold mb-1">Jumlah Siswa Aktif (Kuota Porsi)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="jml_siswa" name="jml_siswa" 
                                       value="<?= htmlspecialchars($edit_data['jml_siswa']) ?>" 
                                       min="0" required placeholder="0">
                                <span class="input-group-text bg-light text-muted small">Anak / Boks</span>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem; line-height: 1.2;">Dikunci sebagai batas kuota distribusi harian maksimal.</small>
                            <div class="invalid-feedback">Jumlah kuota siswa harus berupa angka valid.</div>
                        </div>

                        <div class="mb-2.5">
                            <label for="id_dapur" class="form-label text-dark small fw-bold mb-1">
                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i>Mapping Dapur Umum Penanggung Jawab
                            </label>
                            <select class="form-select form-select-sm" id="id_dapur" name="id_dapur">
                                <option value="">-- Belum Dipetakan (Zonasi Dinas) --</option>
                                <?php foreach ($dapur_options as $dapur): ?>
                                    <option value="<?= $dapur['id_dapur'] ?>" <?= ($edit_data['id_dapur'] == $dapur['id_dapur']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dapur['nama_dapur']) ?> (<?= htmlspecialchars($dapur['id_dapur']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label text-dark small fw-bold mb-1">Alamat Lengkap</label>
                            <textarea class="form-control form-control-sm" id="alamat" name="alamat" rows="2" placeholder="Tuliskan nama jalan, kecamatan, kabupaten..."><?= htmlspecialchars($edit_data['alamat']) ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm text-white fw-bold d-flex align-items-center justify-content-center" style="background-color: #304674;">
                                <i class="bi bi-cursor-fill me-1.5"></i> Simpan Data
                            </button>
                            <?php if ($edit_mode): ?>
                                <a href="sekolah.php" class="btn btn-sm btn-outline-secondary fw-bold d-flex align-items-center justify-content-center">
                                    <i class="bi bi-x-circle me-1.5"></i> Batalkan Edit
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
                        <i class="bi bi-table me-2" style="color: #304674;"></i>Daftar Sekolah Sasaran & Alokasi Zonasi
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 small">
                            <thead class="text-white" style="background-color: #243558; font-size: 0.82rem;">
                                <tr>
                                    <th class="px-3 py-2.5" style="width: 15%">ID / NPSN</th>
                                    <th class="py-2.5" style="width: 30%">Nama Sekolah</th>
                                    <th class="py-2.5" style="width: 15%">Kuota Siswa</th>
                                    <th class="py-2.5" style="width: 25%">Dapur Penanggung Jawab</th>
                                    <th class="py-2.5 text-center" style="width: 15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sekolah_data)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> Belum ada rekaman data instansi sekolah.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sekolah_data as $row): ?>
                                        <tr>
                                            <td class="px-3 fw-bold text-secondary"><?= htmlspecialchars($row['id_sekolah']) ?></td>
                                            <td>
                                                <span class="d-block fw-bold text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($row['nama_sekolah']) ?></span>
                                                <small class="text-muted text-truncate d-inline-block" style="max-width: 220px;" title="<?= htmlspecialchars($row['alamat']) ?>">
                                                    <?= !empty($row['alamat']) ? htmlspecialchars($row['alamat']) : '-' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-2.5 py-1.5 fw-bold" style="background-color: #c6d3e3; color: #243558;">
                                                    <i class="bi bi-people-fill me-1"></i><?= number_format($row['jml_siswa'], 0, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['id_dapur'])): ?>
                                                    <span class="text-dark fw-semibold"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($row['nama_dapur']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger small fw-bold"><i class="bi bi-exclamation-circle me-1"></i>Belum Dipetakan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center px-2">
                                                <div class="btn-group" role="group">
                                                    <a href="sekolah.php?action=edit&id=<?= urlencode($row['id_sekolah']) ?>" 
                                                       class="btn btn-xs btn-outline-primary py-1 px-2 text-decoration-none" style="font-size: 0.78rem;" title="Ubah Data">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    
                                                    <?php if ($row['has_transaction']): ?>
                                                        <button class="btn btn-xs btn-light text-muted border py-1 px-2" style="font-size: 0.78rem;" disabled 
                                                                title="Data terkunci secara permanen, terikat riwayat manifes pengiriman makanan">
                                                            <i class="bi bi-lock-fill text-warning"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="/mbg-app/actions/admin/master_sekolah.php?action=delete&id=<?= urlencode($row['id_sekolah']) ?>" 
                                                           class="btn btn-xs btn-outline-danger py-1 px-2 text-decoration-none" style="font-size: 0.78rem;" 
                                                           onclick="return konfirmasiHapus(event);" title="Hapus Data">
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

<script>
function konfirmasiHapus(event) {
    if (!confirm('Apakah Anda yakin ingin menghapus data instansi sekolah ini secara permanen?')) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>

<?php
// Memanggil komponen footer layouting secara aman
include_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/views/templates/footer.php';
?>