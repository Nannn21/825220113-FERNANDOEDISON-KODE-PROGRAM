<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_supplier.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_supplier = isset($_POST['nama_supplier']) ? trim($_POST['nama_supplier']) : '';
$kontak_supplier = isset($_POST['kontak_supplier']) ? trim($_POST['kontak_supplier']) : '';

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

    $stmt = $conn->prepare("INSERT INTO Supplier (nama_supplier, kontak_supplier) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama_supplier, $kontak_supplier);
    $stmt->execute();
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_supplier.php?success=' . urlencode('Supplier "' . $nama_supplier . '" berhasil ditambahkan'));
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

