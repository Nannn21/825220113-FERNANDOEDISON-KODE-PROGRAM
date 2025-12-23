<?php

require_once 'config.php';
require_once 'functions_inventory_calculation.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: barang_keluar.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
$pelanggan_id = isset($_POST['pelanggan_id']) ? (int)$_POST['pelanggan_id'] : 0;
$jumlah = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$tanggal = isset($_POST['date']) ? trim($_POST['date']) : '';

if ($barang_id <= 0) {
    header('Location: barang_keluar.php?error=' . urlencode('Pilih barang terlebih dahulu'));
    exit();
}

if ($pelanggan_id <= 0) {
    header('Location: barang_keluar.php?error=' . urlencode('Pilih customer terlebih dahulu'));
    exit();
}

if ($jumlah <= 0) {
    header('Location: barang_keluar.php?error=' . urlencode('Quantity harus lebih dari 0'));
    exit();
}

if (empty($tanggal)) {
    header('Location: barang_keluar.php?error=' . urlencode('Pilih tanggal terlebih dahulu'));
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    header('Location: barang_keluar.php?error=' . urlencode('Format tanggal tidak valid'));
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT barang_id, nama_barang, stok, harga_jual FROM Barang WHERE barang_id = ?");
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Barang tidak ditemukan");
    }
    
    $barang = $result->fetch_assoc();
    $nama_barang = $barang['nama_barang'];
    $stok_sekarang = (int)$barang['stok'];
    $harga_jual = (float)$barang['harga_jual'];
    $stmt->close();

    if ($stok_sekarang < $jumlah) {
        throw new Exception("Stok tidak cukup. Stok saat ini: {$stok_sekarang}, Jumlah yang diminta: {$jumlah}");
    }

    $stmt = $conn->prepare("SELECT pelanggan_id, nama_pelanggan FROM Pelanggan WHERE pelanggan_id = ?");
    $stmt->bind_param("i", $pelanggan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Customer tidak ditemukan");
    }
    
    $stmt->close();

    $tanggal_datetime = $tanggal . ' ' . date('H:i:s');

    $stmt = $conn->prepare("INSERT INTO TransaksiKeluar (pelanggan_id, user_id, tanggal) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $pelanggan_id, $user_id, $tanggal_datetime);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan transaksi keluar");
    }
    
    $keluar_id = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO DetailKeluar (keluar_id, barang_id, jumlah, harga_jual) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $keluar_id, $barang_id, $jumlah, $harga_jual);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan detail transaksi");
    }
    
    $stmt->close();

    $stmt = $conn->prepare("UPDATE Barang SET stok = stok - ? WHERE barang_id = ?");
    $stmt->bind_param("ii", $jumlah, $barang_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate stok barang");
    }
    
    $stmt->close();

    $conn->commit();

    try {
        updateSafetyStockROP($barang_id);
    } catch (Exception $e) {
        
        error_log("Error updating Safety Stock & ROP for barang_id {$barang_id}: " . $e->getMessage());
    }
    
    header('Location: barang_keluar.php?success=' . urlencode('Transaksi untuk "' . $nama_barang . '" berhasil ditambahkan. Stok barang telah dikurangi.'));
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

