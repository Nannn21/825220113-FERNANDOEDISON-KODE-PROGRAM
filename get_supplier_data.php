<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$supplier_id = (int)$_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        supplier_id,
        nama_supplier,
        kontak_supplier
    FROM Supplier
    WHERE supplier_id = ?
");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $supplier = $result->fetch_assoc();
    echo json_encode($supplier);
} else {
    echo json_encode(['error' => 'Supplier tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

