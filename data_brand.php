<?php

require_once 'config.php';
requireLogin();
requireSuperadmin(); 

$current_page = 'data_brand.php';
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
    $where_clause = "WHERE nama_brand LIKE ?";
    $search_param = '%' . $search . '%';
    $params = [$search_param];
    $types = 's';
}

$count_sql = "SELECT COUNT(*) as total FROM Brand $where_clause";

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
    SELECT brand_id, nama_brand
    FROM Brand
    $where_clause
    ORDER BY brand_id ASC
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

$data_brand = [];
while ($row = $result->fetch_assoc()) {
    
    $count_stmt = $conn->prepare("SELECT COUNT(*) as row_num FROM Brand WHERE brand_id <= ?");
    $count_stmt->bind_param("i", $row['brand_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $row['row_num'] = $count_result->fetch_assoc()['row_num'];
    $count_stmt->close();
    
    $data_brand[] = $row;
}
$stmt->close();
$conn->close();

$showing_start = $total_records > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $limit, $total_records);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Brand - Inventaris Barang</title>
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
            width: 100%;
            margin-top: 20px;
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
        
        .btn-edit {
            background: #3B82F6;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 5px;
            transition: all 0.2s;
        }
        
        .btn-edit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
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
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: white;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group input.error {
            border-color: #EF4444;
        }
        
        .error-message {
            display: block;
            color: #EF4444;
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
            <h1 class="page-title">Data Brand</h1>
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
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <button class="btn-tambah" onclick="openModal()">
                    <i class="fas fa-plus"></i> Tambah Brand
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
                                <th style="width: 100px;">NO</th>
                                <th style="width: 60%;">Nama Brand</th>
                                <th style="width: 30%;">Pilihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_brand)): ?>
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>Tidak ada data brand<?php echo !empty($search) ? ' yang sesuai dengan pencarian' : ''; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_brand as $brand): ?>
                                <tr>
                                    <td><?php echo $brand['row_num']; ?></td>
                                    <td><?php echo htmlspecialchars($brand['nama_brand']); ?></td>
                                    <td>
                                        <button class="btn-edit" onclick="openEditModal(<?php echo $brand['brand_id']; ?>)">
                                            Edit
                                        </button>
                                        <button class="btn-hapus" onclick="confirmDeleteBrand(<?php echo $brand['brand_id']; ?>, '<?php echo addslashes($brand['nama_brand']); ?>')">
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

    <div id="editModalOverlay" class="modal-overlay" onclick="closeEditModalOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Edit Brand</h2>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="formEditBrand" method="POST" action="edit_brand.php">
                
                <input type="hidden" id="edit_brand_id" name="brand_id" value="">
                
                <div class="form-group">
                    <label for="edit_nama_brand">Nama Brand *</label>
                    <input type="text" 
                           id="edit_nama_brand" 
                           name="nama_brand" 
                           maxlength="100"
                           required 
                           placeholder="Masukkan nama brand">
                    <span class="error-message" id="error_edit_nama_brand"></span>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Tambahkan Brand</h2>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="formTambahBrand" method="POST" action="tambah_brand.php">
                <div class="form-group">
                    <label for="nama_brand">Nama Brand *</label>
                    <input type="text" 
                           id="nama_brand" 
                           name="nama_brand" 
                           maxlength="100"
                           required 
                           placeholder="Masukkan nama brand">
                    <span class="error-message" id="error_nama_brand"></span>
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
            }, 500);
        }

        function changeLimit(limit) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('limit', limit);
            currentUrl.searchParams.set('page', '1');
            window.location.href = currentUrl.toString();
        }

        function confirmDeleteBrand(brandId, namaBrand) {
            if (confirm('Apakah Anda yakin ingin menghapus brand "' + namaBrand + '"?\n\nPeringatan: Brand yang digunakan oleh barang tidak dapat dihapus.')) {
                window.location.href = 'hapus_brand.php?id=' + brandId;
            }
        }

        function openModal() {
            document.getElementById('modalOverlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
            setTimeout(function() {
                document.getElementById('nama_brand').focus();
            }, 100);
        }

        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            const form = document.getElementById('formTambahBrand');
            
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
            
            document.body.style.overflow = 'auto';
            
            if (form) {
                form.reset();
                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                const inputs = form.querySelectorAll('input');
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
                const editModalOverlay = document.getElementById('editModalOverlay');
                if (editModalOverlay && editModalOverlay.style.display === 'block') {
                    closeEditModal();
                }
            }
        });

        const formTambahBrand = document.getElementById('formTambahBrand');
        if (formTambahBrand) {
            formTambahBrand.addEventListener('submit', function(e) {
                let isValid = true;
                
                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                const inputs = formTambahBrand.querySelectorAll('input');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });
                
                const namaBrand = document.getElementById('nama_brand').value.trim();
                if (!namaBrand || namaBrand.length === 0) {
                    isValid = false;
                    document.getElementById('error_nama_brand').textContent = 'Nama brand tidak boleh kosong';
                    document.getElementById('nama_brand').classList.add('error');
                } else if (namaBrand.length > 100) {
                    isValid = false;
                    document.getElementById('error_nama_brand').textContent = 'Nama brand maksimal 100 karakter';
                    document.getElementById('nama_brand').classList.add('error');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    const firstError = formTambahBrand.querySelector('.error');
                    if (firstError) {
                        firstError.focus();
                    }
                    return false;
                }
                
                const submitButton = formTambahBrand.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }

        function openEditModal(brandId) {
            fetch('get_brand_data.php?id=' + brandId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    document.getElementById('edit_brand_id').value = data.brand_id;
                    document.getElementById('edit_nama_brand').value = data.nama_brand;
                    
                    document.getElementById('editModalOverlay').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    
                    setTimeout(function() {
                        document.getElementById('edit_nama_brand').focus();
                    }, 100);
                })
                .catch(error => {
                    alert('Gagal mengambil data brand: ' + error);
                });
        }

        function closeEditModal() {
            const modalOverlay = document.getElementById('editModalOverlay');
            
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
            
            document.body.style.overflow = 'auto';
            
            const errorMessages = document.querySelectorAll('#formEditBrand .error-message');
            errorMessages.forEach(function(el) {
                el.textContent = '';
            });
            
            const formEditBrand = document.getElementById('formEditBrand');
            if (formEditBrand) {
                const inputs = formEditBrand.querySelectorAll('input');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });
            }
        }

        function closeEditModalOnOverlay(event) {
            if (event.target.id === 'editModalOverlay') {
                closeEditModal();
            }
        }

        const formEditBrand = document.getElementById('formEditBrand');
        if (formEditBrand) {
            formEditBrand.addEventListener('submit', function(e) {
                let isValid = true;
                
                const errorMessages = document.querySelectorAll('#formEditBrand .error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                const inputs = formEditBrand.querySelectorAll('input');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });
                
                const namaBrand = document.getElementById('edit_nama_brand').value.trim();
                if (!namaBrand || namaBrand.length === 0) {
                    isValid = false;
                    document.getElementById('error_edit_nama_brand').textContent = 'Nama brand tidak boleh kosong';
                    document.getElementById('edit_nama_brand').classList.add('error');
                } else if (namaBrand.length > 100) {
                    isValid = false;
                    document.getElementById('error_edit_nama_brand').textContent = 'Nama brand maksimal 100 karakter';
                    document.getElementById('edit_nama_brand').classList.add('error');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    const firstError = formEditBrand.querySelector('.error');
                    if (firstError) {
                        firstError.focus();
                    }
                    return false;
                }
                
                const submitButton = formEditBrand.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }
    </script>
</body>
</html>

