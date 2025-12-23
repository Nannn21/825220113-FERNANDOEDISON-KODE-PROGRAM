<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_barang.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_barang = isset($_POST['nama_barang']) ? trim($_POST['nama_barang']) : '';
$harga_jual = isset($_POST['harga_jual']) ? (float)$_POST['harga_jual'] : 0;
$stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 0;
$kategori_id = isset($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : 0;
$brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
$ukuran_id = isset($_POST['ukuran_id']) ? (int)$_POST['ukuran_id'] : 0;
$rasa_id = isset($_POST['rasa_id']) ? (int)$_POST['rasa_id'] : 0;

if (empty($nama_barang)) {
    header('Location: data_barang.php?error=' . urlencode('Nama barang tidak boleh kosong'));
    exit();
}

if (strlen($nama_barang) > 100) {
    header('Location: data_barang.php?error=' . urlencode('Nama barang maksimal 100 karakter'));
    exit();
}

if ($harga_jual <= 0) {
    header('Location: data_barang.php?error=' . urlencode('Harga harus lebih dari 0'));
    exit();
}

if ($stok < 0) {
    header('Location: data_barang.php?error=' . urlencode('Stok tidak boleh negatif'));
    exit();
}

if ($kategori_id <= 0) {
    header('Location: data_barang.php?error=' . urlencode('Pilih kategori terlebih dahulu'));
    exit();
}

if ($brand_id <= 0) {
    header('Location: data_barang.php?error=' . urlencode('Pilih brand terlebih dahulu'));
    exit();
}

if ($ukuran_id <= 0) {
    header('Location: data_barang.php?error=' . urlencode('Pilih ukuran terlebih dahulu'));
    exit();
}

$rasa_id_value = null;
if ($rasa_id > 0) {
    $rasa_id_value = $rasa_id;
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT kategori_id, nama_kategori FROM KategoriBarang WHERE kategori_id = ?");
    $stmt->bind_param("i", $kategori_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Kategori tidak ditemukan");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT brand_id FROM Brand WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Brand tidak ditemukan");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT ukuran_id FROM Ukuran WHERE ukuran_id = ?");
    $stmt->bind_param("i", $ukuran_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Ukuran tidak ditemukan");
    }
    $stmt->close();

    if ($rasa_id_value !== null) {
        $stmt = $conn->prepare("SELECT rasa_id FROM Rasa WHERE rasa_id = ?");
        $stmt->bind_param("i", $rasa_id_value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Rasa tidak ditemukan");
        }
        $stmt->close();
    }

    $stmt = $conn->prepare("INSERT INTO Barang (kategori_id, brand_id, ukuran_id, rasa_id, nama_barang, harga_jual, stok) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiisdi", $kategori_id, $brand_id, $ukuran_id, $rasa_id_value, $nama_barang, $harga_jual, $stok);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan barang");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_barang.php?success=' . urlencode('Barang "' . $nama_barang . '" berhasil ditambahkan'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_barang.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

