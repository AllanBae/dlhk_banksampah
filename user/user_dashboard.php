<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'nasabah') {
    header("Location: ../auth/login.php");
    exit;
}

$data_bulan = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun"];
$data_setoran = [10, 15, 8, 20, 25, 18]; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Nasabah</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .navbar-brand {
            color: #0ea5e9 !important;
            font-weight: 700;
        }

        .nav-link {
            color: #64748b !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: #0ea5e9 !important;
        }

        .welcome-section {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.2);
        }

        .stat-card {
            border: none;
            border-radius: 15px;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" alt="Logo" height="80" class="me-2">
            <span>BANK SAMPAH EL HA KA</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="#">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="saldo.php">Saldo</a></li>
                <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat</a></li>
                <li class="nav-item"><a class="nav-link" href="setor.php">Setor Sampah</a></li>
                <li class="nav-item"><a class="nav-link" href="penarikan.php">Penarikan</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-outline-danger btn-sm rounded-pill" href="../auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="welcome-section">
        <h2 class="fw-bold">Halo, <?= $_SESSION['nama']; ?> 👋</h2>
        <p class="mb-0 opacity-75">Senang melihatmu kembali. Ayo terus berkontribusi untuk lingkungan yang lebih bersih!</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Saldo</h6>
                    <h3 class="fw-bold text-primary">Rp 150.000</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Sampah Terkumpul</h6>
                    <h3 class="fw-bold text-success">42.5 Kg</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Transaksi Bulan Ini</h6>
                    <h3 class="fw-bold text-info">12 Kali</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="chart-container">
                <h5 class="fw-bold mb-4">Statistik Setoran Sampah (Kg)</h5>
                <canvas id="myChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('myChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($data_bulan); ?>,
            datasets: [{
                label: 'Berat Sampah (Kg)',
                data: <?= json_encode($data_setoran); ?>,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

</body>
</html>