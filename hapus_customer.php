<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_customer.php?error=' . urlencode('ID customer tidak valid'));
    exit();
}

$pelanggan_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT nama_pelanggan FROM Pelanggan WHERE pelanggan_id = ?");
    $stmt->bind_param("i", $pelanggan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data customer tidak ditemukan");
    }
    
    $customer = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiKeluar WHERE pelanggan_id = ?");
    $stmt->bind_param("i", $pelanggan_id);
    $stmt->execute();
    $transaksi_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    if ($transaksi_count > 0) {
        throw new Exception("Customer tidak dapat dihapus karena sudah memiliki {$transaksi_count} transaksi");
    }

    $stmt = $conn->prepare("DELETE FROM Pelanggan WHERE pelanggan_id = ?");
    $stmt->bind_param("i", $pelanggan_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus customer");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_customer.php?success=' . urlencode('Customer "' . $customer['nama_pelanggan'] . '" berhasil dihapus'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_customer.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

