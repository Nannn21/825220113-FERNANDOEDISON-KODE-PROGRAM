<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit();
}

$is_superadmin = isSuperadmin();
$is_karyawan = isKaryawan();

function isActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<div class="sidebar">
    
    <div class="sidebar-header">
        <h2 class="sidebar-title">MENU</h2>
    </div>

    <div class="sidebar-section">
        <a href="dashboard.php" class="menu-item <?php echo isActive('dashboard.php', $current_page); ?>">
            <i class="far fa-clock"></i>
            <span>Dashboard</span>
        </a>
    </div>

    <?php if ($is_superadmin): ?>
    <div class="sidebar-section">
        <div class="section-header">DATA MASTER</div>
        
        <a href="data_barang.php" class="menu-item <?php echo isActive('data_barang.php', $current_page); ?>">
            <i class="fas fa-list"></i>
            <span>Data Barang</span>
        </a>
        
        <a href="kategori_barang.php" class="menu-item <?php echo isActive('kategori_barang.php', $current_page); ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Kategori Barang</span>
        </a>
        
        <a href="data_brand.php" class="menu-item <?php echo isActive('data_brand.php', $current_page); ?>">
            <i class="fas fa-tag"></i>
            <span>Data Brand</span>
        </a>
        
        <a href="data_ukuran.php" class="menu-item <?php echo isActive('data_ukuran.php', $current_page); ?>">
            <i class="fas fa-ruler"></i>
            <span>Data Ukuran</span>
        </a>
        
        <a href="data_rasa.php" class="menu-item <?php echo isActive('data_rasa.php', $current_page); ?>">
            <i class="fas fa-palette"></i>
            <span>Data Rasa</span>
        </a>
        
        <a href="data_supplier.php" class="menu-item <?php echo isActive('data_supplier.php', $current_page); ?>">
            <i class="fas fa-truck"></i>
            <span>Data Supplier</span>
        </a>
        
        <a href="data_customer.php" class="menu-item <?php echo isActive('data_customer.php', $current_page); ?>">
            <i class="fas fa-user"></i>
            <span>Data Customer</span>
        </a>
    </div>
    <?php endif; ?>

    <?php if ($is_karyawan): ?>
    <div class="sidebar-section">
        <div class="section-header">DATA MASTER</div>
        
        <a href="data_barang.php" class="menu-item <?php echo isActive('data_barang.php', $current_page); ?>">
            <i class="fas fa-list"></i>
            <span>Data Barang</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="sidebar-section">
        <div class="section-header">TRANSAKSI</div>
        
        <a href="barang_masuk.php" class="menu-item <?php echo isActive('barang_masuk.php', $current_page); ?>">
            <i class="fas fa-arrow-down"></i>
            <span>Barang Masuk</span>
        </a>
        
        <a href="barang_keluar.php" class="menu-item <?php echo isActive('barang_keluar.php', $current_page); ?>">
            <i class="fas fa-arrow-up"></i>
            <span>Barang Keluar</span>
        </a>
    </div>

    <?php if ($is_superadmin): ?>
    <div class="sidebar-section">
        <div class="section-header">MANAJEMEN USER</div>
        
        <a href="data_pengguna.php" class="menu-item <?php echo isActive('data_pengguna.php', $current_page); ?>">
            <i class="fas fa-users"></i>
            <span>Data Pengguna</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="user-profile">
        <div class="user-avatar">
            <img src="https:
                 alt="User Avatar" 
                 class="avatar-img">
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($user['nama_role'] ?? 'User'); ?></div>
        </div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<style>

.sidebar {
    width: 280px;
    height: 100vh;
    background: #FFFFFF;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.sidebar-header {
    padding: 30px 20px 20px;
    border-bottom: 1px solid #E0E0E0;
}

.sidebar-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #000000;
    margin: 0;
    letter-spacing: 0.5px;
}

.sidebar-section {
    padding: 10px 0;
}

.section-header {
    padding: 8px 20px;
    font-size: 11px;
    font-weight: 600;
    color: #999999;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 10px;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #333333;
    text-decoration: none;
    transition: background-color 0.2s ease;
    position: relative;
}

.menu-item i {
    font-size: 16px;
    margin-right: 10px;
    width: 20px;
    text-align: center;
    color: #666666;
}

.menu-item span {
    font-size: 0.95rem;
    font-weight: 500;
}

.menu-item:hover {
    background-color: #F5F5F5;
}

.menu-item.active {
    background-color: #E8E8E8;
    border-left: 4px solid #667eea;
}

.menu-item.active i {
    color: #667eea;
}

.menu-item.active span {
    font-weight: 600;
    color: #000000;
}

.user-profile {
    margin-top: auto;
    padding: 20px;
    border-top: 1px solid #E0E0E0;
}

.user-avatar {
    text-align: center;
    margin-bottom: 15px;
}

.avatar-img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 2px solid #E0E0E0;
}

.user-info {
    text-align: center;
    margin-bottom: 15px;
}

.user-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333333;
    margin-bottom: 4px;
}

.user-role {
    font-size: 0.8rem;
    color: #999999;
}

.btn-logout {
    display: block;
    width: 100%;
    padding: 10px;
    background-color: #E0E0E0;
    color: #333333;
    text-align: center;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.btn-logout:hover {
    background-color: #D0D0D0;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: #F5F5F5;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #CCCCCC;
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #999999;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1000;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
}
</style>

