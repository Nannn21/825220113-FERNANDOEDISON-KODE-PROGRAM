<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_rasa.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$rasa_id = isset($_POST['rasa_id']) ? (int)$_POST['rasa_id'] : 0;
$nama_rasa = isset($_POST['nama_rasa']) ? trim($_POST['nama_rasa']) : '';

if ($rasa_id <= 0) {
    header('Location: data_rasa.php?error=' . urlencode('ID rasa tidak valid'));
    exit();
}

if (empty($nama_rasa)) {
    header('Location: data_rasa.php?error=' . urlencode('Nama rasa tidak boleh kosong'));
    exit();
}

if (strlen($nama_rasa) > 50) {
    header('Location: data_rasa.php?error=' . urlencode('Nama rasa maksimal 50 karakter'));
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT rasa_id FROM Rasa WHERE rasa_id = ?");
    $stmt->bind_param("i", $rasa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data rasa tidak ditemukan");
    }
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT rasa_id FROM Rasa WHERE nama_rasa = ? AND rasa_id != ?");
    $stmt->bind_param("si", $nama_rasa, $rasa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Nama rasa sudah ada. Silakan gunakan nama lain.");
    }
    $stmt->close();
    
    $stmt = $conn->prepare("UPDATE Rasa SET nama_rasa = ? WHERE rasa_id = ?");
    $stmt->bind_param("si", $nama_rasa, $rasa_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate rasa. Tidak ada perubahan data.");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_rasa.php?success=' . urlencode('Rasa "' . $nama_rasa . '" berhasil diupdate'));
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

