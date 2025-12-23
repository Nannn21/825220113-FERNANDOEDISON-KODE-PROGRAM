<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_ukuran.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$ukuran_id = isset($_POST['ukuran_id']) ? (int)$_POST['ukuran_id'] : 0;
$nama_ukuran = isset($_POST['nama_ukuran']) ? trim($_POST['nama_ukuran']) : '';
$satuan = isset($_POST['satuan']) ? trim($_POST['satuan']) : '';

if ($ukuran_id <= 0) {
    header('Location: data_ukuran.php?error=' . urlencode('ID ukuran tidak valid'));
    exit();
}

if (empty($nama_ukuran)) {
    header('Location: data_ukuran.php?error=' . urlencode('Nama ukuran tidak boleh kosong'));
    exit();
}

if (strlen($nama_ukuran) > 50) {
    header('Location: data_ukuran.php?error=' . urlencode('Nama ukuran maksimal 50 karakter'));
    exit();
}

if (!empty($satuan) && strlen($satuan) > 20) {
    header('Location: data_ukuran.php?error=' . urlencode('Satuan maksimal 20 karakter'));
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT ukuran_id FROM Ukuran WHERE ukuran_id = ?");
    $stmt->bind_param("i", $ukuran_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data ukuran tidak ditemukan");
    }
    $stmt->close();
    
    $satuan_value = !empty($satuan) ? $satuan : null;
    
    $stmt = $conn->prepare("UPDATE Ukuran SET nama_ukuran = ?, satuan = ? WHERE ukuran_id = ?");
    $stmt->bind_param("ssi", $nama_ukuran, $satuan_value, $ukuran_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate ukuran. Tidak ada perubahan data.");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_ukuran.php?success=' . urlencode('Ukuran "' . $nama_ukuran . '" berhasil diupdate'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_ukuran.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

