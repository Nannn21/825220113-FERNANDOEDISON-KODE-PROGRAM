<?php

require_once 'config.php';
requireLogin();
requireSuperadmin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$rasa_id = (int)$_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        rasa_id,
        nama_rasa
    FROM Rasa
    WHERE rasa_id = ?
");
$stmt->bind_param("i", $rasa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $rasa = $result->fetch_assoc();
    echo json_encode($rasa);
} else {
    echo json_encode(['error' => 'Rasa tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>

