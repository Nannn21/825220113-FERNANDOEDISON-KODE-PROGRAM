<?php

require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT u.user_id, u.username, u.password, u.nama_lengkap, u.role_id, r.nama_role 
                                FROM User u 
                                LEFT JOIN Role r ON u.role_id = r.role_id 
                                WHERE u.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['password'] === $password) {
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['nama_role'] = $user['nama_role'] ?? '';

                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventaris Barang</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        
        <div class="left-section">
            <div class="title-container">
                <h1 class="main-title">
                    <span class="line1">Inventaris</span>
                    <span class="line2">Barang</span>
                </h1>
            </div>
        </div>

        <div class="right-section">
            <div class="login-form-container">
        <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                        <label for="username" class="form-label">Username:</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                            class="form-input"
                            placeholder="Masukkan username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required 
                        autocomplete="username"
                        autofocus
                    >
            </div>
            
            <div class="form-group">
                        <label for="password" class="form-label">Password:</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                            class="form-input"
                        placeholder="Masukkan password" 
                        required 
                        autocomplete="current-password"
                    >
                </div>
                    
                    <button type="submit" class="btn-login">Login</button>
                    <div style="clear: both;"></div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="login.js"></script>
</body>
</html>
