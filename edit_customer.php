<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_customer.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$pelanggan_id = isset($_POST['pelanggan_id']) ? (int)$_POST['pelanggan_id'] : 0;
$nama_pelanggan = isset($_POST['nama_pelanggan']) ? trim($_POST['nama_pelanggan']) : '';
$kontak_pelanggan = isset($_POST['kontak_pelanggan']) ? trim($_POST['kontak_pelanggan']) : '';
$umur_anak = isset($_POST['umur_anak']) ? trim($_POST['umur_anak']) : '';

if ($pelanggan_id <= 0) {
    header('Location: data_customer.php?error=' . urlencode('ID customer tidak valid'));
    exit();
}

if (empty($nama_pelanggan)) {
    header('Location: data_customer.php?error=' . urlencode('Nama pelanggan tidak boleh kosong'));
    exit();
}

if (strlen($nama_pelanggan) > 100) {
    header('Location: data_customer.php?error=' . urlencode('Nama pelanggan maksimal 100 karakter'));
    exit();
}

if (empty($kontak_pelanggan)) {
    header('Location: data_customer.php?error=' . urlencode('Kontak pelanggan tidak boleh kosong'));
    exit();
}

if (!ctype_digit($kontak_pelanggan)) {
    header('Location: data_customer.php?error=' . urlencode('Kontak pelanggan harus berupa angka'));
    exit();
}

if (strlen($kontak_pelanggan) < 11) {
    header('Location: data_customer.php?error=' . urlencode('Kontak pelanggan minimal 11 angka'));
    exit();
}

if (strlen($kontak_pelanggan) > 20) {
    header('Location: data_customer.php?error=' . urlencode('Kontak pelanggan maksimal 20 angka'));
    exit();
}

$umur_anak_value = null;
if (!empty($umur_anak)) {
    if (!is_numeric($umur_anak)) {
        header('Location: data_customer.php?error=' . urlencode('Umur anak harus berupa angka'));
        exit();
    }
    $umur_anak_value = (int)$umur_anak;
    if ($umur_anak_value < 0) {
        header('Location: data_customer.php?error=' . urlencode('Umur anak tidak boleh negatif'));
        exit();
    }
    if ($umur_anak_value > 120) {
        header('Location: data_customer.php?error=' . urlencode('Umur anak maksimal 120 tahun'));
        exit();
    }
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT pelanggan_id FROM Pelanggan WHERE pelanggan_id = ?");
    $stmt->bind_param("i", $pelanggan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Data customer tidak ditemukan");
    }
    $stmt->close();

    $check_column = $conn->query("SHOW COLUMNS FROM Pelanggan LIKE 'umur_anak'");
    $has_umur_anak = ($check_column && $check_column->num_rows > 0);

    if ($has_umur_anak) {
        
        $stmt = $conn->prepare("UPDATE Pelanggan SET nama_pelanggan = ?, kontak_pelanggan = ?, umur_anak = ? WHERE pelanggan_id = ?");
        $stmt->bind_param("ssii", $nama_pelanggan, $kontak_pelanggan, $umur_anak_value, $pelanggan_id);
    } else {
        
        $stmt = $conn->prepare("UPDATE Pelanggan SET nama_pelanggan = ?, kontak_pelanggan = ? WHERE pelanggan_id = ?");
        $stmt->bind_param("ssi", $nama_pelanggan, $kontak_pelanggan, $pelanggan_id);
    }
    
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal mengupdate customer. Tidak ada perubahan data.");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_customer.php?success=' . urlencode('Customer "' . $nama_pelanggan . '" berhasil diupdate'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_customer.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

