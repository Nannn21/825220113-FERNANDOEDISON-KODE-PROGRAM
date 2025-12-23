<?php

require_once 'config.php';
require_once 'functions_inventory_calculation.php';
requireLogin();
requireSuperadmin();

$results = updateAllBarangSafetyStockROP();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update ROP dan Safety Stock</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .summary {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .summary h3 {
            margin-top: 0;
            color: #333;
        }
        .summary p {
            margin: 5px 0;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h2>Hasil Update ROP dan Safety Stock</h2>
    
    <?php
    $total_barang = count($results);
    $success_count = 0;
    $failed_count = 0;
    
    foreach ($results as $result) {
        if ($result['success']) {
            $success_count++;
        } else {
            $failed_count++;
        }
    }
    ?>
    
    <div class="summary">
        <h3>Ringkasan</h3>
        <p><strong>Total Barang:</strong> <?php echo $total_barang; ?></p>
        <p class="success"><strong>Berhasil:</strong> <?php echo $success_count; ?></p>
        <p class="error"><strong>Gagal:</strong> <?php echo $failed_count; ?></p>
        <p><strong>Lead Time yang digunakan:</strong> 3 hari (default baru)</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Barang ID</th>
                <th>Safety Stock</th>
                <th>ROP</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $barang_id => $result): ?>
                <tr>
                    <td><?php echo htmlspecialchars($barang_id); ?></td>
                    <td><?php echo htmlspecialchars($result['safety_stock']); ?></td>
                    <td><?php echo htmlspecialchars($result['rop']); ?></td>
                    <td class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($result['message']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
        <strong>⚠️ Peringatan Keamanan:</strong> File ini sebaiknya dihapus setelah selesai digunakan untuk mencegah akses tidak sah.
    </div>
</body>
</html>

