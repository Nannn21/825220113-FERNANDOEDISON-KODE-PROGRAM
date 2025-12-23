<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kategori_barang.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$kategori_id = isset($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : 0;
$nama_kategori = isset($_POST['nama_kategori']) ? trim($_POST['nama_kategori']) : '';

if ($kategori_id <= 0) {
    header('Location: kategori_barang.php?error=' . urlencode('ID kategori tidak valid'));
    exit();
}

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

    $stmt = $conn->prepare("SELECT kategori_id, nama_kategori FROM KategoriBarang WHERE kategori_id = ?");
    $stmt->bind_param("i", $kategori_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data kategori tidak ditemukan");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT kategori_id FROM KategoriBarang WHERE nama_kategori = ? AND kategori_id != ?");
    $stmt->bind_param("si", $nama_kategori, $kategori_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Nama kategori sudah ada. Silakan gunakan nama lain.");
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE KategoriBarang SET nama_kategori = ? WHERE kategori_id = ?");
    $stmt->bind_param("si", $nama_kategori, $kategori_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate kategori. Tidak ada perubahan data.");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: kategori_barang.php?success=' . urlencode('Kategori "' . $nama_kategori . '" berhasil diupdate'));
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

