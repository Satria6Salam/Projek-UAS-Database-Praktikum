<?php
// =========================================================================
// ACTIONS ENGINE: ENGINE PROCESSING MASTER DATA DAPUR & STAF
// =========================================================================

// Memulai sesi untuk pencatatan flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil file security & database utama menggunakan server-absolute path
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';
cekRole(['Admin']);
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

// Memastikan request diproses via metode yang valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['scope_action'])) {
    header("Location: /mbg-app/views/admin/master/dapur.php");
    exit();
}

// Tangkap instruksi scope wilayah pemrosesan
$scope = isset($_POST['scope_action']) ? $_POST['scope_action'] : (isset($_GET['scope_action']) ? $_GET['scope_action'] : '');

switch ($scope) {
    
    // -------------------------------------------------------------------------
    // CASE 1: PROSES VALIDASI & CRUD UNTUK DATA PRASARANA DAPUR UMUM
    // -------------------------------------------------------------------------
    case 'dapur_process':
        $action = $_POST['form_action'];
        $id_dapur = strtoupper(trim($conn->real_escape_string($_POST['id_dapur'])));
        $nama_dapur = trim($conn->real_escape_string($_POST['nama_dapur']));
        $kapasitas = intval($_POST['kapasitas']);
        $lokasi = trim($conn->real_escape_string($_POST['lokasi']));

        if (empty($id_dapur) || empty($nama_dapur) || $kapasitas < 0) {
            $_SESSION['gagal'] = "Gagal memproses data! Parameter input wajib tidak boleh dikosongkan.";
            header("Location: /mbg-app/views/admin/master/dapur.php");
            exit();
        }

        if ($action === 'create') {
            // Cek duplikasi identitas primer unik database
            $check_dup = $conn->query("SELECT id_dapur FROM dapur_umum WHERE id_dapur = '$id_dapur' LIMIT 1");
            if ($check_dup && $check_dup->num_rows > 0) {
                $_SESSION['gagal'] = "Registrasi gagal! ID Dapur Umum [ $id_dapur ] sudah terdaftar di sistem ekosistem.";
            } else {
                $query = "INSERT INTO dapur_umum (id_dapur, nama_dapur, lokasi, kapasitas) VALUES ('$id_dapur', '$nama_dapur', '$lokasi', $kapasitas)";
                if ($conn->query($query)) {
                    $_SESSION['sukses'] = "Unit Dapur Umum [ $nama_dapur ] berhasil didaftarkan secara permanen.";
                } else {
                    $_SESSION['gagal'] = "Terjadi kegagalan internal sistem database saat menyimpan data.";
                }
            }
        } elseif ($action === 'update') {
            $query = "UPDATE dapur_umum SET nama_dapur = '$nama_dapur', lokasi = '$lokasi', kapasitas = $kapasitas WHERE id_dapur = '$id_dapur'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Data prasarana [ $id_dapur ] sukses diperbarui.";
            } else {
                $_SESSION['gagal'] = "Gagal memodifikasi rekam data prasarana dapur.";
            }
        }
        break;

    case 'delete_dapur':
        $id_delete = $conn->real_escape_string($_GET['id']);
        
        // 1. CEK RELASI HILIR: Apakah dapur memiliki transaksi logistik hulu/hilir?
        $check_pasokan = $conn->query("SELECT id_pasokan FROM pasokan_bahan WHERE id_dapur = '$id_delete' LIMIT 1");
        $check_kirim = $conn->query("SELECT id_kirim FROM pengiriman WHERE id_dapur = '$id_delete' LIMIT 1");
        
        // 2. CEK RELASI INTERNAL: Apakah ada staf di dapur ini yang sudah punya akun pengguna?
        $check_staf_user = $conn->query("SELECT p.id_user FROM pengguna p 
                                        INNER JOIN staf_dapur sd ON p.id_staf = sd.id_staf 
                                        WHERE sd.id_dapur = '$id_delete' LIMIT 1");

        // 3. CEK RELASI PEMETAAN WILAYAH: Apakah dapur ini masih ditugaskan melayani sekolah sasaran?
        $check_sekolah = $conn->query("SELECT id_sekolah FROM sekolah WHERE id_dapur = '$id_delete' LIMIT 1");
        
        if (($check_pasokan && $check_pasokan->num_rows > 0) || ($check_kirim && $check_kirim->num_rows > 0)) {
            $_SESSION['gagal'] = "Operasi ditolak! Prasarana Dapur memiliki riwayat rekam transaksi aktif di tabel logistik.";
        } 
        elseif ($check_staf_user && $check_staf_user->num_rows > 0) {
            $_SESSION['gagal'] = "Operasi gagal! Beberapa staf di unit dapur ini telah terdaftar sebagai akun pengguna aktif. Hapus akun pengguna mereka terlebih dahulu.";
        } 
        // Penanganan filter pengunci relasi sekolah sasaran (Mencegah Database Crash)
        elseif ($check_sekolah && $check_sekolah->num_rows > 0) {
            $_SESSION['gagal'] = "Penghapusan dicegah! Unit dapur ini masih terikat sebagai pemasok/pelayan aktif untuk sekolah sasaran. Batalkan pemetaan dapur pada menu master sekolah terlebih dahulu.";
        }
        else {
            // Jika lolos semua validasi guardrail, hapus data staf non-aktif terlebih dahulu, lalu hapus unit dapur
            $conn->query("DELETE FROM staf_dapur WHERE id_dapur = '$id_delete'");
            
            $query = "DELETE FROM dapur_umum WHERE id_dapur = '$id_delete'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Rekam unit dapur umum regional berhasil dihapus total dari database.";
            } else {
                $_SESSION['gagal'] = "Gagal memproses penghapusan data prasarana.";
            }
        }
        break;

    // -------------------------------------------------------------------------
    // CASE 2: PROSES VALIDASI & CRUD UNTUK STRUKTUR STAF DAPUR
    // -------------------------------------------------------------------------
    case 'staf_process':
        $action = $_POST['form_action'];
        $id_staf = strtoupper(trim($conn->real_escape_string($_POST['id_staf'])));
        $id_dapur = trim($conn->real_escape_string($_POST['id_dapur']));
        $nama_staf = trim($conn->real_escape_string($_POST['nama_staf']));
        $peran = trim($conn->real_escape_string($_POST['peran']));
        $no_telp = trim($conn->real_escape_string($_POST['no_telp']));

        if (empty($id_staf) || empty($id_dapur) || empty($nama_staf) || empty($peran)) {
            $_SESSION['gagal'] = "Gagal memproses penempatan! Data input wajib belum terpenuhi.";
            header("Location: /mbg-app/views/admin/master/dapur.php");
            exit();
        }

        if ($action === 'create') {
            $check_dup = $conn->query("SELECT id_staf FROM staf_dapur WHERE id_staf = '$id_staf' LIMIT 1");
            if ($check_dup && $check_dup->num_rows > 0) {
                $_SESSION['gagal'] = "Registrasi gagal! ID Staf / NIP [ $id_staf ] sudah terikat dengan pegawai lain.";
            } else {
                $query = "INSERT INTO staf_dapur (id_staf, id_dapur, nama_staf, peran, no_telp) VALUES ('$id_staf', '$id_dapur', '$nama_staf', '$peran', '$no_telp')";
                if ($conn->query($query)) {
                    $_SESSION['sukses'] = "Pegawai baru [ $nama_staf ] berhasil dipetakan ke unit dapur terkait.";
                } else {
                    $_SESSION['gagal'] = "Gagal menyimpan rekam data penempatan pegawai.";
                }
            }
        } elseif ($action === 'update') {
            $query = "UPDATE staf_dapur SET id_dapur = '$id_dapur', nama_staf = '$nama_staf', peran = '$peran', no_telp = '$no_telp' WHERE id_staf = '$id_staf'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Profil struktur kepegawaian [ $id_staf ] berhasil dimodifikasi.";
            } else {
                $_SESSION['gagal'] = "Gagal merubah data profil struktur staf.";
            }
        }
        break;

    case 'delete_staf':
        $id_delete = $conn->real_escape_string($_GET['id']);
        $check_user = $conn->query("SELECT id_user FROM pengguna WHERE id_staf = '$id_delete' LIMIT 1");
        
        if ($check_user && $check_user->num_rows > 0) {
            $_SESSION['gagal'] = "Penghapusan dicegah! Identitas staf ini terkunci karena terikat aktif sebagai kredensial akun pengguna sistem.";
        } else {
            $query = "DELETE FROM staf_dapur WHERE id_staf = '$id_delete'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Data kepegawaian staf berhasil dibersihkan dari penempatan unit kerja.";
            } else {
                $_SESSION['gagal'] = "Terjadi kendala saat menghapus records kepegawaian.";
            }
        }
        break;

    default:
        $_SESSION['gagal'] = "Ruang lingkup tindakan operasional tidak dikenali oleh sistem.";
        break;
}

// Kembalikan alur kerja halaman secara aman menuju antarmuka utama
header("Location: /mbg-app/views/admin/master/dapur.php");
exit();