<?php
// =========================================================================
// CONFIG HELPER: DYNAMIC REAL-TIME INVENTORY STOCK CALCULATOR
// =========================================================================

/**
 * Menghitung sisa kuantitas stok bahan baku riil pada gudang unit dapur tertentu.
 * Rumus: Total Pasokan Terverifikasi - Akumulasi Kebutuhan Bahan Menu Terkirim
 */
function hitungStokRiil($conn, $id_dapur, $id_bahan) {
    
    // A. Hitung Total Pasokan Bahan Makanan yang Telah Disetujui (Verifikasi Hulu)
    $query_pasokan = "SELECT SUM(jumlah) as total_masuk 
                      FROM pasokan_bahan 
                      WHERE id_dapur = ? AND id_bahan = ? AND status = 'Disetujui'";
    $stmt_p = $conn->prepare($query_pasokan);
    $stmt_p->bind_param("ss", $id_dapur, $id_bahan);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result()->fetch_assoc();
    $total_pasokan_terverifikasi = floatval($res_p['total_masuk'] ?? 0.00);
    $stmt_p->close();

    // B. Hitung Akumulasi Kebutuhan Bahan Baku dari Resep Menu yang Telah Dikirim (Hilir)
    $query_kebutuhan = "SELECT SUM(p.jml_porsi * dm.jumlah_takaran) as total_keluar
                        FROM pengiriman p
                        JOIN detail_menu dm ON p.id_menu = dm.id_menu
                        WHERE p.id_dapur = ? AND dm.id_bahan = ?";
    $stmt_k = $conn->prepare($query_kebutuhan);
    $stmt_k->bind_param("ss", $id_dapur, $id_bahan);
    $stmt_k->execute();
    $res_k = $stmt_k->get_result()->fetch_assoc();
    $total_kebutuhan_terkirim = floatval($res_k['total_keluar'] ?? 0.00);
    $stmt_k->close();
    
    // C. Kalkulasi Dinamis Selisih Stok Aktual Bersih Gudang
    $stok_riil = $total_pasokan_terverifikasi - $total_kebutuhan_terkirim;
    
    return (float) ($stok_riil > 0 ? $stok_riil : 0.00);
}

/**
 * Memeriksa status ambang batas minimum stok global untuk memicu bendera peringatan visual.
 */
function cekAlertStok($conn, $id_dapur, $id_bahan) {
    
    $stok_riil = hitungStokRiil($conn, $id_dapur, $id_bahan);
    
    // Ambil parameter nilai batas aman (stok_min) dari master tabel bahan baku
    $query_min = "SELECT stok_min FROM bahan_baku WHERE id_bahan = ? LIMIT 1";
    $stmt = $conn->prepare($query_min);
    $stmt->bind_param("s", $id_bahan);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stok_min = floatval($res['stok_min'] ?? 0.00);
    $stmt->close();
    
    if ($stok_riil <= $stok_min) {
        return true; // Memicu flag peringatan visual restock pada dashboard dapur
    }
    
    return false;
}
?>