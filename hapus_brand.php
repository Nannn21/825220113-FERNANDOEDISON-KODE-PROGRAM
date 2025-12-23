<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_brand.php?error=' . urlencode('ID brand tidak valid'));
    exit();
}

$brand_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT nama_brand FROM Brand WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data brand tidak ditemukan");
    }
    
    $brand = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Barang WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $barang_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    if ($barang_count > 0) {
        throw new Exception("Brand tidak dapat dihapus karena masih digunakan oleh {$barang_count} barang");
    }

    $stmt = $conn->prepare("DELETE FROM Brand WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus brand");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_brand.php?success=' . urlencode('Brand "' . $brand['nama_brand'] . '" berhasil dihapus'));
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

