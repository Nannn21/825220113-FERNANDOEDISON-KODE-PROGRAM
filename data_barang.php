<?php

require_once 'config.php';
require_once 'functions_dashboard.php';
requireLogin();

$current_page = 'data_barang.php';
$user = getCurrentUser();

$is_superadmin = isSuperadmin();
$is_karyawan = isKaryawan();

$limit_param = isset($_GET['limit']) ? $_GET['limit'] : '10';
$limit = ($limit_param === 'all') ? 'all' : (int)$limit_param;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($limit !== 'all' && $limit <= 0) $limit = 10;
if ($page <= 0) $page = 1;

$offset = ($limit === 'all') ? 0 : ($page - 1) * $limit;

$conn = getDBConnection();

$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE b.nama_barang LIKE ? OR kb.nama_kategori LIKE ? OR br.nama_brand LIKE ? OR u.nama_ukuran LIKE ? OR COALESCE(r.nama_rasa, '') LIKE ?";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param];
    $types = 'sssss';
}

$count_sql = "
    SELECT COUNT(*) as total
    FROM Barang b
    INNER JOIN KategoriBarang kb ON b.kategori_id = kb.kategori_id
    INNER JOIN Brand br ON b.brand_id = br.brand_id
    INNER JOIN Ukuran u ON b.ukuran_id = u.ukuran_id
    LEFT JOIN Rasa r ON b.rasa_id = r.rasa_id
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

$total_pages = ($limit === 'all') ? 1 : ceil($total_records / $limit);

$data_sql = "
    SELECT 
        b.barang_id,
        b.nama_barang,
        b.harga_jual,
        b.stok,
        b.kategori_id,
        b.brand_id,
        b.ukuran_id,
        b.rasa_id,
        b.Safety_Stock,
        b.ROP,
        kb.nama_kategori,
        br.nama_brand,
        u.nama_ukuran,
        u.satuan,
        r.nama_rasa
    FROM Barang b
    INNER JOIN KategoriBarang kb ON b.kategori_id = kb.kategori_id
    INNER JOIN Brand br ON b.brand_id = br.brand_id
    INNER JOIN Ukuran u ON b.ukuran_id = u.ukuran_id
    LEFT JOIN Rasa r ON b.rasa_id = r.rasa_id
    $where_clause
    ORDER BY b.barang_id ASC
";

if ($limit !== 'all') {
    $data_sql .= " LIMIT ? OFFSET ?";
}

if (!empty($search)) {
    $stmt = $conn->prepare($data_sql);
    if ($limit !== 'all') {
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare($data_sql);
    if ($limit !== 'all') {
        $stmt->bind_param("ii", $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}

$data_barang = [];
$index = 1;
while ($row = $result->fetch_assoc()) {
    
    $count_stmt = $conn->prepare("SELECT COUNT(*) as row_num FROM Barang WHERE barang_id <= ?");
    $count_stmt->bind_param("i", $row['barang_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $row['row_num'] = $count_result->fetch_assoc()['row_num'];
    $count_stmt->close();
    
    $data_barang[] = $row;
}
$stmt->close();
$conn->close();

if ($limit === 'all') {
    $showing_start = $total_records > 0 ? 1 : 0;
    $showing_end = $total_records;
} else {
    $showing_start = $total_records > 0 ? $offset + 1 : 0;
    $showing_end = min($offset + $limit, $total_records);
}

$conn_dropdown = getDBConnection();

$kategori_list = [];
$kategori_query = "SELECT kategori_id, nama_kategori FROM KategoriBarang ORDER BY nama_kategori ASC";
$kategori_result = $conn_dropdown->query($kategori_query);
if ($kategori_result && $kategori_result->num_rows > 0) {
    while ($row = $kategori_result->fetch_assoc()) {
        $kategori_list[] = $row;
    }
}

$brand_list = [];
$brand_query = "SELECT brand_id, nama_brand FROM Brand ORDER BY nama_brand ASC";
$brand_result = $conn_dropdown->query($brand_query);
if ($brand_result && $brand_result->num_rows > 0) {
    while ($row = $brand_result->fetch_assoc()) {
        $brand_list[] = $row;
    }
}

$ukuran_list = [];
$ukuran_query = "SELECT ukuran_id, nama_ukuran, satuan FROM Ukuran ORDER BY nama_ukuran ASC";
$ukuran_result = $conn_dropdown->query($ukuran_query);
if ($ukuran_result && $ukuran_result->num_rows > 0) {
    while ($row = $ukuran_result->fetch_assoc()) {
        $ukuran_list[] = $row;
    }
}

$rasa_list = [];
$rasa_query = "SELECT rasa_id, nama_rasa FROM Rasa ORDER BY nama_rasa ASC";
$rasa_result = $conn_dropdown->query($rasa_query);
if ($rasa_result && $rasa_result->num_rows > 0) {
    while ($row = $rasa_result->fetch_assoc()) {
        $rasa_list[] = $row;
    }
}

$conn_dropdown->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Inventaris Barang</title>
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
            padding: 0px 30px;
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
            width: 800px; 
            max-width: 90%;
            max-height: 90vh; 
            padding: 25px;
            z-index: 1001;
            animation: slideDownModal 0.3s ease;
            overflow-y: auto; 
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row-full {
            grid-column: 1 / -1; 
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
        .form-group input[type="number"],
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
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-cancel {
            background: #EF4444;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .btn-cancel:hover {
            background: #DC2626;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        
        .btn-submit {
            background: #4CAF50;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .btn-submit:hover {
            background: #45A049;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
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

        @media (max-width: 900px) {
            .modal-box {
                width: 95%;
                max-width: 500px;
            }
            
            .form-row {
                grid-template-columns: 1fr; 
            }
            
            .form-row-full {
                grid-column: 1;
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
            <h1 class="page-title">Data Barang</h1>
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

                <?php if ($is_superadmin): ?>
                <button class="btn-tambah" onclick="openModal()">
                    <i class="fas fa-plus"></i> Tambah Barang
                </button>
                <?php endif; ?>

                <div class="controls-row">
                    
                    <div class="entries-control">
                        <label>Show</label>
                        <select id="limitSelect" onchange="changeLimit(this.value)">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="30" <?php echo $limit == 30 ? 'selected' : ''; ?>>30</option>
                            <option value="40" <?php echo $limit == 40 ? 'selected' : ''; ?>>40</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="all" <?php echo $limit === 'all' ? 'selected' : ''; ?>>All</option>
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
                                <th style="width: 20%;">Nama Barang</th>
                                <th style="width: 12%;">Brand</th>
                                <th style="width: 10%;">Ukuran</th>
                                <th style="width: 10%;">Rasa</th>
                                <th style="width: 12%;">Harga</th>
                                <th style="width: 8%;">Stok</th>
                                <th style="width: 12%;">Kategori</th>
                                <th style="width: 15%;">Pilihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_barang)): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>Tidak ada data barang<?php echo !empty($search) ? ' yang sesuai dengan pencarian' : ''; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_barang as $barang): ?>
                                <tr>
                                    <td><?php echo $barang['row_num']; ?></td>
                                    <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                    <td><?php echo htmlspecialchars($barang['nama_brand']); ?></td>
                                    <td><?php echo htmlspecialchars($barang['nama_ukuran']); ?><?php echo !empty($barang['satuan']) ? ' (' . htmlspecialchars($barang['satuan']) . ')' : ''; ?></td>
                                    <td><?php echo $barang['nama_rasa'] ? htmlspecialchars($barang['nama_rasa']) : '-'; ?></td>
                                    <td><?php echo number_format($barang['harga_jual'], 0, ',', '.'); ?></td>
                                    <td><?php echo $barang['stok']; ?></td>
                                    <td><?php echo htmlspecialchars($barang['nama_kategori']); ?></td>
                                    <td>
                                        <?php if ($is_superadmin): ?>
                                        <button class="btn-edit" onclick="openEditModal(<?php echo $barang['barang_id']; ?>)">
                                            Edit
                                        </button>
                                        <button class="btn-hapus" onclick="confirmDeleteBarang(<?php echo $barang['barang_id']; ?>, '<?php echo addslashes($barang['nama_barang']); ?>')">
                                            Hapus
                                        </button>
                                        <?php else: ?>
                                        <span style="color: #999; font-style: italic;">-</span>
                                        <?php endif; ?>
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
                    
                    <?php if ($limit !== 'all'): ?>
                    <div class="pagination">
                        
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo urlencode($limit); ?>&search=<?php echo urlencode($search); ?>">
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
                            <a href="?page=<?php echo $i; ?>&limit=<?php echo urlencode($limit); ?>&search=<?php echo urlencode($search); ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo urlencode($limit); ?>&search=<?php echo urlencode($search); ?>">
                                Next
                            </a>
                        <?php else: ?>
                            <button disabled>Next</button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Tambahkan Data Barang</h2>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="formTambahBarang" method="POST" action="tambah_barang.php">
                
                <div class="form-group form-row-full">
                    <label for="nama_barang">Nama Barang *</label>
                    <input type="text" 
                           id="nama_barang" 
                           name="nama_barang" 
                           maxlength="100"
                           required 
                           placeholder="Masukkan nama barang">
                    <span class="error-message" id="error_nama_barang"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="brand_id">Brand *</label>
                        <select id="brand_id" name="brand_id" required>
                            <option value="" disabled selected>--Pilih Brand--</option>
                            <?php foreach ($brand_list as $brand): ?>
                                <option value="<?php echo $brand['brand_id']; ?>">
                                    <?php echo htmlspecialchars($brand['nama_brand']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-message" id="error_brand_id"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="kategori_id">Kategori *</label>
                        <select id="kategori_id" name="kategori_id" required>
                            <option value="" disabled selected>--Pilih Kategori--</option>
                            <?php foreach ($kategori_list as $kategori): ?>
                                <option value="<?php echo $kategori['kategori_id']; ?>">
                                    <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-message" id="error_kategori_id"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ukuran_id">Ukuran *</label>
                        <select id="ukuran_id" name="ukuran_id" required>
                            <option value="" disabled selected>--Pilih Ukuran--</option>
                            <?php foreach ($ukuran_list as $ukuran): ?>
                                <option value="<?php echo $ukuran['ukuran_id']; ?>">
                                    <?php echo htmlspecialchars($ukuran['nama_ukuran']); ?><?php echo !empty($ukuran['satuan']) ? ' (' . htmlspecialchars($ukuran['satuan']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-message" id="error_ukuran_id"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="rasa_id">Rasa</label>
                        <select id="rasa_id" name="rasa_id">
                            <option value="" selected>--Tidak Ada Rasa--</option>
                            <?php foreach ($rasa_list as $rasa): ?>
                                <option value="<?php echo $rasa['rasa_id']; ?>">
                                    <?php echo htmlspecialchars($rasa['nama_rasa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-message" id="error_rasa_id"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="harga_jual">Harga *</label>
                        <input type="number" 
                               id="harga_jual" 
                               name="harga_jual" 
                               min="0"
                               step="0.01"
                               required 
                               placeholder="Masukkan harga">
                        <span class="error-message" id="error_harga_jual"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="stok">Stok *</label>
                        <input type="number" 
                               id="stok" 
                               name="stok" 
                               min="0"
                               step="1"
                               required 
                               placeholder="Masukkan stok">
                        <span class="error-message" id="error_stok"></span>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModalOverlay" class="modal-overlay" onclick="closeEditModalOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Edit Data Barang</h2>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="formEditBarang" method="POST" action="edit_barang.php">
                
                <input type="hidden" id="edit_barang_id" name="barang_id" value="">
                
                <div class="form-group">
                    <label for="edit_nama_barang">Nama Barang *</label>
                    <input type="text" 
                           id="edit_nama_barang" 
                           name="nama_barang" 
                           maxlength="100"
                           required 
                           placeholder="Masukkan nama barang">
                    <span class="error-message" id="error_edit_nama_barang"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_brand_id">Brand *</label>
                    <select id="edit_brand_id" name="brand_id" required>
                        <option value="" disabled>--Pilih Brand--</option>
                        <?php foreach ($brand_list as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>">
                                <?php echo htmlspecialchars($brand['nama_brand']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error_edit_brand_id"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_ukuran_id">Ukuran *</label>
                    <select id="edit_ukuran_id" name="ukuran_id" required>
                        <option value="" disabled>--Pilih Ukuran--</option>
                        <?php foreach ($ukuran_list as $ukuran): ?>
                            <option value="<?php echo $ukuran['ukuran_id']; ?>">
                                <?php echo htmlspecialchars($ukuran['nama_ukuran']); ?><?php echo !empty($ukuran['satuan']) ? ' (' . htmlspecialchars($ukuran['satuan']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error_edit_ukuran_id"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_rasa_id">Rasa</label>
                    <select id="edit_rasa_id" name="rasa_id">
                        <option value="" selected>--Tidak Ada Rasa--</option>
                        <?php foreach ($rasa_list as $rasa): ?>
                            <option value="<?php echo $rasa['rasa_id']; ?>">
                                <?php echo htmlspecialchars($rasa['nama_rasa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error_edit_rasa_id"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_kategori_id">Kategori *</label>
                    <select id="edit_kategori_id" name="kategori_id" required>
                        <option value="" disabled>--Pilih Kategori--</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?php echo $kategori['kategori_id']; ?>">
                                <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error_edit_kategori_id"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_harga_jual">Harga *</label>
                    <input type="number" 
                           id="edit_harga_jual" 
                           name="harga_jual" 
                           min="0"
                           step="0.01"
                           required 
                           placeholder="Masukkan harga">
                    <span class="error-message" id="error_edit_harga_jual"></span>
                </div>
                
                <div class="form-group">
                    <label for="edit_stok">Stok *</label>
                    <input type="number" 
                           id="edit_stok" 
                           name="stok" 
                           min="0"
                           step="1"
                           required 
                           placeholder="Masukkan stok">
                    <span class="error-message" id="error_edit_stok"></span>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
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

        function confirmDeleteBarang(barangId, namaBarang) {
            if (confirm('Apakah Anda yakin ingin menghapus barang "' + namaBarang + '"?\n\nPeringatan: Barang yang sudah memiliki transaksi tidak dapat dihapus.')) {
                window.location.href = 'hapus_barang.php?id=' + barangId;
            }
        }

        function openModal() {
            document.getElementById('modalOverlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(function() {
                document.getElementById('nama_barang').focus();
            }, 100);
        }

        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            const form = document.getElementById('formTambahBarang');
            
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
            
            document.body.style.overflow = 'auto';
            
            if (form) {
                form.reset();
                
                const brandSelect = document.getElementById('brand_id');
                if (brandSelect) {
                    brandSelect.selectedIndex = 0;
                }
                const ukuranSelect = document.getElementById('ukuran_id');
                if (ukuranSelect) {
                    ukuranSelect.selectedIndex = 0;
                }
                const rasaSelect = document.getElementById('rasa_id');
                if (rasaSelect) {
                    rasaSelect.selectedIndex = 0;
                }
                const kategoriSelect = document.getElementById('kategori_id');
                if (kategoriSelect) {
                    kategoriSelect.selectedIndex = 0;
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
                const editModalOverlay = document.getElementById('editModalOverlay');
                if (editModalOverlay && editModalOverlay.style.display === 'block') {
                    closeEditModal();
                }
            }
        });

        const formTambahBarang = document.getElementById('formTambahBarang');
        if (formTambahBarang) {
            formTambahBarang.addEventListener('submit', function(e) {
                let isValid = true;

                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                const inputs = formTambahBarang.querySelectorAll('input, select');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });

                const namaBarang = document.getElementById('nama_barang').value.trim();
                if (!namaBarang || namaBarang.length === 0) {
                    isValid = false;
                    document.getElementById('error_nama_barang').textContent = 'Nama barang tidak boleh kosong';
                    document.getElementById('nama_barang').classList.add('error');
                } else if (namaBarang.length > 100) {
                    isValid = false;
                    document.getElementById('error_nama_barang').textContent = 'Nama barang maksimal 100 karakter';
                    document.getElementById('nama_barang').classList.add('error');
                }

                const hargaJual = document.getElementById('harga_jual').value;
                if (!hargaJual || hargaJual.length === 0) {
                    isValid = false;
                    document.getElementById('error_harga_jual').textContent = 'Harga tidak boleh kosong';
                    document.getElementById('harga_jual').classList.add('error');
                } else if (parseFloat(hargaJual) < 0) {
                    isValid = false;
                    document.getElementById('error_harga_jual').textContent = 'Harga tidak boleh negatif';
                    document.getElementById('harga_jual').classList.add('error');
                }

                const stok = document.getElementById('stok').value;
                if (!stok || stok.length === 0) {
                    isValid = false;
                    document.getElementById('error_stok').textContent = 'Stok tidak boleh kosong';
                    document.getElementById('stok').classList.add('error');
                } else if (parseInt(stok) < 0) {
                    isValid = false;
                    document.getElementById('error_stok').textContent = 'Stok tidak boleh negatif';
                    document.getElementById('stok').classList.add('error');
                }

                const brandId = document.getElementById('brand_id');
                if (!brandId || !brandId.value || brandId.value === '') {
                    isValid = false;
                    document.getElementById('error_brand_id').textContent = 'Pilih brand terlebih dahulu';
                    if (brandId) {
                        brandId.classList.add('error');
                    }
                }

                const ukuranId = document.getElementById('ukuran_id');
                if (!ukuranId || !ukuranId.value || ukuranId.value === '') {
                    isValid = false;
                    document.getElementById('error_ukuran_id').textContent = 'Pilih ukuran terlebih dahulu';
                    if (ukuranId) {
                        ukuranId.classList.add('error');
                    }
                }

                const kategoriId = document.getElementById('kategori_id');
                if (!kategoriId || !kategoriId.value || kategoriId.value === '') {
                    isValid = false;
                    document.getElementById('error_kategori_id').textContent = 'Pilih kategori terlebih dahulu';
                    if (kategoriId) {
                        kategoriId.classList.add('error');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    const firstError = formTambahBarang.querySelector('.error');
                    if (firstError) {
                        firstError.focus();
                    }
                    return false;
                }

                const submitButton = formTambahBarang.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }

        function openEditModal(barangId) {
            
            fetch('get_barang_data.php?id=' + barangId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }

                    document.getElementById('edit_barang_id').value = data.barang_id;
                    document.getElementById('edit_nama_barang').value = data.nama_barang || '';
                    document.getElementById('edit_harga_jual').value = data.harga_jual || '';
                    document.getElementById('edit_stok').value = data.stok || '';
                    document.getElementById('edit_kategori_id').value = data.kategori_id || '';
                    document.getElementById('edit_brand_id').value = data.brand_id || '';
                    document.getElementById('edit_ukuran_id').value = data.ukuran_id || '';
                    document.getElementById('edit_rasa_id').value = data.rasa_id || ''; 

                    document.getElementById('editModalOverlay').style.display = 'block';
                    document.body.style.overflow = 'hidden';

                    setTimeout(function() {
                        document.getElementById('edit_nama_barang').focus();
                    }, 100);
                })
                .catch(error => {
                    alert('Gagal mengambil data barang: ' + error);
                });
        }

        function closeEditModal() {
            const modalOverlay = document.getElementById('editModalOverlay');
            
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
            
            document.body.style.overflow = 'auto';

            const errorMessages = document.querySelectorAll('#formEditBarang .error-message');
            errorMessages.forEach(function(el) {
                el.textContent = '';
            });

            const formEditBarang = document.getElementById('formEditBarang');
            if (formEditBarang) {
                const inputs = formEditBarang.querySelectorAll('input, select');
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

        const formEditBarang = document.getElementById('formEditBarang');
        if (formEditBarang) {
            formEditBarang.addEventListener('submit', function(e) {
                let isValid = true;

                const errorMessages = document.querySelectorAll('#formEditBarang .error-message');
                errorMessages.forEach(function(el) {
                    el.textContent = '';
                });
                const inputs = formEditBarang.querySelectorAll('input, select');
                inputs.forEach(function(el) {
                    el.classList.remove('error');
                });

                const namaBarang = document.getElementById('edit_nama_barang').value.trim();
                if (!namaBarang || namaBarang.length === 0) {
                    isValid = false;
                    document.getElementById('error_edit_nama_barang').textContent = 'Nama barang tidak boleh kosong';
                    document.getElementById('edit_nama_barang').classList.add('error');
                } else if (namaBarang.length > 100) {
                    isValid = false;
                    document.getElementById('error_edit_nama_barang').textContent = 'Nama barang maksimal 100 karakter';
                    document.getElementById('edit_nama_barang').classList.add('error');
                }

                const hargaJual = document.getElementById('edit_harga_jual').value;
                if (!hargaJual || hargaJual.length === 0) {
                    isValid = false;
                    document.getElementById('error_edit_harga_jual').textContent = 'Harga tidak boleh kosong';
                    document.getElementById('edit_harga_jual').classList.add('error');
                } else if (parseFloat(hargaJual) < 0) {
                    isValid = false;
                    document.getElementById('error_edit_harga_jual').textContent = 'Harga tidak boleh negatif';
                    document.getElementById('edit_harga_jual').classList.add('error');
                }

                const stok = document.getElementById('edit_stok').value;
                if (!stok || stok.length === 0) {
                    isValid = false;
                    document.getElementById('error_edit_stok').textContent = 'Stok tidak boleh kosong';
                    document.getElementById('edit_stok').classList.add('error');
                } else if (parseInt(stok) < 0) {
                    isValid = false;
                    document.getElementById('error_edit_stok').textContent = 'Stok tidak boleh negatif';
                    document.getElementById('edit_stok').classList.add('error');
                }

                const brandId = document.getElementById('edit_brand_id');
                if (!brandId || !brandId.value || brandId.value === '') {
                    isValid = false;
                    document.getElementById('error_edit_brand_id').textContent = 'Pilih brand terlebih dahulu';
                    if (brandId) {
                        brandId.classList.add('error');
                    }
                }

                const ukuranId = document.getElementById('edit_ukuran_id');
                if (!ukuranId || !ukuranId.value || ukuranId.value === '') {
                    isValid = false;
                    document.getElementById('error_edit_ukuran_id').textContent = 'Pilih ukuran terlebih dahulu';
                    if (ukuranId) {
                        ukuranId.classList.add('error');
                    }
                }

                const kategoriId = document.getElementById('edit_kategori_id');
                if (!kategoriId || !kategoriId.value || kategoriId.value === '') {
                    isValid = false;
                    document.getElementById('error_edit_kategori_id').textContent = 'Pilih kategori terlebih dahulu';
                    if (kategoriId) {
                        kategoriId.classList.add('error');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    const firstError = formEditBarang.querySelector('.error');
                    if (firstError) {
                        firstError.focus();
                    }
                    return false;
                }

                const submitButton = formEditBarang.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }
    </script>
</body>
</html>
