<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_supplier.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
$nama_supplier = isset($_POST['nama_supplier']) ? trim($_POST['nama_supplier']) : '';
$kontak_supplier = isset($_POST['kontak_supplier']) ? trim($_POST['kontak_supplier']) : '';

if ($supplier_id <= 0) {
    header('Location: data_supplier.php?error=' . urlencode('ID supplier tidak valid'));
    exit();
}

if (empty($nama_supplier)) {
    header('Location: data_supplier.php?error=' . urlencode('Nama supplier tidak boleh kosong'));
    exit();
}

if (strlen($nama_supplier) > 100) {
    header('Location: data_supplier.php?error=' . urlencode('Nama supplier maksimal 100 karakter'));
    exit();
}

if (empty($kontak_supplier)) {
    header('Location: data_supplier.php?error=' . urlencode('Kontak supplier tidak boleh kosong'));
    exit();
}

if (!ctype_digit($kontak_supplier)) {
    header('Location: data_supplier.php?error=' . urlencode('Kontak supplier harus berupa angka'));
    exit();
}

if (strlen($kontak_supplier) < 11) {
    header('Location: data_supplier.php?error=' . urlencode('Kontak supplier minimal 11 angka'));
    exit();
}

if (strlen($kontak_supplier) > 20) {
    header('Location: data_supplier.php?error=' . urlencode('Kontak supplier maksimal 20 angka'));
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT supplier_id, nama_supplier, kontak_supplier FROM Supplier WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data supplier tidak ditemukan");
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE Supplier SET nama_supplier = ?, kontak_supplier = ? WHERE supplier_id = ?");
    $stmt->bind_param("ssi", $nama_supplier, $kontak_supplier, $supplier_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate supplier. Tidak ada perubahan data.");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_supplier.php?success=' . urlencode('Supplier "' . $nama_supplier . '" berhasil diupdate'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_supplier.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

