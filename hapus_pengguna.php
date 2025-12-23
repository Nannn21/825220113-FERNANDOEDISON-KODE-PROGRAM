<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_pengguna.php?error=' . urlencode('ID pengguna tidak valid'));
    exit();
}

$user_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT nama_lengkap FROM User WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data pengguna tidak ditemukan");
    }
    
    $user = $result->fetch_assoc();
    $nama_lengkap = $user['nama_lengkap'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiMasuk WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $masuk_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiKeluar WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $keluar_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $total_transaksi = $masuk_count + $keluar_count;
    
    if ($total_transaksi > 0) {
        throw new Exception("User tidak dapat dihapus karena sudah memiliki {$total_transaksi} transaksi");
    }

    $stmt = $conn->prepare("DELETE FROM User WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menghapus pengguna");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_pengguna.php?success=' . urlencode('Pengguna "' . $nama_lengkap . '" berhasil dihapus'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_pengguna.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

