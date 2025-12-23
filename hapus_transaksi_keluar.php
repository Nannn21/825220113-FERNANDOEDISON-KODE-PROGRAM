<?php

require_once 'config.php';
require_once 'functions_inventory_calculation.php';
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: barang_keluar.php?error=' . urlencode('ID transaksi tidak valid'));
    exit();
}

$detailkeluar_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT dk.detailkeluar_id, dk.keluar_id, dk.barang_id, dk.jumlah, b.nama_barang, b.stok
        FROM DetailKeluar dk
        INNER JOIN Barang b ON dk.barang_id = b.barang_id
        WHERE dk.detailkeluar_id = ?
    ");
    $stmt->bind_param("i", $detailkeluar_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data transaksi tidak ditemukan");
    }
    
    $transaksi = $result->fetch_assoc();
    $keluar_id = (int)$transaksi['keluar_id'];
    $barang_id = (int)$transaksi['barang_id'];
    $jumlah = (int)$transaksi['jumlah'];
    $nama_barang = $transaksi['nama_barang'];
    $stmt->close();

    $stmt = $conn->prepare("UPDATE Barang SET stok = stok + ? WHERE barang_id = ?");
    $stmt->bind_param("ii", $jumlah, $barang_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengembalikan stok barang");
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM DetailKeluar WHERE detailkeluar_id = ?");
    $stmt->bind_param("i", $detailkeluar_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus transaksi");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM DetailKeluar WHERE keluar_id = ?");
    $stmt->bind_param("i", $keluar_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $remaining_details = $count_result->fetch_assoc()['total'];
    $stmt->close();

    if ($remaining_details == 0) {
        $stmt = $conn->prepare("DELETE FROM TransaksiKeluar WHERE keluar_id = ?");
        $stmt->bind_param("i", $keluar_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    try {
        updateSafetyStockROP($barang_id);
    } catch (Exception $e) {
        
        error_log("Error updating Safety Stock & ROP for barang_id {$barang_id}: " . $e->getMessage());
    }
    
    header('Location: barang_keluar.php?success=' . urlencode('Transaksi untuk "' . $nama_barang . '" berhasil dihapus. Stok barang telah dikembalikan.'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: barang_keluar.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

