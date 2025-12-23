<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: kategori_barang.php?error=' . urlencode('ID kategori tidak valid'));
    exit();
}

$kategori_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT nama_kategori FROM KategoriBarang WHERE kategori_id = ?");
    $stmt->bind_param("i", $kategori_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data kategori tidak ditemukan");
    }
    
    $kategori = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Barang WHERE kategori_id = ?");
    $stmt->bind_param("i", $kategori_id);
    $stmt->execute();
    $barang_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    if ($barang_count > 0) {
        throw new Exception("Kategori tidak dapat dihapus karena masih digunakan oleh {$barang_count} barang");
    }

    $stmt = $conn->prepare("DELETE FROM KategoriBarang WHERE kategori_id = ?");
    $stmt->bind_param("i", $kategori_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus kategori");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: kategori_barang.php?success=' . urlencode('Kategori "' . $kategori['nama_kategori'] . '" berhasil dihapus'));
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

