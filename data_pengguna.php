<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

$current_page = 'data_pengguna.php';
$user = getCurrentUser();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($limit <= 0) $limit = 10;
if ($page <= 0) $page = 1;
if (!in_array($limit, [10, 20, 30, 40, 50])) $limit = 10;

$offset = ($page - 1) * $limit;

$conn = getDBConnection();

$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE u.nama_lengkap LIKE ? OR u.username LIKE ?";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param];
    $types = 'ss';
}

$count_sql = "
    SELECT COUNT(*) as total
    FROM User u
    INNER JOIN Role r ON u.role_id = r.role_id
    $where_clause
";

if (!empty($search)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $count_result = $conn->query($count_sql);
    $total_records = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

$data_sql = "
    SELECT 
        u.user_id,
        u.nama_lengkap,
        u.username,
        r.nama_role
    FROM User u
    INNER JOIN Role r ON u.role_id = r.role_id
    $where_clause
    ORDER BY u.user_id ASC
    LIMIT ? OFFSET ?
";

if (!empty($search)) {
    $stmt = $conn->prepare($data_sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare($data_sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

$data_pengguna = [];
while ($row = $result->fetch_assoc()) {
    
    $count_stmt = $conn->prepare("SELECT COUNT(*) as row_num FROM User WHERE user_id <= ?");
    $count_stmt->bind_param("i", $row['user_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $row['row_num'] = $count_result->fetch_assoc()['row_num'];
    $count_stmt->close();
    
    $data_pengguna[] = $row;
}
$stmt->close();
$conn->close();

$showing_start = $total_records > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $limit, $total_records);

$conn_dropdown = getDBConnection();

$role_list = [];
$role_query = "SELECT role_id, nama_role FROM Role ORDER BY nama_role ASC";
$role_result = $conn_dropdown->query($role_query);
if ($role_result && $role_result->num_rows > 0) {
    while ($row = $role_result->fetch_assoc()) {
        $role_list[] = $row;
    }
}

$conn_dropdown->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna - Inventaris Barang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #F3EFFF;
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
            width: calc(100% - 280px);
            display: flex;
            flex-direction: column;
        }

        .page-header {
            background: linear-gradient(135deg, #8B7FC7 0%, #9B8FD7 100%);
            padding: 1px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .page-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .user-name {
            color: white;
            font-weight: 600;
        }

        .content-wrapper {
            padding: 30px;
        }
        
        .data-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .btn-tambah {
            background: #3B82F6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        
        .btn-tambah:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .entries-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .entries-control label {
            color: #666;
            font-size: 0.95rem;
        }
        
        .entries-control select {
            padding: 8px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        
        .search-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-control label {
            color: #666;
            font-size: 0.95rem;
        }
        
        .search-control input {
            width: 250px;
            padding: 8px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        
        .search-control input:focus {
            outline: none;
            border-color: #3B82F6;
        }

        .table-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            width: 100%;
            margin-top: 20px;
            max-height: 600px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: #F5F5F5;
            color: #666;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #E0E0E0;
            font-size: 0.95rem;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #F0F0F0;
            color: #333;
            text-align: left;
        }
        
        tr:hover {
            background-color: #FAFAFA;
        }
        
        .email-link {
            color: #3B82F6;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .email-link:hover {
            color: #2563EB;
        }
        
        .btn-hapus {
            background: #EF4444;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-hapus:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .showing-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            gap: 3px;
        }
        
        .pagination button,
        .pagination a {
            padding: 8px 12px;
            border: none;
            background: #E0E0E0;
            color: #666;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .pagination button:hover:not(:disabled),
        .pagination a:hover {
            background: #D0D0D0;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination a.active {
            background: #3B82F6;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #CCC;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            color: #2E7D32;
        }
        
        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #F44336;
            color: #C62828;
        }
        
        .alert i {
            font-size: 1.2rem;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5); 
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 500px;
            max-width: 90%;
            padding: 25px;
            z-index: 1001;
            animation: slideDownModal 0.3s ease;
        }
        
        .modal-header {
            position: relative;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .modal-close {
            position: absolute;
            top: 0;
            right: 0;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 5px 10px;
            line-height: 1;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: white;
            font-family: inherit;
        }
        
        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http:
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group input.error,
        .form-group select.error {
            border-color: #EF4444;
        }
        
        .error-message {
            display: block;
            color: #EF4444;
            font-size: 0.85rem;
            margin-top: 5px;
            min-height: 20px;
        }
        
        .success-message {
            display: block;
            color: #4CAF50;
            font-size: 0.85rem;
            margin-top: 5px;
            min-height: 20px;
        }
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-cancel {
            background: #EF4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-cancel:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-submit {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideDownModal {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .controls-row {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-control input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">Data Pengguna</h1>
            <div class="user-profile">
                <img src="https:
                     alt="User" 
                     class="user-avatar">
                <span class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="data-card">
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" id="alertMessage">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error" id="alertMessage">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <button class="btn-tambah" onclick="openModal()">
                    <i class="fas fa-plus"></i> Tambah Pengguna
                </button>

                <div class="controls-row">
                    
                    <div class="entries-control">
                        <label>Show</label>
                        <select id="limitSelect" onchange="changeLimit(this.value)">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="30" <?php echo $limit == 30 ? 'selected' : ''; ?>>30</option>
                            <option value="40" <?php echo $limit == 40 ? 'selected' : ''; ?>>40</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        </select>
                        <label>entries</label>
                    </div>

                    <div class="search-control">
                        <label>Search :</label>
                        <input type="text" 
                               id="searchInput" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               onkeyup="handleSearch(this.value)"
                               placeholder="">
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 80px;">NO</th>
                                <th style="width: 25%;">Nama Pengguna</th>
                                <th style="width: 30%;">Email</th>
                                <th style="width: 20%;">Role</th>
                                <th style="width: 25%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_pengguna)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>Tidak ada data pengguna<?php echo !empty($search) ? ' yang sesuai dengan pencarian' : ''; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_pengguna as $pengguna): ?>
                                <tr>
                                    <td><?php echo $pengguna['row_num']; ?></td>
                                    <td><?php echo htmlspecialchars($pengguna['nama_lengkap']); ?></td>
                                    <td>
                                        <span class="email-link"><?php echo htmlspecialchars($pengguna['username']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($pengguna['nama_role']); ?></td>
                                    <td>
                                        <button class="btn-hapus" onclick="confirmDeletePengguna(<?php echo $pengguna['user_id']; ?>, '<?php echo addslashes($pengguna['nama_lengkap']); ?>')">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <div class="showing-info">
                        Showing <?php echo $showing_start; ?> to <?php echo $showing_end; ?> of <?php echo $total_records; ?> entries
                    </div>
                    
                    <div class="pagination">
                        
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                Previous
                            </a>
                        <?php else: ?>
                            <button disabled>Previous</button>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                Next
                            </a>
                        <?php else: ?>
                            <button disabled>Next</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Tambahkan Data Pengguna</h2>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="formTambahPengguna" method="POST" action="tambah_pengguna.php">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Pengguna *</label>
                    <input type="text" 
                           id="nama_lengkap" 
                           name="nama_lengkap" 
                           maxlength="100"
                           required 
                           placeholder="Masukkan nama lengkap">
                    <span class="error-message" id="error_nama_lengkap"></span>
                </div>
                
                <div class="form-group">
                    <label for="username">Email *</label>
                    <input type="email" 
                           id="username" 
                           name="username" 
                           maxlength="50"
                           required 
                           placeholder="Masukkan email">
                    <span class="error-message" id="error_username"></span>
                </div>
                
                <div class="form-group">
                    <label for="role_id">Role *</label>
                    <select id="role_id" name="role_id" required>
                        <option value="" disabled selected>--Pilih Role--</option>
                        <?php foreach ($role_list as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>">
                                <?php echo htmlspecialchars($role['nama_role']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error_role_id"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           minlength="6"
                           required 
                           placeholder="Masukkan password">
                    <span class="error-message" id="error_password"></span>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           placeholder="Konfirmasi password">
                    <span class="error-message" id="error_confirm_password"></span>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let searchTimeout;

        function handleSearch(keyword) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('search', keyword);
                currentUrl.searchParams.set('page', '1'); 
                window.location.href = currentUrl.toString();
            }, 300); 
        }

        function changeLimit(limit) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('limit', limit);
            currentUrl.searchParams.set('page', '1'); 
            window.location.href = currentUrl.toString();
        }

        function confirmDeletePengguna(userId, namaLengkap) {
            if (confirm('Apakah Anda yakin ingin menghapus pengguna "' + namaLengkap + '"?\n\nPeringatan: Pengguna yang sudah memiliki transaksi tidak dapat dihapus.')) {
                window.location.href = 'hapus_pengguna.php?id=' + userId;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.getElementById('alertMessage');
            if (alertMessage) {
                setTimeout(function() {
                    alertMessage.style.transition = 'opacity 0.5s ease';
                    alertMessage.style.opacity = '0';
                    setTimeout(function() {
                        alertMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });

        function openModal() {
            document.getElementById('modalOverlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(function() {
                document.getElementById('nama_lengkap').focus();
            }, 100);
        }

        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            const form = document.getElementById('formTambahPengguna');
            
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
            
            document.body.style.overflow = 'auto';
            
            if (form) {
                form.reset();
                
                const roleSelect = document.getElementById('role_id');
                if (roleSelect) {
                    roleSelect.selectedIndex = 0;
                }
                
                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });
            }
        }

        function closeModalOnOverlay(event) {
            if (event.target.id === 'modalOverlay') {
                closeModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalOverlay = document.getElementById('modalOverlay');
                if (modalOverlay && modalOverlay.style.display === 'block') {
                    closeModal();
                }
            }
        });

        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const errorPassword = document.getElementById('error_password');
        const errorConfirmPassword = document.getElementById('error_confirm_password');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;

                passwordInput.classList.remove('error');
                errorPassword.textContent = '';
                
                if (password.length > 0) {
                    
                    let errorMsg = '';
                    
                    if (password.length < 6) {
                        errorMsg = 'Password minimal 6 karakter';
                    } else if (!/[A-Z]/.test(password)) {
                        errorMsg = 'Password harus mengandung minimal 1 huruf besar';
                    } else if (!/[a-z]/.test(password)) {
                        errorMsg = 'Password harus mengandung minimal 1 huruf kecil';
                    } else if (!/[0-9]/.test(password)) {
                        errorMsg = 'Password harus mengandung minimal 1 angka';
                    } else if (!/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password)) {
                        errorMsg = 'Password harus mengandung minimal 1 tanda baca';
                    }
                    
                    if (errorMsg) {
                        errorPassword.textContent = errorMsg;
                        errorPassword.className = 'error-message';
                        passwordInput.classList.add('error');
                    } else {
                        errorPassword.textContent = 'Password valid';
                        errorPassword.className = 'success-message';
                    }
                }

                if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
                    confirmPasswordInput.dispatchEvent(new Event('input'));
                }
            });
        }
        
        if (confirmPasswordInput && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0) {
                    if (password !== confirmPassword) {
                        errorConfirmPassword.textContent = 'Password tidak cocok';
                        errorConfirmPassword.className = 'error-message';
                        confirmPasswordInput.classList.add('error');
                    } else {
                        errorConfirmPassword.textContent = 'Password cocok';
                        errorConfirmPassword.className = 'success-message';
                        confirmPasswordInput.classList.remove('error');
                    }
                } else {
                    errorConfirmPassword.textContent = '';
                    confirmPasswordInput.classList.remove('error');
                }
            });
        }

        const formTambahPengguna = document.getElementById('formTambahPengguna');
        if (formTambahPengguna) {
            formTambahPengguna.addEventListener('submit', function(e) {
                let isValid = true;

                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                const inputs = formTambahPengguna.querySelectorAll('input, select');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });

                const namaLengkap = document.getElementById('nama_lengkap').value.trim();
                if (!namaLengkap || namaLengkap.length === 0) {
                    isValid = false;
                    document.getElementById('error_nama_lengkap').textContent = 'Nama pengguna tidak boleh kosong';
                    document.getElementById('nama_lengkap').classList.add('error');
                } else if (namaLengkap.length > 100) {
                    isValid = false;
                    document.getElementById('error_nama_lengkap').textContent = 'Nama pengguna maksimal 100 karakter';
                    document.getElementById('nama_lengkap').classList.add('error');
                }

                const username = document.getElementById('username').value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!username || username.length === 0) {
                    isValid = false;
                    document.getElementById('error_username').textContent = 'Email tidak boleh kosong';
                    document.getElementById('username').classList.add('error');
                } else if (!emailRegex.test(username)) {
                    isValid = false;
                    document.getElementById('error_username').textContent = 'Format email tidak valid';
                    document.getElementById('username').classList.add('error');
                } else if (username.length > 50) {
                    isValid = false;
                    document.getElementById('error_username').textContent = 'Email maksimal 50 karakter';
                    document.getElementById('username').classList.add('error');
                }

                const password = document.getElementById('password').value;
                const errorPassword = document.getElementById('error_password');
                if (!password || password.length === 0) {
                    isValid = false;
                    errorPassword.textContent = 'Password tidak boleh kosong';
                    errorPassword.className = 'error-message';
                    document.getElementById('password').classList.add('error');
                } else if (password.length < 6) {
                    isValid = false;
                    errorPassword.textContent = 'Password minimal 6 karakter';
                    errorPassword.className = 'error-message';
                    document.getElementById('password').classList.add('error');
                } else if (!/[A-Z]/.test(password)) {
                    isValid = false;
                    errorPassword.textContent = 'Password harus mengandung minimal 1 huruf besar';
                    errorPassword.className = 'error-message';
                    document.getElementById('password').classList.add('error');
                } else if (!/[a-z]/.test(password)) {
                    isValid = false;
                    errorPassword.textContent = 'Password harus mengandung minimal 1 huruf kecil';
                    errorPassword.className = 'error-message';
                    document.getElementById('password').classList.add('error');
                } else if (!/[0-9]/.test(password)) {
                    isValid = false;
                    errorPassword.textContent = 'Password harus mengandung minimal 1 angka';
                    errorPassword.className = 'error-message';
                    document.getElementById('password').classList.add('error');
                } else if (!/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password)) {
                    isValid = false;
                    errorPassword.textContent = 'Password harus mengandung minimal 1 tanda baca';
                    errorPassword.className = 'error-message';
                    document.getElementById('password').classList.add('error');
                }

                const confirmPassword = document.getElementById('confirm_password').value;
                if (!confirmPassword || confirmPassword.length === 0) {
                    isValid = false;
                    document.getElementById('error_confirm_password').textContent = 'Konfirmasi password tidak boleh kosong';
                    document.getElementById('error_confirm_password').className = 'error-message';
                    document.getElementById('confirm_password').classList.add('error');
                } else if (password !== confirmPassword) {
                    isValid = false;
                    document.getElementById('error_confirm_password').textContent = 'Password tidak cocok';
                    document.getElementById('error_confirm_password').className = 'error-message';
                    document.getElementById('confirm_password').classList.add('error');
                }

                const roleId = document.getElementById('role_id');
                if (!roleId || !roleId.value || roleId.value === '') {
                    isValid = false;
                    document.getElementById('error_role_id').textContent = 'Pilih role terlebih dahulu';
                    if (roleId) {
                        roleId.classList.add('error');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    const firstError = formTambahPengguna.querySelector('.error');
                    if (firstError) {
                        firstError.focus();
                    }
                    return false;
                }

                const submitButton = formTambahPengguna.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }
    </script>
</body>
</html>
