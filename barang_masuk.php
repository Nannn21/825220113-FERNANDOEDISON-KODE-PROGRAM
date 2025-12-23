<?php
require_once 'config.php';
requireLogin();

$current_page = 'barang_masuk.php';
$user = getCurrentUser();

// Pagination & Search parameters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? trim($_GET['tanggal_mulai']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

// Validate sort parameter
$valid_sorts = ['default', 'quantity_desc', 'quantity_asc', 'nama_asc', 'nama_desc', 'supplier_asc', 'supplier_desc', 'tanggal_desc', 'tanggal_asc'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'default';
}

// Validate parameters
if ($limit <= 0) $limit = 10;
if ($page <= 0) $page = 1;
if (!in_array($limit, [10, 20, 30, 40, 50])) $limit = 10;

$offset = ($page - 1) * $limit;

// Get data from database
$conn = getDBConnection();

// Build query with search and date filter
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(b.nama_barang LIKE ? OR s.nama_supplier LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($tanggal_mulai)) {
    $where_conditions[] = "DATE(tm.tanggal) >= ?";
    $params[] = $tanggal_mulai;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Count total records
$count_sql = "
    SELECT COUNT(*) as total
    FROM DetailMasuk dm
    INNER JOIN TransaksiMasuk tm ON dm.masuk_id = tm.masuk_id
    INNER JOIN Barang b ON dm.barang_id = b.barang_id
    INNER JOIN Supplier s ON tm.supplier_id = s.supplier_id
    $where_clause
";

if (!empty($where_clause)) {
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $count_result = $conn->query($count_sql);
    $total_records = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Validate page number
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Build ORDER BY clause based on sort parameter
$order_by = "ORDER BY ";
switch($sort) {
    case 'quantity_desc':
        $order_by .= "dm.jumlah DESC";
        break;
    case 'quantity_asc':
        $order_by .= "dm.jumlah ASC";
        break;
    case 'nama_asc':
        $order_by .= "b.nama_barang ASC";
        break;
    case 'nama_desc':
        $order_by .= "b.nama_barang DESC";
        break;
    case 'supplier_asc':
        $order_by .= "s.nama_supplier ASC";
        break;
    case 'supplier_desc':
        $order_by .= "s.nama_supplier DESC";
        break;
    case 'tanggal_desc':
        $order_by .= "tm.tanggal DESC";
        break;
    case 'tanggal_asc':
        $order_by .= "tm.tanggal ASC";
        break;
    default:
        $order_by .= "dm.detailmasuk_id ASC";
}

// Get paginated data with JOIN
$data_sql = "
    SELECT 
        dm.detailmasuk_id,
        dm.masuk_id,
        dm.barang_id,
        dm.jumlah,
        b.nama_barang,
        s.nama_supplier,
        DATE(tm.tanggal) as tanggal
    FROM DetailMasuk dm
    INNER JOIN TransaksiMasuk tm ON dm.masuk_id = tm.masuk_id
    INNER JOIN Barang b ON dm.barang_id = b.barang_id
    INNER JOIN Supplier s ON tm.supplier_id = s.supplier_id
    $where_clause
    $order_by
    LIMIT ? OFFSET ?
";

$params_for_query = $params;
$params_for_query[] = $limit;
$params_for_query[] = $offset;
$types_for_query = $types . 'ii';

$stmt = $conn->prepare($data_sql);
if (!empty($params)) {
    $stmt->bind_param($types_for_query, ...$params_for_query);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Get data and calculate row number based on current page and offset
$data_transaksi = [];
$row_counter = $offset + 1;
while ($row = $result->fetch_assoc()) {
    $row['row_num'] = $row_counter++;
    $data_transaksi[] = $row;
}
$stmt->close();
$conn->close();

// Calculate showing info
$showing_start = $total_records > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $limit, $total_records);

// Get data for dropdowns
$conn_dropdown = getDBConnection();

// Get barang list
$barang_list = [];
$barang_query = "SELECT barang_id, nama_barang FROM Barang ORDER BY nama_barang ASC";
$barang_result = $conn_dropdown->query($barang_query);
if ($barang_result && $barang_result->num_rows > 0) {
    while ($row = $barang_result->fetch_assoc()) {
        $barang_list[] = $row;
    }
}

// Get supplier list
$supplier_list = [];
$supplier_query = "SELECT supplier_id, nama_supplier FROM Supplier ORDER BY nama_supplier ASC";
$supplier_result = $conn_dropdown->query($supplier_query);
if ($supplier_result && $supplier_result->num_rows > 0) {
    while ($row = $supplier_result->fetch_assoc()) {
        $supplier_list[] = $row;
    }
}

$conn_dropdown->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Masuk - Inventaris Barang</title>
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
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
        }
        
        /* Header Section */
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
        
        /* Content Section */
        .content-wrapper {
            padding: 30px;
        }
        
        .data-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
        }
        
        /* Button & Controls */
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
        
        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #F5F5F5;
            color: #666;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #E0E0E0;
            font-size: 0.95rem;
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
        
        /* Pagination */
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
        
        /* Alert Messages */
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
        
        /* Modal Overlay */
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
        
        .form-group select,
        .form-group input[type="number"],
        .form-group input[type="date"] {
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
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
            
            .modal-box {
                width: 90%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">Transaksi Masuk</h1>
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['nama_lengkap']); ?>&background=667eea&color=fff&size=50" 
                     alt="User" 
                     class="user-avatar">
                <span class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-wrapper">
            <div class="data-card">
                <!-- Alert Messages -->
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
                
                <!-- Tombol Tambah dan Export -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <button class="btn-tambah" onclick="openModal()">
                        <i class="fas fa-plus"></i> Tambah Transaksi
                    </button>
                    
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-export" onclick="exportExcel()" style="background: #10B981; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button class="btn-export" onclick="exportPDF()" style="background: #EF4444; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
                
                <!-- Filter Tanggal dan Sort -->
                <div style="background: #F9FAFB; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label style="color: #666; font-size: 0.95rem; font-weight: 600;">Tanggal Awal:</label>
                        <input type="date" 
                               id="tanggal_mulai" 
                               name="tanggal_mulai"
                               value="<?php echo htmlspecialchars($tanggal_mulai); ?>"
                               style="padding: 8px 12px; border: 1px solid #E0E0E0; border-radius: 6px; font-size: 0.95rem;"
                               onchange="applyFilters()">
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label style="color: #666; font-size: 0.95rem; font-weight: 600;">Sort By:</label>
                        <select id="sortSelect" 
                                onchange="applySort(this.value)" 
                                style="padding: 8px 12px; border: 1px solid #E0E0E0; border-radius: 6px; font-size: 0.95rem; cursor: pointer; background: white;">
                            <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Default</option>
                            <option value="quantity_desc" <?php echo $sort == 'quantity_desc' ? 'selected' : ''; ?>>Quantity Terbanyak</option>
                            <option value="quantity_asc" <?php echo $sort == 'quantity_asc' ? 'selected' : ''; ?>>Quantity Terkecil</option>
                            <option value="nama_asc" <?php echo $sort == 'nama_asc' ? 'selected' : ''; ?>>Nama Barang A-Z</option>
                            <option value="nama_desc" <?php echo $sort == 'nama_desc' ? 'selected' : ''; ?>>Nama Barang Z-A</option>
                            <option value="supplier_asc" <?php echo $sort == 'supplier_asc' ? 'selected' : ''; ?>>Supplier A-Z</option>
                            <option value="supplier_desc" <?php echo $sort == 'supplier_desc' ? 'selected' : ''; ?>>Supplier Z-A</option>
                            <option value="tanggal_desc" <?php echo $sort == 'tanggal_desc' ? 'selected' : ''; ?>>Tanggal Terbaru</option>
                            <option value="tanggal_asc" <?php echo $sort == 'tanggal_asc' ? 'selected' : ''; ?>>Tanggal Terlama</option>
                        </select>
                    </div>
                </div>
                
                <!-- Controls Row -->
                <div class="controls-row">
                    <!-- Entries Dropdown -->
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
                    
                    <!-- Search Box -->
                    <div class="search-control">
                        <label>Search :</label>
                        <input type="text" 
                               id="searchInput" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               onkeyup="handleSearch(this.value)"
                               placeholder="">
                    </div>
                </div>
                
                <!-- Table -->
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 80px;">NO</th>
                                <th style="width: 35%;">Nama Barang</th>
                                <th style="width: 20%;">Supplier</th>
                                <th style="width: 10%;">Qty.</th>
                                <th style="width: 15%;">Tanggal Masuk</th>
                                <th style="width: 20%;">Pilihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_transaksi)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>Tidak ada data transaksi masuk<?php echo !empty($search) ? ' yang sesuai dengan pencarian' : ''; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_transaksi as $transaksi): ?>
                                <tr>
                                    <td><?php echo $transaksi['row_num']; ?></td>
                                    <td><?php echo htmlspecialchars($transaksi['nama_barang']); ?></td>
                                    <td><?php echo htmlspecialchars($transaksi['nama_supplier']); ?></td>
                                    <td><?php echo htmlspecialchars($transaksi['jumlah']); ?></td>
                                    <td><?php echo htmlspecialchars($transaksi['tanggal']); ?></td>
                                    <td>
                                        <button class="btn-hapus" onclick="confirmDeleteTransaksiMasuk(<?php echo $transaksi['detailmasuk_id']; ?>, '<?php echo addslashes($transaksi['nama_barang']); ?>', <?php echo $transaksi['jumlah']; ?>)">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <div class="showing-info">
                        Showing <?php echo $showing_start; ?> to <?php echo $showing_end; ?> of <?php echo $total_records; ?> entries
                    </div>
                    
                    <div class="pagination">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&sort=<?php echo urlencode($sort); ?>">
                                Previous
                            </a>
                        <?php else: ?>
                            <button disabled>Previous</button>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&sort=<?php echo urlencode($sort); ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&sort=<?php echo urlencode($sort); ?>">
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
    
    <!-- Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Tambahkan Transaksi Masuk</h2>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="formTambahTransaksi" method="POST" action="tambah_transaksi_masuk.php">
                <div class="form-group">
                    <label for="barang_id">Nama Barang *</label>
                    <select id="barang_id" name="barang_id" required>
                        <option value="" disabled selected>--Pilih Barang--</option>
                        <?php foreach ($barang_list as $barang): ?>
                            <option value="<?php echo $barang['barang_id']; ?>">
                                <?php echo htmlspecialchars($barang['nama_barang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier_id">Supplier *</label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="" disabled selected>--Pilih Supplier--</option>
                        <?php foreach ($supplier_list as $supplier): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" step="1" required placeholder="Masukkan quantity">
                </div>
                
                <div class="form-group">
                    <label for="tanggal_pesan">Tanggal Pemesanan *</label>
                    <input type="date" id="tanggal_pesan" name="tanggal_pesan" value="<?php echo date('Y-m-d'); ?>" required>
                    <small style="color: #666; display: block; margin-top: 5px;">Tanggal saat Anda memesan barang ke supplier</small>
                </div>
                
                <div class="form-group">
                    <label for="date">Tanggal Barang Diterima *</label>
                    <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    <small style="color: #666; display: block; margin-top: 5px;">Tanggal saat barang sampai di gudang</small>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let searchTimeout;
        
        // Handle search with debounce
        function handleSearch(keyword) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 500); // Wait 500ms after user stops typing
        }
        
        // Apply filters (search and date)
        function applyFilters() {
            const currentUrl = new URL(window.location.href);
            const search = document.getElementById('searchInput').value;
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const sort = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'default';
            
            currentUrl.searchParams.set('search', search);
            currentUrl.searchParams.set('page', '1'); // Reset to page 1
            
            if (tanggalMulai) {
                currentUrl.searchParams.set('tanggal_mulai', tanggalMulai);
            } else {
                currentUrl.searchParams.delete('tanggal_mulai');
            }
            
            // Preserve sort parameter
            if (sort && sort !== 'default') {
                currentUrl.searchParams.set('sort', sort);
            } else {
                currentUrl.searchParams.delete('sort');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        // Apply sort
        function applySort(sortValue) {
            const currentUrl = new URL(window.location.href);
            const search = document.getElementById('searchInput').value;
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            
            currentUrl.searchParams.set('sort', sortValue);
            currentUrl.searchParams.set('page', '1'); // Reset to page 1 saat sort berubah
            
            // Preserve search and date filter
            if (search) {
                currentUrl.searchParams.set('search', search);
            } else {
                currentUrl.searchParams.delete('search');
            }
            
            if (tanggalMulai) {
                currentUrl.searchParams.set('tanggal_mulai', tanggalMulai);
            } else {
                currentUrl.searchParams.delete('tanggal_mulai');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        // Clear date filter
        function clearDateFilter() {
            const currentUrl = new URL(window.location.href);
            const sort = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'default';
            
            currentUrl.searchParams.delete('tanggal_mulai');
            currentUrl.searchParams.set('page', '1');
            
            // Preserve sort parameter
            if (sort && sort !== 'default') {
                currentUrl.searchParams.set('sort', sort);
            } else {
                currentUrl.searchParams.delete('sort');
            }
            window.location.href = currentUrl.toString();
        }
        
        // Change entries limit
        function changeLimit(limit) {
            const currentUrl = new URL(window.location.href);
            const sort = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'default';
            
            currentUrl.searchParams.set('limit', limit);
            currentUrl.searchParams.set('page', '1'); // Reset to page 1
            
            // Preserve sort parameter
            if (sort && sort !== 'default') {
                currentUrl.searchParams.set('sort', sort);
            } else {
                currentUrl.searchParams.delete('sort');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        // Export to Excel
        function exportExcel() {
            const search = document.getElementById('searchInput').value;
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const sort = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'default';
            
            let url = 'export_excel_barang_masuk.php?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (tanggalMulai) url += 'tanggal_mulai=' + encodeURIComponent(tanggalMulai) + '&';
            if (sort && sort !== 'default') url += 'sort=' + encodeURIComponent(sort) + '&';
            
            // Remove trailing & if exists
            url = url.replace(/&$/, '');
            
            // Open in same window to trigger download
            window.location.href = url;
        }
        
        // Export to PDF
        function exportPDF() {
            const search = document.getElementById('searchInput').value;
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const sort = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'default';
            
            let url = 'export_pdf_barang_masuk.php?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (tanggalMulai) url += 'tanggal_mulai=' + encodeURIComponent(tanggalMulai) + '&';
            if (sort && sort !== 'default') url += 'sort=' + encodeURIComponent(sort) + '&';
            if (tanggalMulai) url += 'tanggal_mulai=' + encodeURIComponent(tanggalMulai) + '&';
            
            window.open(url, '_blank');
        }
        
        // Confirm delete transaksi masuk
        function confirmDeleteTransaksiMasuk(detailmasukId, namaBarang, jumlah) {
            if (confirm('Apakah Anda yakin ingin menghapus transaksi masuk untuk barang "' + namaBarang + '" dengan jumlah ' + jumlah + '?\n\nPeringatan: Stok barang akan dikurangi sesuai dengan jumlah yang dihapus.')) {
                window.location.href = 'hapus_transaksi_masuk.php?id=' + detailmasukId;
            }
        }
        
        // Open modal
        function openModal() {
            document.getElementById('modalOverlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Close modal
        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            const form = document.getElementById('formTambahTransaksi');
            
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
            
            document.body.style.overflow = 'auto';
            
            if (form) {
                form.reset();
                // Reset dates to today
                const tanggalPesanInput = document.getElementById('tanggal_pesan');
                const dateInput = document.getElementById('date');
                if (tanggalPesanInput) {
                    tanggalPesanInput.value = '<?php echo date('Y-m-d'); ?>';
                }
                if (dateInput) {
                    dateInput.value = '<?php echo date('Y-m-d'); ?>';
                }
                // Reset select dropdowns to default
                const barangSelect = document.getElementById('barang_id');
                const supplierSelect = document.getElementById('supplier_id');
                if (barangSelect) {
                    barangSelect.selectedIndex = 0;
                }
                if (supplierSelect) {
                    supplierSelect.selectedIndex = 0;
                }
            }
        }
        
        // Close on overlay click
        function closeModalOnOverlay(event) {
            if (event.target.id === 'modalOverlay') {
                closeModal();
            }
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalOverlay = document.getElementById('modalOverlay');
                if (modalOverlay && modalOverlay.style.display === 'block') {
                    closeModal();
                }
            }
        });
        
        // Form validation and submit handling
        const formTambahTransaksi = document.getElementById('formTambahTransaksi');
        if (formTambahTransaksi) {
            formTambahTransaksi.addEventListener('submit', function(e) {
                const barang = document.getElementById('barang_id').value;
                const supplier = document.getElementById('supplier_id').value;
                const quantity = document.getElementById('quantity').value;
                const tanggalPesan = document.getElementById('tanggal_pesan').value;
                const date = document.getElementById('date').value;
                
                // Additional validation (HTML5 validation will handle most cases)
                if (!barang || barang === '') {
                    e.preventDefault();
                    alert('Pilih barang terlebih dahulu');
                    document.getElementById('barang_id').focus();
                    return false;
                }
                
                if (!supplier || supplier === '') {
                    e.preventDefault();
                    alert('Pilih supplier terlebih dahulu');
                    document.getElementById('supplier_id').focus();
                    return false;
                }
                
                if (!quantity || parseInt(quantity) <= 0) {
                    e.preventDefault();
                    alert('Masukkan quantity yang valid (minimal 1)');
                    document.getElementById('quantity').focus();
                    return false;
                }
                
                if (!tanggalPesan) {
                    e.preventDefault();
                    alert('Pilih tanggal pemesanan terlebih dahulu');
                    document.getElementById('tanggal_pesan').focus();
                    return false;
                }
                
                if (!date) {
                    e.preventDefault();
                    alert('Pilih tanggal barang diterima terlebih dahulu');
                    document.getElementById('date').focus();
                    return false;
                }
                
                // Validasi: tanggal_pesan tidak boleh lebih besar dari tanggal diterima
                if (tanggalPesan > date) {
                    e.preventDefault();
                    alert('Tanggal pemesanan tidak boleh lebih besar dari tanggal barang diterima');
                    document.getElementById('tanggal_pesan').focus();
                    return false;
                }
                
                // Show loading state
                const submitButton = formTambahTransaksi.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }
    </script>
</body>
</html>
