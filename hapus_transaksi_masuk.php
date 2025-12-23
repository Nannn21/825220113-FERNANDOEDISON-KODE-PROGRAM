<?php

require_once 'config.php';
require_once 'functions_inventory_calculation.php';
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: barang_masuk.php?error=' . urlencode('ID transaksi tidak valid'));
    exit();
}

$detailmasuk_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT dm.detailmasuk_id, dm.masuk_id, dm.barang_id, dm.jumlah, b.nama_barang, b.stok
        FROM DetailMasuk dm
        INNER JOIN Barang b ON dm.barang_id = b.barang_id
        WHERE dm.detailmasuk_id = ?
    ");
    $stmt->bind_param("i", $detailmasuk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data transaksi tidak ditemukan");
    }
    
    $transaksi = $result->fetch_assoc();
    $masuk_id = (int)$transaksi['masuk_id'];
    $barang_id = (int)$transaksi['barang_id'];
    $jumlah = (int)$transaksi['jumlah'];
    $stok_sekarang = (int)$transaksi['stok'];
    $nama_barang = $transaksi['nama_barang'];
    $stmt->close();

    if ($stok_sekarang < $jumlah) {
        throw new Exception("Stok tidak cukup untuk menghapus transaksi. Stok saat ini: {$stok_sekarang}, Jumlah yang akan dihapus: {$jumlah}");
    }

    $stmt = $conn->prepare("UPDATE Barang SET stok = stok - ? WHERE barang_id = ?");
    $stmt->bind_param("ii", $jumlah, $barang_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengurangi stok barang");
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM DetailMasuk WHERE detailmasuk_id = ?");
    $stmt->bind_param("i", $detailmasuk_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus transaksi");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM DetailMasuk WHERE masuk_id = ?");
    $stmt->bind_param("i", $masuk_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $remaining_details = $count_result->fetch_assoc()['total'];
    $stmt->close();

    if ($remaining_details == 0) {
        $stmt = $conn->prepare("DELETE FROM TransaksiMasuk WHERE masuk_id = ?");
        $stmt->bind_param("i", $masuk_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    try {
        updateSafetyStockROP($barang_id);
    } catch (Exception $e) {
        
        error_log("Error updating Safety Stock & ROP for barang_id {$barang_id}: " . $e->getMessage());
    }
    
    header('Location: barang_masuk.php?success=' . urlencode('Transaksi untuk "' . $nama_barang . '" berhasil dihapus. Stok barang telah dikurangi.'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: barang_masuk.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

