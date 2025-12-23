<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_barang.php?error=' . urlencode('ID barang tidak valid'));
    exit();
}

$barang_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT nama_barang FROM Barang WHERE barang_id = ?");
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data barang tidak ditemukan");
    }
    
    $barang = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM DetailMasuk WHERE barang_id = ?");
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $masuk_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM DetailKeluar WHERE barang_id = ?");
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $keluar_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $total_transaksi = $masuk_count + $keluar_count;
    
    if ($total_transaksi > 0) {
        throw new Exception("Barang tidak dapat dihapus karena sudah memiliki {$total_transaksi} transaksi");
    }

    $stmt = $conn->prepare("DELETE FROM Barang WHERE barang_id = ?");
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus barang");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_barang.php?success=' . urlencode('Barang "' . $barang['nama_barang'] . '" berhasil dihapus'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_barang.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

