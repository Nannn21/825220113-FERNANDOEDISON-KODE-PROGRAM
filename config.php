<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_db'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'role_id' => $_SESSION['role_id'] ?? null,
        'nama_role' => $_SESSION['nama_role'] ?? ''
    ];
}

function isSuperadmin() {
    $user = getCurrentUser();
    if (!$user) return false;

    if (isset($_SESSION['nama_role']) && $_SESSION['nama_role'] === 'Superadmin') {
        return true;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT r.nama_role FROM User u INNER JOIN Role r ON u.role_id = r.role_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nama_role = $row['nama_role'];
        $stmt->close();
        $conn->close();

        $_SESSION['nama_role'] = $nama_role;
        
        return $nama_role === 'Superadmin';
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

function isKaryawan() {
    $user = getCurrentUser();
    if (!$user) return false;

    if (isset($_SESSION['nama_role']) && $_SESSION['nama_role'] === 'Karyawan') {
        return true;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT r.nama_role FROM User u INNER JOIN Role r ON u.role_id = r.role_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nama_role = $row['nama_role'];
        $stmt->close();
        $conn->close();

        $_SESSION['nama_role'] = $nama_role;
        
        return $nama_role === 'Karyawan';
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

function requireSuperadmin() {
    requireLogin();
    if (!isSuperadmin()) {
        header('Location: dashboard.php?error=' . urlencode('Akses ditolak. Hanya Superadmin yang dapat mengakses halaman ini.'));
        exit();
    }
}

function requireKaryawan() {
    requireLogin();
    if (!isKaryawan()) {
        header('Location: dashboard.php?error=' . urlencode('Akses ditolak.'));
        exit();
    }
}

function canAccessPage($page_name) {
    if (isSuperadmin()) {
        return true; 
    }
    
    if (isKaryawan()) {
        
        $allowed_pages = [
            'dashboard.php',
            'data_barang.php', 
            'barang_masuk.php',
            'barang_keluar.php',
            'logout.php'
        ];
        return in_array($page_name, $allowed_pages);
    }
    
    return false;
}
?>

