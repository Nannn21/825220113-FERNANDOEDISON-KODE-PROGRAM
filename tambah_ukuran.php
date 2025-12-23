<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_ukuran.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_ukuran = isset($_POST['nama_ukuran']) ? trim($_POST['nama_ukuran']) : '';
$satuan = isset($_POST['satuan']) ? trim($_POST['satuan']) : '';

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
    
    $satuan_value = !empty($satuan) ? $satuan : null;
    
    $stmt = $conn->prepare("INSERT INTO Ukuran (nama_ukuran, satuan) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama_ukuran, $satuan_value);
    $stmt->execute();
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_ukuran.php?success=' . urlencode('Ukuran "' . $nama_ukuran . '" berhasil ditambahkan'));
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

