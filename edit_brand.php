<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_brand.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
$nama_brand = isset($_POST['nama_brand']) ? trim($_POST['nama_brand']) : '';

if ($brand_id <= 0) {
    header('Location: data_brand.php?error=' . urlencode('ID brand tidak valid'));
    exit();
}

if (empty($nama_brand)) {
    header('Location: data_brand.php?error=' . urlencode('Nama brand tidak boleh kosong'));
    exit();
}

if (strlen($nama_brand) > 100) {
    header('Location: data_brand.php?error=' . urlencode('Nama brand maksimal 100 karakter'));
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT brand_id, nama_brand FROM Brand WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data brand tidak ditemukan");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT brand_id FROM Brand WHERE nama_brand = ? AND brand_id != ?");
    $stmt->bind_param("si", $nama_brand, $brand_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Nama brand sudah ada. Silakan gunakan nama lain.");
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE Brand SET nama_brand = ? WHERE brand_id = ?");
    $stmt->bind_param("si", $nama_brand, $brand_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate brand. Tidak ada perubahan data.");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_brand.php?success=' . urlencode('Brand "' . $nama_brand . '" berhasil diupdate'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_brand.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

