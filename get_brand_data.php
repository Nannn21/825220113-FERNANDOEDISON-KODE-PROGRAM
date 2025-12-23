<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$brand_id = (int)$_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        brand_id,
        nama_brand
    FROM Brand
    WHERE brand_id = ?
");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $brand = $result->fetch_assoc();
    echo json_encode($brand);
} else {
    echo json_encode(['error' => 'Brand tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

