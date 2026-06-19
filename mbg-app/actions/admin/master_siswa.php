<?php
// =========================================================================
// ACTIONS ENGINE: CODE PROCESSING MASTER DATA SISWA & MASS IMPORT PARSER
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/security.php';
cekRole(['Admin']);
require_once $_SERVER['DOCUMENT_ROOT'] . '/mbg-app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['scope_action'])) {
    header("Location: /mbg-app/views/admin/master/siswa.php");
    exit();
}

$scope = isset($_POST['scope_action']) ? $_POST['scope_action'] : (isset($_GET['scope_action']) ? $_GET['scope_action'] : '');

switch ($scope) {

    // -------------------------------------------------------------------------
    // CASE: PROSES SIMPAN / UPDATE INDIVIDU MANUAL
    // -------------------------------------------------------------------------
    case 'siswa_process':
        $action = $_POST['form_action'];
        $nisn = trim($conn->real_escape_string($_POST['nisn']));
        $nama_siswa = trim($conn->real_escape_string($_POST['nama_siswa']));
        $kelas = trim($conn->real_escape_string($_POST['kelas']));
        $id_sekolah = trim($conn->real_escape_string($_POST['id_sekolah']));

        if (empty($nisn) || empty($nama_siswa) || empty($kelas) || empty($id_sekolah)) {
            $_SESSION['gagal'] = "Parameter input tidak lengkap. Seluruh data wajib diisi.";
            header("Location: /mbg-app/views/admin/master/siswa.php");
            exit();
        }

        if ($action === 'create') {
            $check_dup = $conn->query("SELECT nisn FROM siswa WHERE nisn = '$nisn' LIMIT 1");
            if ($check_dup && $check_dup->num_rows > 0) {
                $_SESSION['gagal'] = "Gagal! NISN [ $nisn ] sudah terdaftar pada siswa lain.";
            } else {
                $query = "INSERT INTO siswa (nisn, nama_siswa, kelas, id_sekolah) VALUES ('$nisn', '$nama_siswa', '$kelas', '$id_sekolah')";
                if ($conn->query($query)) {
                    $_SESSION['sukses'] = "Siswa baru bernama [ $nama_siswa ] berhasil diregistrasikan.";
                } else {
                    $_SESSION['gagal'] = "Kegagalan sistem internal saat mendaftarkan data siswa.";
                }
            }
        } elseif ($action === 'update') {
            $query = "UPDATE siswa SET nama_siswa = '$nama_siswa', kelas = '$kelas', id_sekolah = '$id_sekolah' WHERE nisn = '$nisn'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Profil biodata siswa NISN [ $nisn ] berhasil diperbarui.";
            } else {
                $_SESSION['gagal'] = "Gagal memperbarui rekam biodata siswa.";
            }
        }
        break;

    // -------------------------------------------------------------------------
    // CASE: PROSES PARSING MASSAL BERKAS CSV (NATIVE BULK INSERT)
    // -------------------------------------------------------------------------
    case 'siswa_import':
        if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['gagal'] = "Mohon unggah berkas CSV data siswa yang valid.";
            header("Location: /mbg-app/views/admin/master/siswa.php");
            exit();
        }

        $file_name = $_FILES['file_excel']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $_SESSION['gagal'] = "Format ditolak! Sistem hanya menerima ekstensi file koma terpisah (.csv).";
            header("Location: /mbg-app/views/admin/master/siswa.php");
            exit();
        }

        $file_handle = fopen($_FILES['file_excel']['tmp_name'], 'r');
        // Lewati baris pertama jika file CSV Anda menyertakan header kolom
        fgetcsv($file_handle, 1000, ",");

        $inserted = 0;
        $skipped = 0;

        $conn->begin_transaction();
        try {
            while (($row = fgetcsv($file_handle, 1000, ",")) !== FALSE) {
                if (count($row) < 4) continue;

                $nisn = trim($conn->real_escape_string($row[0]));
                $nama_siswa = trim($conn->real_escape_string($row[1]));
                $kelas = trim($conn->real_escape_string($row[2]));
                $id_sekolah = trim($conn->real_escape_string($row[3]));

                if (empty($nisn) || empty($nama_siswa) || empty($id_sekolah)) {
                    $skipped++;
                    continue;
                }

                // Validasi keberadaan ID Sekolah dan cek duplikasi NISN
                $sch_check = $conn->query("SELECT id_sekolah FROM sekolah WHERE id_sekolah = '$id_sekolah' LIMIT 1");
                $dup_check = $conn->query("SELECT nisn FROM siswa WHERE nisn = '$nisn' LIMIT 1");

                if ($sch_check->num_rows > 0 && $dup_check->num_rows == 0) {
                    $conn->query("INSERT INTO siswa (nisn, nama_siswa, kelas, id_sekolah) VALUES ('$nisn', '$nama_siswa', '$kelas', '$id_sekolah')");
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
            fclose($file_handle);
            $conn->commit();
            $_SESSION['sukses'] = "Impor Selesai! Berhasil memasukkan $inserted siswa. ($skipped baris dilewati/data tidak valid).";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['gagal'] = "Terjadi galat saat memproses dokumen data massal.";
        }
        break;

    // -------------------------------------------------------------------------
    // CASE: PROSES HAPUS SISWA DENGAN RESTRICT DELETE RULE
    // -------------------------------------------------------------------------
    case 'delete_siswa':
        $nisn_delete = $conn->real_escape_string($_GET['id']);

        // Proteksi: Cek riwayat kehadiran makan pada tabel presensi_makan
        $check_presence = $conn->query("SELECT id_presensi FROM presensi_makan WHERE id_siswa = '$nisn_delete' LIMIT 1");

        if ($check_presence && $check_presence->num_rows > 0) {
            $_SESSION['gagal'] = "Restrict Delete Rule: Data siswa tidak dapat dihapus karena memiliki rekam presensi makan aktif.";
        } else {
            $query = "DELETE FROM siswa WHERE nisn = '$nisn_delete'";
            if ($conn->query($query)) {
                $_SESSION['sukses'] = "Rekam biodata siswa berhasil dihapus dari sistem.";
            } else {
                $_SESSION['gagal'] = "Gagal menghapus rekam data siswa dari database.";
            }
        }
        break;

    default:
        $_SESSION['gagal'] = "Ruang lingkup pemrosesan data tidak ditemukan.";
        break;
}

header("Location: /mbg-app/views/admin/master/siswa.php");
exit();