<?php
// =========================================================================
// ACTIONS BACKEND: PEMROSES LOGIKA BISNIS CRUD DATA MASTER SEKOLAH
// =========================================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Hak Akses (RBAC) - Sesuai aturan bisnis multi-actor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../index.php?pesan=denied");
    exit();
}

require_once '../../config/database.php';

// MENANGANI OPERASI POST (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action   = isset($_POST['form_action']) ? trim($_POST['form_action']) : '';
    $id_sekolah    = isset($_POST['id_sekolah']) ? trim($_POST['id_sekolah']) : '';
    $nama_sekolah  = isset($_POST['nama_sekolah']) ? trim($_POST['nama_sekolah']) : '';
    $jml_siswa     = isset($_POST['jml_siswa']) ? intval($_POST['jml_siswa']) : 0;
    $id_dapur      = (isset($_POST['id_dapur']) && $_POST['id_dapur'] !== '') ? trim($_POST['id_dapur']) : null;
    $alamat        = isset($_POST['alamat']) ? trim($_POST['alamat']) : null;

    // Validasi basic server-side jika ada manipulasi bypass client-side
    if (empty($id_sekolah) || empty($nama_sekolah)) {
        $_SESSION['gagal'] = "Gagal memproses data! Input NPSN/ID dan nama sekolah bersifat mandatori.";
        header("Location: ../../views/admin/master/sekolah.php");
        exit();
    }

    // --- PROSES TAMBAH DATA BARU (CREATE) ---
    if ($form_action === 'create') {
        // Cek Keunikan ID/NPSN: Aturan bisnis 2 (Kode identifikasi unik wajib tidak duplikat)
        $stmt_check = $conn->prepare("SELECT id_sekolah FROM sekolah WHERE id_sekolah = ?");
        $stmt_check->bind_param("s", $id_sekolah);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['gagal'] = "Gagal mendaftarkan sekolah! Kode ID/NPSN '<strong>" . htmlspecialchars($id_sekolah) . "</strong>' sudah terdaftar di sistem.";
            $stmt_check->close();
            header("Location: ../../views/admin/master/sekolah.php");
            exit();
        }
        $stmt_check->close();

        // Eksekusi insert data baru
        $stmt_insert = $conn->prepare("INSERT INTO sekolah (id_sekolah, nama_sekolah, alamat, jml_siswa, id_dapur) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssis", $id_sekolah, $nama_sekolah, $alamat, $jml_siswa, $id_dapur);

        if ($stmt_insert->execute()) {
            $_SESSION['sukses'] = "Sekolah <strong>" . htmlspecialchars($nama_sekolah) . "</strong> berhasil didaftarkan dan dialokasikan ke zonasi.";
        } else {
            $_SESSION['gagal'] = "Gagal menyimpan data ke database akibat kegagalan query internal.";
        }
        $stmt_insert->close();
    } 
    
    // --- PROSES UBAH DATA (UPDATE) ---
    elseif ($form_action === 'update') {
        $stmt_update = $conn->prepare("UPDATE sekolah SET nama_sekolah = ?, alamat = ?, jml_siswa = ?, id_dapur = ? WHERE id_sekolah = ?");
        $stmt_update->bind_param("ssiss", $nama_sekolah, $alamat, $jml_siswa, $id_dapur, $id_sekolah);

        if ($stmt_update->execute()) {
            $_SESSION['sukses'] = "Pembaruan informasi data sekolah <strong>" . htmlspecialchars($nama_sekolah) . "</strong> berhasil disimpan.";
        } else {
            $_SESSION['gagal'] = "Sistem gagal memperbarui data sekolah.";
        }
        $stmt_update->close();
    }

    header("Location: ../../views/admin/master/sekolah.php");
    exit();
}

// MENANGANI OPERASI GET (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id_sekolah = isset($_GET['id']) ? trim($_GET['id']) : '';

    if (empty($id_sekolah)) {
        $_SESSION['gagal'] = "ID Target tidak valid untuk memproses aksi hapus.";
        header("Location: ../../views/admin/master/sekolah.php");
        exit();
    }

    // --- IMPLEMENTASI RESTRICT DELETE RULE (ATURAN BISNIS 2) ---
    // Penghapusan ditolak mutlak jika data sekolah sudah berelasi dengan riwayat pengantaran makanan
    $stmt_guard = $conn->prepare("SELECT id_kirim FROM pengiriman WHERE id_sekolah = ? LIMIT 1");
    $stmt_guard->bind_param("s", $id_sekolah);
    $stmt_guard->execute();
    $stmt_guard->store_result();

    if ($stmt_guard->num_rows > 0) {
        $_SESSION['gagal'] = "<strong>Restriksi Keamanan Gagal:</strong> Sekolah tidak dapat dihapus karena sudah memiliki riwayat rekam transaksi pengiriman logistik boks makanan!";
        $stmt_guard->close();
        header("Location: ../../views/admin/master/sekolah.php");
        exit();
    }
    $stmt_guard->close();

    // Eksekusi hapus jika aman (Lolos Restrict Guardrail)
    $stmt_delete = $conn->prepare("DELETE FROM sekolah WHERE id_sekolah = ?");
    $stmt_delete->bind_param("s", $id_sekolah);

    if ($stmt_delete->execute()) {
        $_SESSION['sukses'] = "Data sekolah berhasil dihapus permanen dari master data global.";
    } else {
        $_SESSION['gagal'] = "Gagal memproses penghapusan data akibat kendala relasi database.";
    }
    $stmt_delete->close();

    header("Location: ../../views/admin/master/sekolah.php");
    exit();
}

// Redirect paksa jika ada akses langsung non-prosedural ke file action
header("Location: ../../views/admin/master/sekolah.php");
exit();