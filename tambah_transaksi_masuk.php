<?php

require_once 'config.php';
require_once 'functions_inventory_calculation.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: barang_masuk.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
$supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
$jumlah = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$tanggal_pesan = isset($_POST['tanggal_pesan']) ? trim($_POST['tanggal_pesan']) : '';
$tanggal = isset($_POST['date']) ? trim($_POST['date']) : '';
$harga_beli = isset($_POST['harga_beli']) ? (float)$_POST['harga_beli'] : 0;
$tgl_kadarluarsa = isset($_POST['tgl_kadarluarsa']) && !empty($_POST['tgl_kadarluarsa']) ? trim($_POST['tgl_kadarluarsa']) : null;

if ($barang_id <= 0) {
    header('Location: barang_masuk.php?error=' . urlencode('Pilih barang terlebih dahulu'));
    exit();
}

if ($supplier_id <= 0) {
    header('Location: barang_masuk.php?error=' . urlencode('Pilih supplier terlebih dahulu'));
    exit();
}

if ($jumlah <= 0) {
    header('Location: barang_masuk.php?error=' . urlencode('Quantity harus lebih dari 0'));
    exit();
}

if (empty($tanggal_pesan)) {
    header('Location: barang_masuk.php?error=' . urlencode('Pilih tanggal pemesanan terlebih dahulu'));
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_pesan)) {
    header('Location: barang_masuk.php?error=' . urlencode('Format tanggal pemesanan tidak valid'));
    exit();
}

if (empty($tanggal)) {
    header('Location: barang_masuk.php?error=' . urlencode('Pilih tanggal barang diterima terlebih dahulu'));
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    header('Location: barang_masuk.php?error=' . urlencode('Format tanggal barang diterima tidak valid'));
    exit();
}

// Validasi: tanggal_pesan tidak boleh lebih besar dari tanggal diterima
if ($tanggal_pesan > $tanggal) {
    header('Location: barang_masuk.php?error=' . urlencode('Tanggal pemesanan tidak boleh lebih besar dari tanggal barang diterima'));
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT barang_id, nama_barang FROM Barang WHERE barang_id = ?");
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Barang tidak ditemukan");
    }
    
    $barang = $result->fetch_assoc();
    $nama_barang = $barang['nama_barang'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT supplier_id, nama_supplier FROM Supplier WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Supplier tidak ditemukan");
    }
    
    $stmt->close();

    $tanggal_pesan_datetime = $tanggal_pesan . ' ' . date('H:i:s');
    $tanggal_datetime = $tanggal . ' ' . date('H:i:s');

    $stmt = $conn->prepare("INSERT INTO TransaksiMasuk (supplier_id, user_id, tanggal_pesan, tanggal) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $supplier_id, $user_id, $tanggal_pesan_datetime, $tanggal_datetime);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan transaksi masuk");
    }
    
    $masuk_id = $conn->insert_id;
    $stmt->close();

    if ($tgl_kadarluarsa) {
        $stmt = $conn->prepare("INSERT INTO DetailMasuk (masuk_id, barang_id, jumlah, harga_beli, tgl_kadarluarsa) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiids", $masuk_id, $barang_id, $jumlah, $harga_beli, $tgl_kadarluarsa);
    } else {
        $stmt = $conn->prepare("INSERT INTO DetailMasuk (masuk_id, barang_id, jumlah, harga_beli) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $masuk_id, $barang_id, $jumlah, $harga_beli);
    }
    
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan detail transaksi");
    }
    
    $stmt->close();

    $stmt = $conn->prepare("UPDATE Barang SET stok = stok + ? WHERE barang_id = ?");
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
    
    header('Location: barang_masuk.php?success=' . urlencode('Transaksi untuk "' . $nama_barang . '" berhasil ditambahkan. Stok barang telah ditambah.'));
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

