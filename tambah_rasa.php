<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_rasa.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_rasa = isset($_POST['nama_rasa']) ? trim($_POST['nama_rasa']) : '';

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
    
    $stmt = $conn->prepare("SELECT rasa_id FROM Rasa WHERE nama_rasa = ?");
    $stmt->bind_param("s", $nama_rasa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Nama rasa sudah ada. Silakan gunakan nama lain.");
    }
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO Rasa (nama_rasa) VALUES (?)");
    $stmt->bind_param("s", $nama_rasa);
    $stmt->execute();
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_rasa.php?success=' . urlencode('Rasa "' . $nama_rasa . '" berhasil ditambahkan'));
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

