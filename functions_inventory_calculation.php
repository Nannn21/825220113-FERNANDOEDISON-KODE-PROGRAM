<?php

require_once 'config.php';

function calculateAverageDailyUsage($barang_id, $days = 30) {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            COALESCE(SUM(dk.jumlah), 0) as total_keluar,
            COUNT(DISTINCT DATE(tk.tanggal)) as jumlah_hari
        FROM TransaksiKeluar tk
        INNER JOIN DetailKeluar dk ON tk.keluar_id = dk.keluar_id
        WHERE dk.barang_id = ? 
        AND tk.tanggal >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $barang_id, $days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_keluar = (float)$row['total_keluar'];
        $jumlah_hari = (int)$row['jumlah_hari'];
        
        $stmt->close();
        $conn->close();

        if ($jumlah_hari == 0 || $total_keluar == 0) {
            return 0.0;
        }

        return round($total_keluar / $days, 2);
    }
    
    $stmt->close();
    $conn->close();
    return 0.0;
}

function calculateStandardDeviation($barang_id, $days = 30) {
    $conn = getDBConnection();

    $average_usage = calculateAverageDailyUsage($barang_id, $days);
    
    if ($average_usage == 0) {
        $conn->close();
        return 0.0;
    }

    $sql = "
        SELECT 
            DATE(tk.tanggal) as tanggal,
            COALESCE(SUM(dk.jumlah), 0) as jumlah_keluar
        FROM TransaksiKeluar tk
        INNER JOIN DetailKeluar dk ON tk.keluar_id = dk.keluar_id
        WHERE dk.barang_id = ? 
        AND tk.tanggal >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(tk.tanggal)
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $barang_id, $days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data_points = [];
    while ($row = $result->fetch_assoc()) {
        $data_points[] = (float)$row['jumlah_keluar'];
    }
    $stmt->close();

    if (count($data_points) < 2) {
        $conn->close();
        return 0.0;
    }

    $sum_squared_diff = 0;
    $n = count($data_points);
    
    foreach ($data_points as $value) {
        $diff = $value - $average_usage;
        $sum_squared_diff += ($diff * $diff);
    }

    $variance = $sum_squared_diff / ($n - 1);
    $standard_deviation = sqrt($variance);
    
    $conn->close();
    return round($standard_deviation, 2);
}

function calculateAverageLeadTime($barang_id, $months = 6, $min_transactions = 2) {
    $conn = getDBConnection();

    // Query untuk mengambil tanggal_pesan dan tanggal (received)
    $sql = "
        SELECT 
            DATE(tm.tanggal_pesan) as tanggal_pesan,
            DATE(tm.tanggal) as tanggal_terima
        FROM TransaksiMasuk tm
        INNER JOIN DetailMasuk dm ON tm.masuk_id = dm.masuk_id
        WHERE dm.barang_id = ?
        AND tm.tanggal >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        AND tm.tanggal_pesan IS NOT NULL
        AND tm.tanggal_pesan <= tm.tanggal
        ORDER BY tm.tanggal ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $barang_id, $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lead_times = [];
    while ($row = $result->fetch_assoc()) {
        $tanggal_pesan = new DateTime($row['tanggal_pesan']);
        $tanggal_terima = new DateTime($row['tanggal_terima']);
        $diff = $tanggal_pesan->diff($tanggal_terima);
        $days_diff = (int)$diff->days;
        
        // Validasi: lead time harus positif dan masuk akal (0-365 hari)
        if ($days_diff >= 0 && $days_diff <= 365) {
            $lead_times[] = $days_diff;
        }
    }
    $stmt->close();
    $conn->close();
    
    $transaction_count = count($lead_times);
    
    // Minimal 2 transaksi untuk menghitung rata-rata
    if ($transaction_count < $min_transactions) {
        return null; 
    }
    
    // Hitung rata-rata lead time
    $average_lead_time = array_sum($lead_times) / $transaction_count;
    
    // Validasi hasil
    if ($average_lead_time <= 0 || $average_lead_time > 365) {
        return null;
    }
    
    return round($average_lead_time, 2);
}

function getLeadTime($barang_id) {
    
    $calculated_lead_time = calculateAverageLeadTime($barang_id);
    
    if ($calculated_lead_time !== null && $calculated_lead_time > 0 && $calculated_lead_time <= 365) {
        return (int)ceil($calculated_lead_time);
    }

    $conn = getDBConnection();
    $check_column = $conn->query("SHOW COLUMNS FROM Barang LIKE 'lead_time'");
    
    if ($check_column && $check_column->num_rows > 0) {
        $sql = "SELECT lead_time FROM Barang WHERE barang_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $barang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lead_time = (int)$row['lead_time'];
            $stmt->close();
            $conn->close();
            
            if ($lead_time > 0) {
                return $lead_time;
            }
        }
        $stmt->close();
    }

    $conn->close();
    return 3;
}

function calculateSafetyStock($barang_id, $z_score = 1.65) {
    
    $standard_deviation = calculateStandardDeviation($barang_id, 30);

    if ($standard_deviation == 0) {
        
        $average_usage = calculateAverageDailyUsage($barang_id, 30);
        $lead_time = getLeadTime($barang_id);
        $default_safety_stock = ($average_usage * $lead_time) * 0.1;
        return (int)ceil($default_safety_stock > 0 ? $default_safety_stock : 5);
    }

    $lead_time = getLeadTime($barang_id);

    $safety_stock_part1 = $z_score * $standard_deviation * sqrt($lead_time);

    $average_usage = calculateAverageDailyUsage($barang_id, 30);
    $buffer = ($average_usage * $lead_time) * 0.1;

    $safety_stock = $safety_stock_part1 + $buffer;

    return (int)ceil(max($safety_stock, 1));
}

function calculateROP($barang_id) {
    
    $average_daily_usage = calculateAverageDailyUsage($barang_id, 30);

    $lead_time = getLeadTime($barang_id);

    $safety_stock = calculateSafetyStock($barang_id);

    $demand_during_lead_time = $average_daily_usage * $lead_time;
    $rop = $demand_during_lead_time + $safety_stock;

    return (int)ceil(max($rop, 1));
}

function updateSafetyStockROP($barang_id) {
    try {
        $conn = getDBConnection();

        $safety_stock = calculateSafetyStock($barang_id);
        $rop = calculateROP($barang_id);

        $sql = "UPDATE Barang SET Safety_Stock = ?, ROP = ? WHERE barang_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $safety_stock, $rop, $barang_id);
        
        $success = $stmt->execute();
        
        $stmt->close();
        $conn->close();
        
        return $success;
    } catch (Exception $e) {
        error_log("Error updating Safety Stock and ROP for barang_id {$barang_id}: " . $e->getMessage());
        return false;
    }
}

function updateAllBarangSafetyStockROP() {
    $conn = getDBConnection();

    $sql = "SELECT barang_id FROM Barang ORDER BY barang_id ASC";
    $result = $conn->query($sql);
    
    $results = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barang_id = (int)$row['barang_id'];

            $safety_stock = calculateSafetyStock($barang_id);
            $rop = calculateROP($barang_id);

            $success = updateSafetyStockROP($barang_id);
            
            $results[$barang_id] = [
                'success' => $success,
                'safety_stock' => $safety_stock,
                'rop' => $rop,
                'message' => $success ? 'Berhasil diupdate' : 'Gagal diupdate'
            ];
        }
    }
    
    $conn->close();
    return $results;
}

function getBarangInfo($barang_id) {
    $conn = getDBConnection();
    
    $sql = "SELECT barang_id, nama_barang, stok, Safety_Stock, ROP FROM Barang WHERE barang_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        
        return [
            'barang_id' => (int)$row['barang_id'],
            'nama_barang' => $row['nama_barang'],
            'stok' => (int)$row['stok'],
            'Safety_Stock' => (int)$row['Safety_Stock'],
            'ROP' => (int)$row['ROP']
        ];
    }
    
    $stmt->close();
    $conn->close();
    return null;
}

function hasEnoughTransactionData($barang_id, $min_days = 7) {
    $conn = getDBConnection();
    
    $sql = "
        SELECT COUNT(DISTINCT DATE(tk.tanggal)) as jumlah_hari
        FROM TransaksiKeluar tk
        INNER JOIN DetailKeluar dk ON tk.keluar_id = dk.keluar_id
        WHERE dk.barang_id = ? 
        AND tk.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $jumlah_hari = (int)$row['jumlah_hari'];
        $stmt->close();
        $conn->close();
        
        return $jumlah_hari >= $min_days;
    }
    
    $stmt->close();
    $conn->close();
    return false;
}
?>

