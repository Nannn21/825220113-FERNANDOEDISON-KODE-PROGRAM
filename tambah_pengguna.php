<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_pengguna.php?error=' . urlencode('Method tidak diizinkan'));
    exit();
}

$nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

if (empty($nama_lengkap)) {
    header('Location: data_pengguna.php?error=' . urlencode('Nama pengguna tidak boleh kosong'));
    exit();
}

if (strlen($nama_lengkap) > 100) {
    header('Location: data_pengguna.php?error=' . urlencode('Nama pengguna maksimal 100 karakter'));
    exit();
}

if (empty($username)) {
    header('Location: data_pengguna.php?error=' . urlencode('Email tidak boleh kosong'));
    exit();
}

if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
    header('Location: data_pengguna.php?error=' . urlencode('Format email tidak valid'));
    exit();
}

if (strlen($username) > 50) {
    header('Location: data_pengguna.php?error=' . urlencode('Email maksimal 50 karakter'));
    exit();
}

if (empty($password)) {
    header('Location: data_pengguna.php?error=' . urlencode('Password tidak boleh kosong'));
    exit();
}

if (strlen($password) < 6) {
    header('Location: data_pengguna.php?error=' . urlencode('Password minimal 6 karakter'));
    exit();
}

if (!preg_match('/[A-Z]/', $password)) {
    header('Location: data_pengguna.php?error=' . urlencode('Password harus mengandung minimal 1 huruf besar'));
    exit();
}

if (!preg_match('/[a-z]/', $password)) {
    header('Location: data_pengguna.php?error=' . urlencode('Password harus mengandung minimal 1 huruf kecil'));
    exit();
}

if (!preg_match('/[0-9]/', $password)) {
    header('Location: data_pengguna.php?error=' . urlencode('Password harus mengandung minimal 1 angka'));
    exit();
}

if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
    header('Location: data_pengguna.php?error=' . urlencode('Password harus mengandung minimal 1 tanda baca'));
    exit();
}

if (empty($confirm_password)) {
    header('Location: data_pengguna.php?error=' . urlencode('Konfirmasi password tidak boleh kosong'));
    exit();
}

if ($password !== $confirm_password) {
    header('Location: data_pengguna.php?error=' . urlencode('Password dan konfirmasi password tidak cocok'));
    exit();
}

if ($role_id <= 0) {
    header('Location: data_pengguna.php?error=' . urlencode('Pilih role terlebih dahulu'));
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT user_id FROM User WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email sudah terdaftar. Silakan gunakan email lain.");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT role_id, nama_role FROM Role WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Role tidak ditemukan");
    }
    $stmt->close();

    $kontak_default = "000000000000";
    $stmt = $conn->prepare("INSERT INTO Karyawan (nama_karyawan, kontak_karyawan) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama_lengkap, $kontak_default);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan karyawan");
    }
    
    $karyawan_id = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO User (role_id, karyawan_id, nama_lengkap, username, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $role_id, $karyawan_id, $nama_lengkap, $username, $password);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal menambahkan pengguna");
    }
    
    $stmt->close();
    $conn->commit();
    
    header('Location: data_pengguna.php?success=' . urlencode('Pengguna "' . $nama_lengkap . '" berhasil ditambahkan'));
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Location: data_pengguna.php?error=' . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

