<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_rasa.php?error=' . urlencode('ID rasa tidak valid'));
    exit();
}

$rasa_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT nama_rasa FROM Rasa WHERE rasa_id = ?");
    $stmt->bind_param("i", $rasa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data rasa tidak ditemukan");
    }
    
    $rasa = $result->fetch_assoc();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Barang WHERE rasa_id = ?");
    $stmt->bind_param("i", $rasa_id);
    $stmt->execute();
    $barang_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    if ($barang_count > 0) {
        throw new Exception("Rasa tidak dapat dihapus karena masih digunakan oleh {$barang_count} barang");
    }
    
    $stmt = $conn->prepare("DELETE FROM Rasa WHERE rasa_id = ?");
    $stmt->bind_param("i", $rasa_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus rasa");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_rasa.php?success=' . urlencode('Rasa "' . $rasa['nama_rasa'] . '" berhasil dihapus'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_rasa.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

