<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_brand.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_brand = isset($_POST['nama_brand']) ? trim($_POST['nama_brand']) : '';

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

    $stmt = $conn->prepare("SELECT brand_id FROM Brand WHERE nama_brand = ?");
    $stmt->bind_param("s", $nama_brand);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Nama brand sudah ada. Silakan gunakan nama lain.");
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO Brand (nama_brand) VALUES (?)");
    $stmt->bind_param("s", $nama_brand);
    $stmt->execute();
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_brand.php?success=' . urlencode('Brand "' . $nama_brand . '" berhasil ditambahkan'));
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

