<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kategori_barang.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_kategori = isset($_POST['nama_kategori']) ? trim($_POST['nama_kategori']) : '';

if (empty($nama_kategori)) {
    header('Location: kategori_barang.php?error=' . urlencode('Nama kategori tidak boleh kosong'));
    exit();
}

if (strlen($nama_kategori) > 50) {
    header('Location: kategori_barang.php?error=' . urlencode('Nama kategori maksimal 50 karakter'));
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT kategori_id FROM KategoriBarang WHERE nama_kategori = ?");
    $stmt->bind_param("s", $nama_kategori);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Nama kategori sudah ada. Silakan gunakan nama lain.");
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO KategoriBarang (nama_kategori) VALUES (?)");
    $stmt->bind_param("s", $nama_kategori);
    $stmt->execute();
    
    $stmt->close();
    $conn->commit();
    
    header('Location: kategori_barang.php?success=' . urlencode('Kategori "' . $nama_kategori . '" berhasil ditambahkan'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: kategori_barang.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

