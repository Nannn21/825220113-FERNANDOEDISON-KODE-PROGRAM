<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$barang_id = (int)$_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        b.barang_id,
        b.nama_barang,
        b.harga_jual,
        b.stok,
        b.kategori_id,
        b.brand_id,
        b.ukuran_id,
        b.rasa_id
    FROM Barang b
    WHERE b.barang_id = ?
");
$stmt->bind_param("i", $barang_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $barang = $result->fetch_assoc();
    echo json_encode([
        'barang_id' => (int)$barang['barang_id'],
        'nama_barang' => $barang['nama_barang'],
        'harga_jual' => (float)$barang['harga_jual'],
        'stok' => (int)$barang['stok'],
        'kategori_id' => (int)$barang['kategori_id'],
        'brand_id' => (int)$barang['brand_id'],
        'ukuran_id' => (int)$barang['ukuran_id'],
        'rasa_id' => $barang['rasa_id'] ? (int)$barang['rasa_id'] : null
    ]);
} else {
    echo json_encode(['error' => 'Barang tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

