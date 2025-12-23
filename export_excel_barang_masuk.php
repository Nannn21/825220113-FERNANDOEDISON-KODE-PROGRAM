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

$filename = 'Barang_Masuk_' . date('Y-m-d_His') . '.csv';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['NO', 'Nama Barang', 'Supplier', 'Quantity', 'Tanggal Masuk'], ';');

$no = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['nama_barang'],
        $row['nama_supplier'],
        $row['jumlah'],
        $row['tanggal']
    ], ';');
}

$stmt->close();
$conn->close();
fclose($output);
exit;
