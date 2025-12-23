<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$pelanggan_id = (int)$_GET['id'];
$conn = getDBConnection();

$check_column = $conn->query("SHOW COLUMNS FROM Pelanggan LIKE 'umur_anak'");
$has_umur_anak = ($check_column && $check_column->num_rows > 0);

if ($has_umur_anak) {
    $stmt = $conn->prepare("
        SELECT 
            pelanggan_id,
            nama_pelanggan,
            kontak_pelanggan,
            umur_anak
        FROM Pelanggan
        WHERE pelanggan_id = ?
    ");
} else {
    $stmt = $conn->prepare("
        SELECT 
            pelanggan_id,
            nama_pelanggan,
            kontak_pelanggan
        FROM Pelanggan
        WHERE pelanggan_id = ?
    ");
}

$stmt->bind_param("i", $pelanggan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    
    if (!$has_umur_anak) {
        $customer['umur_anak'] = null;
    }
    echo json_encode($customer);
} else {
    echo json_encode(['error' => 'Customer tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

