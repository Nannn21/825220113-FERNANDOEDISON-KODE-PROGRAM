<?php

require_once 'config.php';
requireLogin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? trim($_GET['tanggal_mulai']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

// Validate sort parameter
$valid_sorts = ['default', 'quantity_desc', 'quantity_asc', 'nama_asc', 'nama_desc', 'supplier_asc', 'supplier_desc', 'tanggal_desc', 'tanggal_asc'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'default';
}

$conn = getDBConnection();

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
";

$stmt = $conn->prepare($data_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data_transaksi = [];
$no = 1;
while ($row = $result->fetch_assoc()) {
    $data_transaksi[] = [
        'no' => $no++,
        'nama_barang' => $row['nama_barang'],
        'nama_supplier' => $row['nama_supplier'],
        'jumlah' => $row['jumlah'],
        'tanggal' => $row['tanggal']
    ];
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Barang Masuk</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        .no-print button {
            background: #EF4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
        }
        .no-print button:hover {
            background: #DC2626;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #8B7FC7;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
    </style>
    <script>
        function printPDF() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="no-print">
        <button onclick="printPDF()">🖨️ Print / Save as PDF</button>
        <button onclick="window.close()">✕ Tutup</button>
    </div>
    
    <h1>LAPORAN BARANG MASUK</h1>
    
    <div class="info">
        <p><strong>Tanggal Export:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        
        <?php if (!empty($tanggal_mulai)): ?>
        <p><strong>Tanggal Awal:</strong> <?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($search)): ?>
        <p><strong>Pencarian:</strong> <?php echo htmlspecialchars($search); ?></p>
        <?php endif; ?>
        
        <p><strong>Total Data:</strong> <?php echo count($data_transaksi); ?> transaksi</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">NO</th>
                <th>Nama Barang</th>
                <th>Supplier</th>
                <th style="width: 80px; text-align: center;">Quantity</th>
                <th style="width: 120px;">Tanggal Masuk</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data_transaksi)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data_transaksi as $transaksi): ?>
                <tr>
                    <td><?php echo $transaksi['no']; ?></td>
                    <td><?php echo htmlspecialchars($transaksi['nama_barang']); ?></td>
                    <td><?php echo htmlspecialchars($transaksi['nama_supplier']); ?></td>
                    <td style="text-align: center;"><?php echo $transaksi['jumlah']; ?></td>
                    <td><?php echo $transaksi['tanggal']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>
