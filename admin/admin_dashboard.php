<?php
session_start();

require_once '../config/db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}


$query_nasabah = mysqli_query($conn, "SELECT COUNT(*) as total FROM data_nasabah");
if ($query_nasabah) {
    $data_nasabah = mysqli_fetch_assoc($query_nasabah);
    $total_nasabah = $data_nasabah['total'];
} else {
    $total_nasabah = 0;
}

$data_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$data_berat = [45, 59, 80, 81, 56, 95, 40]; 
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | Bank Sampah</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background: #2c3e50;
            color: #fff;
            position: sticky;
            top: 0;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #1a252f;
            text-align: center;
        }

        #sidebar ul li a {
            padding: 15px 20px;
            display: block;
            color: #bdc3c7;
            text-decoration: none;
            border-bottom: 1px solid #34495e;
            transition: 0.3s;
        }

        #sidebar ul li a:hover {
            background: #34495e;
            color: #fff;
            padding-left: 25px;
        }

        #sidebar ul li.active > a {
            background: #3498db;
            color: #fff;
        }

        .main-content {
            width: 100%;
            padding: 25px;
        }

        .admin-card {
            border: none;
            border-left: 5px solid #3498db;
            border-radius: 10px;
            background: #fff;
        }

        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h4 class="fw-bold m-0">Admin Panel</h4>
        </div>

        <ul class="list-unstyled components">
            <li class="active">
                <a href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
            </li>
            <li>
                <a href="data_nasabah.php"><i class="fas fa-users me-2"></i> Data Nasabah</a>
            </li>
            <li>
                <a href="admin_kelolasampah.php"><i class="fas fa-recycle me-2"></i> Kelola Sampah</a>
            </li>
            <li>
                <a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-2"></i> Kelola Berita</a>
            </li>
            <li>
                <a href="laporan.php"><i class="fas fa-file-invoice me-2"></i> Laporan</a>
            </li>
            <li>
                <a href="../auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Dashboard</h2>
            <div class="dropdown">
                <button class="btn btn-secondary btn-sm rounded-pill px-3 shadow-sm">
                    Login sebagai: <span class="fw-bold"><?= $_SESSION['nama']; ?></span>
                </button>
            </div>
        </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card admin-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">TOTAL NASABAH AKTIF</h6>
                    <h2 class="fw-bold mb-1"><?= $total_nasabah; ?></h2>
                    <small class="text-success"><i class="fas fa-arrow-up"></i> Terdaftar di sistem</small>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card admin-card shadow-sm h-100" style="border-left-color: #2ecc71;">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">SAMPAH TERKUMPUL (KG)</h6>
                    <h2 class="fw-bold mb-1">450 Kg</h2>
                    <small class="text-primary">Dari seluruh nasabah</small>
                </div>
            </div>
        </div>
            
        <div class="col-md-4 mb-3">
            <div class="card admin-card shadow-sm h-100" style="border-left-color: #f39c12;">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">ANTREAN PENARIKAN</h6>
                    <h2 class="fw-bold text-danger mb-1">5</h2>
                    <small class="text-muted">Perlu diproses segera</small>
                </div>
            </div>
        </div>
    </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="chart-container shadow-sm h-100">
                    <h5 class="fw-bold mb-4">Tren Setoran Sampah (Kg)</h5>
                    <canvas id="adminChart" height="150"></canvas>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="table-container shadow-sm h-100">
                    <div class="p-3 bg-white border-bottom">
                        <h5 class="fw-bold m-0">Aktivitas Terkini</h5>
                    </div>
                    <div class="p-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item p-3">
                                <strong>Moch Allan</strong> <span class="text-muted">Setor Plastik</span>
                                <div class="text-success small">10 Menit lalu</div>
                            </li>
                            <li class="list-group-item p-3">
                                <strong>Budi Santoso</strong> <span class="text-muted">Tarik Saldo</span>
                                <div class="text-warning small">1 Jam lalu</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('adminChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($data_hari); ?>,
            datasets: [{
                label: 'Berat Sampah (Kg)',
                data: <?= json_encode($data_berat); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

</body>
</html>