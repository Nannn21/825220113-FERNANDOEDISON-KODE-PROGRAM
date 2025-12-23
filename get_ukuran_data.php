<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$ukuran_id = (int)$_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        ukuran_id,
        nama_ukuran,
        satuan
    FROM Ukuran
    WHERE ukuran_id = ?
");
$stmt->bind_param("i", $ukuran_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ukuran = $result->fetch_assoc();
    echo json_encode($ukuran);
} else {
    echo json_encode(['error' => 'Ukuran tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

