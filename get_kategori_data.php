<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$kategori_id = (int)$_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        kategori_id,
        nama_kategori
    FROM KategoriBarang
    WHERE kategori_id = ?
");
$stmt->bind_param("i", $kategori_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $kategori = $result->fetch_assoc();
    echo json_encode($kategori);
} else {
    echo json_encode(['error' => 'Kategori tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

