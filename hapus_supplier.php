<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_supplier.php?error=' . urlencode('ID supplier tidak valid'));
    exit();
}

$supplier_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT nama_supplier FROM Supplier WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data supplier tidak ditemukan");
    }
    
    $supplier = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiMasuk WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $transaksi_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    if ($transaksi_count > 0) {
        throw new Exception("Supplier tidak dapat dihapus karena sudah memiliki {$transaksi_count} transaksi");
    }

    $stmt = $conn->prepare("DELETE FROM Supplier WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus supplier");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_supplier.php?success=' . urlencode('Supplier "' . $supplier['nama_supplier'] . '" berhasil dihapus'));
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

