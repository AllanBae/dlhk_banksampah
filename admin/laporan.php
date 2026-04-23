<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$total_nasabah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM data_nasabah WHERE role = 'nasabah'"))['total'];
$total_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(saldo) as total FROM data_nasabah WHERE role = 'nasabah'"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tahunan | Admin Bank Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        #sidebar { min-width: 250px; background: #2c3e50; color: #fff; min-height: 100vh; position: sticky; top: 0; }
        #sidebar .sidebar-header { padding: 25px; background: #1a252f; text-align: center; }
        #sidebar ul li a { padding: 15px 20px; display: block; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { background: #3498db; color: #fff; }
        
        .main-content { width: 100%; padding: 30px; }
        .report-header { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-stat { border: none; border-radius: 12px; color: white; transition: transform 0.3s; }
        .card-stat:hover { transform: translateY(-5px); }
        
        @media print {
            #sidebar, .btn-print, .no-print { display: none !important; }
            .main-content { padding: 0; }
            .card-stat { color: black !important; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h4 class="fw-bold m-0">Admin Panel</h4>
        </div>
        <ul class="list-unstyled">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-2"></i> Data Nasabah</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-2"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-2"></i> Kelola Berita</a></li>
            <li class="active"><a href="laporan.php"><i class="fas fa-file-invoice me-2"></i> Laporan</a></li>
            <li><a href="../auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="report-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold m-0 text-dark">Laporan Inventaris & Keuangan</h2>
                <p class="text-muted m-0">Data terbaru per tanggal: <?= date('d F Y'); ?></p>
            </div>
            <button onclick="window.print()" class="btn btn-primary btn-print">
                <i class="fas fa-print me-2"></i> Cetak Laporan
            </button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card card-stat bg-primary p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="m-0">Total Nasabah Aktif</h5>
                            <h2 class="fw-bold m-0"><?= $total_nasabah; ?> Orang</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-stat bg-success p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="fas fa-wallet fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="m-0">Total Tabungan Nasabah</h5>
                            <h2 class="fw-bold m-0">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-list me-2 text-primary"></i>Rincian Saldo Nasabah</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>No. Telp</th>
                                <th class="text-end">Saldo Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $query = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE role = 'nasabah' ORDER BY nama_lengkap ASC");
                            while($d = mysqli_fetch_assoc($query)) :
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="fw-bold"><?= $d['nama_lengkap']; ?></td>
                                <td><span class="badge bg-light text-dark">@<?= $d['username']; ?></span></td>
                                <td><?= $d['no_telp']; ?></td>
                                <td class="text-end fw-bold text-success">Rp <?= number_format($d['saldo'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                                <td class="text-end text-primary">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center text-muted no-print">
            <small>&copy; 2026 Bank Sampah DLHK - Laporan ini digenerate secara otomatis oleh sistem.</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>