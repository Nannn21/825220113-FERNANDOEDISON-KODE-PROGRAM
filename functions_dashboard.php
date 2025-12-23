<?php

require_once 'config.php';

function getTotalBarang() {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as total FROM Barang";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total = (int)$row['total'];
        $conn->close();
        return $total;
    }
    
    $conn->close();
    return 0;
}

function getTotalBarangMasuk($bulan = null, $tahun = null) {
    $conn = getDBConnection();
    
    if ($bulan !== null && $tahun !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiMasuk WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $stmt->bind_param("ii", $bulan, $tahun);
    } elseif ($bulan !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiMasuk WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = YEAR(CURRENT_DATE)");
        $stmt->bind_param("i", $bulan);
    } else {
        $sql = "SELECT COUNT(*) as total FROM TransaksiMasuk";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $total = (int)$row['total'];
            $conn->close();
            return $total;
        }
        $conn->close();
        return 0;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total = (int)$row['total'];
        $stmt->close();
        $conn->close();
        return $total;
    }
    
    $stmt->close();
    $conn->close();
    return 0;
}

function getTotalBarangKeluar($bulan = null, $tahun = null) {
    $conn = getDBConnection();
    
    if ($bulan !== null && $tahun !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiKeluar WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $stmt->bind_param("ii", $bulan, $tahun);
    } elseif ($bulan !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM TransaksiKeluar WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = YEAR(CURRENT_DATE)");
        $stmt->bind_param("i", $bulan);
    } else {
        $sql = "SELECT COUNT(*) as total FROM TransaksiKeluar";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $total = (int)$row['total'];
            $conn->close();
            return $total;
        }
        $conn->close();
        return 0;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total = (int)$row['total'];
        $stmt->close();
        $conn->close();
        return $total;
    }
    
    $stmt->close();
    $conn->close();
    return 0;
}

function getBarangMasukKeluarByMonth($months = 3) {
    $conn = getDBConnection();
    $data = [];

    $nama_bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    if ($months === 'all' || $months === '0') {
        
        $sql_masuk = "
            SELECT 
                MONTH(tm.tanggal) as bulan,
                YEAR(tm.tanggal) as tahun,
                COALESCE(SUM(dm.jumlah), 0) as total_masuk
            FROM TransaksiMasuk tm
            LEFT JOIN DetailMasuk dm ON tm.masuk_id = dm.masuk_id
            GROUP BY YEAR(tm.tanggal), MONTH(tm.tanggal)
            ORDER BY tahun ASC, bulan ASC
        ";
        $stmt_masuk = $conn->prepare($sql_masuk);
        $stmt_masuk->execute();
    } else {
        $sql_masuk = "
            SELECT 
                MONTH(tm.tanggal) as bulan,
                YEAR(tm.tanggal) as tahun,
                COALESCE(SUM(dm.jumlah), 0) as total_masuk
            FROM TransaksiMasuk tm
            LEFT JOIN DetailMasuk dm ON tm.masuk_id = dm.masuk_id
            WHERE tm.tanggal >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(tm.tanggal), MONTH(tm.tanggal)
            ORDER BY tahun ASC, bulan ASC
        ";
        $stmt_masuk = $conn->prepare($sql_masuk);
        $months_int = (int)$months;
        $stmt_masuk->bind_param("i", $months_int);
        $stmt_masuk->execute();
    }
    
    $result_masuk = $stmt_masuk->get_result();
    
    $data_masuk = [];
    while ($row = $result_masuk->fetch_assoc()) {
        $key = $row['tahun'] . '-' . $row['bulan'];
        $data_masuk[$key] = [
            'bulan' => $nama_bulan[(int)$row['bulan']],
            'bulan_num' => (int)$row['bulan'],
            'tahun' => (int)$row['tahun'],
            'masuk' => (int)$row['total_masuk']
        ];
    }
    $stmt_masuk->close();

    if ($months === 'all' || $months === '0') {
        
        $sql_keluar = "
            SELECT 
                MONTH(tk.tanggal) as bulan,
                YEAR(tk.tanggal) as tahun,
                COALESCE(SUM(dk.jumlah), 0) as total_keluar
            FROM TransaksiKeluar tk
            LEFT JOIN DetailKeluar dk ON tk.keluar_id = dk.keluar_id
            GROUP BY YEAR(tk.tanggal), MONTH(tk.tanggal)
            ORDER BY tahun ASC, bulan ASC
        ";
        $stmt_keluar = $conn->prepare($sql_keluar);
        $stmt_keluar->execute();
    } else {
        $sql_keluar = "
            SELECT 
                MONTH(tk.tanggal) as bulan,
                YEAR(tk.tanggal) as tahun,
                COALESCE(SUM(dk.jumlah), 0) as total_keluar
            FROM TransaksiKeluar tk
            LEFT JOIN DetailKeluar dk ON tk.keluar_id = dk.keluar_id
            WHERE tk.tanggal >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(tk.tanggal), MONTH(tk.tanggal)
            ORDER BY tahun ASC, bulan ASC
        ";
        $stmt_keluar = $conn->prepare($sql_keluar);
        $months_int = (int)$months;
        $stmt_keluar->bind_param("i", $months_int);
        $stmt_keluar->execute();
    }
    
    $result_keluar = $stmt_keluar->get_result();
    
    $data_keluar = [];
    while ($row = $result_keluar->fetch_assoc()) {
        $key = $row['tahun'] . '-' . $row['bulan'];
        $data_keluar[$key] = (int)$row['total_keluar'];
    }
    $stmt_keluar->close();

    $all_keys = array_unique(array_merge(array_keys($data_masuk), array_keys($data_keluar)));
    sort($all_keys);
    
    foreach ($all_keys as $key) {
        $masuk = isset($data_masuk[$key]) ? $data_masuk[$key]['masuk'] : 0;
        $keluar = isset($data_keluar[$key]) ? $data_keluar[$key] : 0;
        
        if (isset($data_masuk[$key])) {
            
            $bulan_label = $data_masuk[$key]['bulan'];
            if ($months === 'all' || $months === '0' || (int)$months > 12) {
                $bulan_label = $data_masuk[$key]['bulan'] . ' ' . $data_masuk[$key]['tahun'];
            }
            $data[] = [
                'bulan' => $bulan_label,
                'masuk' => $masuk,
                'keluar' => $keluar
            ];
        } else {
            
            list($tahun, $bulan_num) = explode('-', $key);
            $bulan_label = $nama_bulan[(int)$bulan_num];
            if ($months === 'all' || $months === '0' || (int)$months > 12) {
                $bulan_label = $nama_bulan[(int)$bulan_num] . ' ' . $tahun;
            }
            $data[] = [
                'bulan' => $bulan_label,
                'masuk' => $masuk,
                'keluar' => $keluar
            ];
        }
    }

    if (empty($data) && $months !== 'all' && $months !== '0') {
        $current_month = (int)date('n');
        $current_year = (int)date('Y');
        $months_int = (int)$months;
        
        for ($i = $months_int - 1; $i >= 0; $i--) {
            $month = $current_month - $i;
            $year = $current_year;
            
            if ($month <= 0) {
                $month += 12;
                $year -= 1;
            }
            
            $data[] = [
                'bulan' => $nama_bulan[$month],
                'masuk' => 0,
                'keluar' => 0
            ];
        }
    }
    
    $conn->close();
    return $data;
}

function getBarangStokMinimum() {
    $conn = getDBConnection();
    
    $sql = "
        SELECT 
            b.barang_id,
            b.nama_barang,
            b.stok,
            b.Safety_Stock,
            b.ROP,
            kb.nama_kategori,
            br.nama_brand
        FROM Barang b
        INNER JOIN KategoriBarang kb ON b.kategori_id = kb.kategori_id
        INNER JOIN Brand br ON b.brand_id = br.brand_id
        WHERE b.stok <= b.Safety_Stock OR b.stok <= b.ROP
        ORDER BY b.stok ASC
    ";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'barang_id' => (int)$row['barang_id'],
                'nama_barang' => $row['nama_barang'],
                'stok' => (int)$row['stok'],
                'Safety_Stock' => (int)$row['Safety_Stock'],
                'ROP' => (int)$row['ROP'],
                'nama_kategori' => $row['nama_kategori'],
                'nama_brand' => $row['nama_brand']
            ];
        }
    }
    
    $conn->close();
    return $data;
}

function getAllBarangWithDetails() {
    $conn = getDBConnection();
    
    $sql = "
        SELECT 
            b.barang_id,
            b.nama_barang,
            b.stok,
            b.Safety_Stock,
            b.ROP,
            b.harga_jual,
            b.harga_jual_satuan,
            kb.kategori_id,
            kb.nama_kategori,
            br.brand_id,
            br.nama_brand,
            r.rasa_id,
            r.nama_rasa,
            u.ukuran_id,
            u.nama_ukuran,
            u.satuan
        FROM Barang b
        INNER JOIN KategoriBarang kb ON b.kategori_id = kb.kategori_id
        INNER JOIN Brand br ON b.brand_id = br.brand_id
        LEFT JOIN Rasa r ON b.rasa_id = r.rasa_id
        INNER JOIN Ukuran u ON b.ukuran_id = u.ukuran_id
        ORDER BY b.barang_id ASC
    ";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'barang_id' => (int)$row['barang_id'],
                'nama_barang' => $row['nama_barang'],
                'stok' => (int)$row['stok'],
                'Safety_Stock' => (int)$row['Safety_Stock'],
                'ROP' => (int)$row['ROP'],
                'harga_jual' => (float)$row['harga_jual'],
                'harga_jual_satuan' => $row['harga_jual_satuan'] ? (float)$row['harga_jual_satuan'] : null,
                'kategori_id' => (int)$row['kategori_id'],
                'nama_kategori' => $row['nama_kategori'],
                'brand_id' => (int)$row['brand_id'],
                'nama_brand' => $row['nama_brand'],
                'rasa_id' => $row['rasa_id'] ? (int)$row['rasa_id'] : null,
                'nama_rasa' => $row['nama_rasa'] ?? null,
                'ukuran_id' => (int)$row['ukuran_id'],
                'nama_ukuran' => $row['nama_ukuran'],
                'satuan' => $row['satuan']
            ];
        }
    }
    
    $conn->close();
    return $data;
}

function searchBarang($keyword) {
    $conn = getDBConnection();
    
    $search_term = '%' . $keyword . '%';
    $sql = "
        SELECT 
            b.barang_id,
            b.nama_barang,
            b.stok,
            b.Safety_Stock,
            b.ROP,
            b.harga_jual,
            b.harga_jual_satuan,
            kb.kategori_id,
            kb.nama_kategori,
            br.brand_id,
            br.nama_brand,
            r.rasa_id,
            r.nama_rasa,
            u.ukuran_id,
            u.nama_ukuran,
            u.satuan
        FROM Barang b
        INNER JOIN KategoriBarang kb ON b.kategori_id = kb.kategori_id
        INNER JOIN Brand br ON b.brand_id = br.brand_id
        LEFT JOIN Rasa r ON b.rasa_id = r.rasa_id
        INNER JOIN Ukuran u ON b.ukuran_id = u.ukuran_id
        WHERE b.nama_barang LIKE ?
        ORDER BY b.barang_id ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'barang_id' => (int)$row['barang_id'],
                'nama_barang' => $row['nama_barang'],
                'stok' => (int)$row['stok'],
                'Safety_Stock' => (int)$row['Safety_Stock'],
                'ROP' => (int)$row['ROP'],
                'harga_jual' => (float)$row['harga_jual'],
                'harga_jual_satuan' => $row['harga_jual_satuan'] ? (float)$row['harga_jual_satuan'] : null,
                'kategori_id' => (int)$row['kategori_id'],
                'nama_kategori' => $row['nama_kategori'],
                'brand_id' => (int)$row['brand_id'],
                'nama_brand' => $row['nama_brand'],
                'rasa_id' => $row['rasa_id'] ? (int)$row['rasa_id'] : null,
                'nama_rasa' => $row['nama_rasa'] ?? null,
                'ukuran_id' => (int)$row['ukuran_id'],
                'nama_ukuran' => $row['nama_ukuran'],
                'satuan' => $row['satuan']
            ];
        }
    }
    
    $stmt->close();
    $conn->close();
    return $data;
}

function getBarangMasukStats($barang_id = null) {
    $conn = getDBConnection();
    
    if ($barang_id !== null) {
        $sql = "
            SELECT 
                b.barang_id,
                b.nama_barang,
                COALESCE(SUM(dm.jumlah), 0) as total_masuk
            FROM Barang b
            LEFT JOIN DetailMasuk dm ON b.barang_id = dm.barang_id
            WHERE b.barang_id = ?
            GROUP BY b.barang_id, b.nama_barang
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $barang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'barang_id' => (int)$row['barang_id'],
                'nama_barang' => $row['nama_barang'],
                'total_masuk' => (int)$row['total_masuk']
            ];
            $stmt->close();
            $conn->close();
            return $data;
        }
        
        $stmt->close();
        $conn->close();
        return null;
    } else {
        $sql = "
            SELECT 
                b.barang_id,
                b.nama_barang,
                COALESCE(SUM(dm.jumlah), 0) as total_masuk
            FROM Barang b
            LEFT JOIN DetailMasuk dm ON b.barang_id = dm.barang_id
            GROUP BY b.barang_id, b.nama_barang
            ORDER BY total_masuk DESC
        ";
        
        $result = $conn->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'barang_id' => (int)$row['barang_id'],
                    'nama_barang' => $row['nama_barang'],
                    'total_masuk' => (int)$row['total_masuk']
                ];
            }
        }
        
        $conn->close();
        return $data;
    }
}

function getBarangKeluarStats($barang_id = null) {
    $conn = getDBConnection();
    
    if ($barang_id !== null) {
        $sql = "
            SELECT 
                b.barang_id,
                b.nama_barang,
                COALESCE(SUM(dk.jumlah), 0) as total_keluar
            FROM Barang b
            LEFT JOIN DetailKeluar dk ON b.barang_id = dk.barang_id
            WHERE b.barang_id = ?
            GROUP BY b.barang_id, b.nama_barang
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $barang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'barang_id' => (int)$row['barang_id'],
                'nama_barang' => $row['nama_barang'],
                'total_keluar' => (int)$row['total_keluar']
            ];
            $stmt->close();
            $conn->close();
            return $data;
        }
        
        $stmt->close();
        $conn->close();
        return null;
    } else {
        $sql = "
            SELECT 
                b.barang_id,
                b.nama_barang,
                COALESCE(SUM(dk.jumlah), 0) as total_keluar
            FROM Barang b
            LEFT JOIN DetailKeluar dk ON b.barang_id = dk.barang_id
            GROUP BY b.barang_id, b.nama_barang
            ORDER BY total_keluar DESC
        ";
        
        $result = $conn->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'barang_id' => (int)$row['barang_id'],
                    'nama_barang' => $row['nama_barang'],
                    'total_keluar' => (int)$row['total_keluar']
                ];
            }
        }
        
        $conn->close();
        return $data;
    }
}
?>

