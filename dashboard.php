<?php

require_once 'config.php';
require_once 'functions_dashboard.php';

requireLogin();

$current_page = 'dashboard.php';

$user = getCurrentUser();

$chart_months = isset($_GET['chart_months']) ? $_GET['chart_months'] : '3';
$stok_limit = isset($_GET['stok_limit']) ? $_GET['stok_limit'] : '10';

if (!in_array($chart_months, ['1', '3', 'all'])) {
    $chart_months = '3';
}
if (!in_array($stok_limit, ['3', '5', '10', '20', 'all'])) {
    $stok_limit = '10';
}

$totalBarang = getTotalBarang();
$totalBarangMasuk = getTotalBarangMasuk();
$totalBarangKeluar = getTotalBarangKeluar();
$grafikData = getBarangMasukKeluarByMonth($chart_months);
$stokMinimumAll = getBarangStokMinimum();

$stokMinimum = $stok_limit === 'all' ? $stokMinimumAll : array_slice($stokMinimumAll, 0, (int)$stok_limit);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventaris Barang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            padding: 30px;
            max-height: 100vh;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333333;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: #FFFFFF;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #FFFFFF;
            margin-right: 15px;
        }
        
        .card-icon.blue {
            background-color: #3B82F6;
        }
        
        .card-icon.green {
            background-color: #10B981;
        }
        
        .card-icon.yellow {
            background-color: #F59E0B;
        }
        
        .card-content {
            flex: 1;
        }
        
        .card-label {
            font-size: 0.9rem;
            color: #666666;
            margin-bottom: 5px;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333333;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: #FFFFFF;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 400px;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333333;
            margin: 0;
        }
        
        .chart-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-controls label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .chart-controls select {
            padding: 8px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
            min-width: 120px;
        }
        
        .chart-controls select:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            flex: 1;
            min-height: 300px;
            width: 100%;
        }
        
        .chart-container canvas {
            max-height: 100%;
        }

        .table-card {
            background: #FFFFFF;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            height: 100%;
            max-height: 600px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333333;
            margin: 0;
        }
        
        .table-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-controls label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .table-controls select {
            padding: 8px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
            min-width: 100px;
        }
        
        .table-controls select:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .table-wrapper {
            overflow-y: auto;
            flex: 1;
            border: 1px solid #F0F0F0;
            border-radius: 6px;
            max-height: calc(100vh - 400px);
        }

        .table-wrapper.limit-3 {
            max-height: 180px;
        }
        
        .table-wrapper.limit-5 {
            max-height: 280px;
        }
        
        .table-wrapper.limit-10 {
            max-height: 500px;
        }
        
        .table-wrapper.limit-20 {
            max-height: 600px;
        }
        
        .table-wrapper.limit-all {
            max-height: calc(100vh - 400px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }
        
        th {
            background-color: #F5F5F5;
            color: #666666;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #E0E0E0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #F0F0F0;
            color: #333333;
        }
        
        tr:hover {
            background-color: #F9F9F9;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: #F59E0B;
            color: #FFFFFF;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            min-width: 40px;
            text-align: center;
        }
        
        .badge.critical {
            background-color: #EF4444;
        }
        
        .badge.warning {
            background-color: #F59E0B;
        }
        
        .badge.info {
            background-color: #3B82F6;
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="background: #E8F5E9; border-left: 4px solid #4CAF50; color: #2E7D32; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" style="background: #FFEBEE; border-left: 4px solid #F44336; color: #C62828; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-icon blue">
                    <i class="fas fa-list"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Semua Barang</div>
                    <div class="card-value"><?php echo $totalBarang; ?></div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon green">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Barang Masuk</div>
                    <div class="card-value"><?php echo $totalBarangMasuk; ?></div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon yellow">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Barang Keluar</div>
                    <div class="card-value"><?php echo $totalBarangKeluar; ?></div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="chart-card">
                <div class="chart-header">
                    <h2 class="chart-title">Grafik Barang Masuk dan Keluar</h2>
                    <div class="chart-controls">
                        <label for="chartMonths">Periode:</label>
                        <select id="chartMonths" onchange="changeChartPeriod(this.value)">
                            <option value="1" <?php echo $chart_months == '1' ? 'selected' : ''; ?>>1 Bulan</option>
                            <option value="3" <?php echo $chart_months == '3' ? 'selected' : ''; ?>>3 Bulan</option>
                            <option value="all" <?php echo $chart_months == 'all' ? 'selected' : ''; ?>>Semua</option>
                        </select>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="barangChart"></canvas>
                    <div id="chartError" style="display: none; text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>Tidak ada data untuk ditampilkan</p>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h2 class="table-title">Stok Mencapai Batas Minimum</h2>
                    <div class="table-controls">
                        <label for="stokLimit">Tampilkan:</label>
                        <select id="stokLimit" onchange="changeStokLimit(this.value)">
                            <option value="3" <?php echo $stok_limit == '3' ? 'selected' : ''; ?>>3 Item</option>
                            <option value="5" <?php echo $stok_limit == '5' ? 'selected' : ''; ?>>5 Item</option>
                            <option value="10" <?php echo $stok_limit == '10' ? 'selected' : ''; ?>>10 Item</option>
                            <option value="20" <?php echo $stok_limit == '20' ? 'selected' : ''; ?>>20 Item</option>
                            <option value="all" <?php echo $stok_limit == 'all' ? 'selected' : ''; ?>>Semua</option>
                        </select>
                    </div>
                </div>
                <div class="table-wrapper limit-<?php echo $stok_limit === 'all' ? 'all' : $stok_limit; ?>">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th style="width: 100px;">ID</th>
                                <th>Nama Barang</th>
                                <th style="width: 80px;">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stokMinimum)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle" style="color: #10B981;"></i>
                                            <p>Tidak ada barang dengan stok minimum</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $total_stok_minimum = count($stokMinimumAll);
                                $showing_count = count($stokMinimum);
                                ?>
                                <?php foreach ($stokMinimum as $index => $item): ?>
                                    <?php
                                    
                                    $badge_class = 'info';
                                    if ($item['stok'] <= 0) {
                                        $badge_class = 'critical';
                                    } elseif ($item['stok'] <= $item['ROP']) {
                                        $badge_class = 'critical';
                                    } elseif ($item['stok'] <= $item['Safety_Stock']) {
                                        $badge_class = 'warning';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $item['barang_id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $item['stok']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($stok_limit !== 'all' && $total_stok_minimum > $showing_count): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 15px; color: #666; font-style: italic; background: #FAFAFA;">
                                            Menampilkan <?php echo $showing_count; ?> dari <?php echo $total_stok_minimum; ?> item. 
                                            <a href="?chart_months=<?php echo $chart_months; ?>&stok_limit=all" style="color: #3B82F6; text-decoration: none; font-weight: 500;">Tampilkan semua</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function changeChartPeriod(months) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('chart_months', months);
            
            const stokLimit = document.getElementById('stokLimit').value;
            currentUrl.searchParams.set('stok_limit', stokLimit);
            window.location.href = currentUrl.toString();
        }

        function changeStokLimit(limit) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('stok_limit', limit);
            const chartMonths = document.getElementById('chartMonths').value;
            currentUrl.searchParams.set('chart_months', chartMonths);
            window.location.href = currentUrl.toString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const chartData = <?php echo json_encode($grafikData); ?>;
            
            if (!chartData || chartData.length === 0) {
                console.warn('No chart data available');
                const errorDiv = document.getElementById('chartError');
                const canvas = document.getElementById('barangChart');
                if (errorDiv) errorDiv.style.display = 'block';
                if (canvas) canvas.style.display = 'none';
                return;
            }
            
            const labels = chartData.map(item => item.bulan);
            const dataMasuk = chartData.map(item => item.masuk || 0);
            const dataKeluar = chartData.map(item => item.keluar || 0);

            const canvas = document.getElementById('barangChart');
            if (!canvas) {
                console.error('Canvas element not found');
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Could not get 2D context');
                return;
            }

            const barangChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: dataMasuk,
                        backgroundColor: '#3B82F6',
                        borderRadius: 5
                    },
                    {
                        label: 'Barang Keluar',
                        data: dataKeluar,
                        backgroundColor: '#EF4444',
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F0F0F0'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
            });
        });
    </script>
</body>
</html>
