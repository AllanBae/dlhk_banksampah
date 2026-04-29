<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : "Admin";

// Ambil data statistik
$total_nasabah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM data_nasabah WHERE role = 'nasabah'"))['total'];
$total_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(saldo) as total FROM data_nasabah WHERE role = 'nasabah'"))['total'];

// Data tren bulanan
$data_berat_bulan = array_fill(0, 12, 0); 
$tahun_ini = date('Y');
$q_tren_bulan = mysqli_query($conn, "SELECT MONTH(tanggal) as bulan, SUM(berat) as total_berat FROM transaksi WHERE YEAR(tanggal) = '$tahun_ini' GROUP BY MONTH(tanggal)");
while($row = mysqli_fetch_assoc($q_tren_bulan)){
    $data_berat_bulan[$row['bulan'] - 1] = $row['total_berat'];
}
$json_data_bulan = json_encode($data_berat_bulan); 

// Top data
$q_top_sampah = mysqli_query($conn, "SELECT hs.jenis_sampah, SUM(t.berat) as total_berat FROM transaksi t JOIN harga_sampah hs ON t.id_sampah = hs.id GROUP BY t.id_sampah ORDER BY total_berat DESC LIMIT 5");
$q_top_nasabah = mysqli_query($conn, "SELECT dn.nama_lengkap, dn.username, COUNT(t.id) as frekuensi, SUM(t.berat) as total_berat FROM transaksi t JOIN data_nasabah dn ON t.id_nasabah = dn.id GROUP BY t.id_nasabah ORDER BY frekuensi DESC, total_berat DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        /* Sidebar Styling */
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); padding-left: 30px; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }
        
        #content { width: 100%; transition: all 0.3s; min-height: 100vh; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* Mobile Navbar Updates */
        .sidebar-overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1045; top: 0; left: 0; }
        
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; }
            #sidebar.active { margin-left: 0; }
            .sidebar-overlay.active { display: block; }
            .main-inner { padding: 15px; }
        }

        .badge-rank { width: 30px; height: 30px; background: var(--hijau-bg); color: var(--hijau-tua); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="d-flex"> 
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <i class="fas fa-leaf fs-3 me-2"></i>
            <h4 class="fw-bold m-0">EL HA KA</h4>
        </div>
        <ul class="list-unstyled components">
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'data_nasabah.php' ? 'active' : ''; ?>">
                <a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'data_setoran.php' ? 'active' : ''; ?>">
                <a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_kelolasampah.php' ? 'active' : ''; ?>">
                <a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_kelolaberita.php' ? 'active' : ''; ?>">
                <a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
                <a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a>
            </li>
            <li>
                <a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a>
            </li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Dashboard Statistik</h4>
            </div>

            <div class="d-flex align-items-center">
                <span class="text-muted fw-medium me-4 d-none d-md-inline">
                    <i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y'); ?>
                </span>
                
                <div class="bg-light px-3 py-2 rounded-pill shadow-sm border" style="border-color: rgba(26, 143, 58, 0.2) !important;">
                    <span class="text-muted small me-1 d-none d-sm-inline">Login Sebagai:</span>
                    <span class="fw-bold" style="color: var(--hijau-tua);">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nama_admin); ?>
                    </span>
                </div>
            </div>
        </nav>

        <div class="main-inner">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(154, 205, 50, 0.2); color: var(--hijau-tua);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Nasabah Aktif</h6>
                                <h3 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= $total_nasabah; ?> Orang</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(26, 143, 58, 0.1); color: var(--hijau-tua);">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Saldo Nasabah</h6>
                                <h3 class="fw-bold m-0" style="color: var(--hijau-tua);">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="glass-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0" style="color: var(--hijau-tua);"><i class="fas fa-chart-area me-2"></i>Tren Setoran (<?= $tahun_ini; ?>)</h5>
                            <span class="badge" style="background: var(--hijau-bg); color: var(--hijau-tua);">Kilogram (Kg)</span>
                        </div>
                        <canvas id="trendChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 border-top border-4" style="border-top-color: var(--hijau-tua) !important;">
                        <h5 class="fw-bold mb-4" style="color: var(--hijau-tua);"><i class="fas fa-medal me-2 text-warning"></i>Nasabah Terajin</h5>
                        <ul class="list-group list-group-flush">
                            <?php 
                            $no_n = 1;
                            if(mysqli_num_rows($q_top_nasabah) > 0):
                                while($dn = mysqli_fetch_assoc($q_top_nasabah)): 
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="badge-rank me-3 shadow-sm"><?= $no_n++; ?></div>
                                        <div>
                                            <h6 class="fw-bold m-0 text-dark"><?= $dn['nama_lengkap']; ?></h6>
                                            <small class="text-muted">@<?= $dn['username']; ?></small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge rounded-pill bg-success mb-1"><?= $dn['frekuensi']; ?>x Setor</span><br>
                                        <small class="fw-bold text-muted"><?= number_format($dn['total_berat'], 2, ',', '.'); ?> Kg</small>
                                    </div>
                                </li>
                            <?php endwhile; else: echo "<p class='text-muted'>Belum ada data.</p>"; endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 border-top border-4" style="border-top-color: var(--hijau-muda) !important;">
                        <h5 class="fw-bold mb-4" style="color: var(--hijau-tua);"><i class="fas fa-box-open me-2 text-info"></i>Sampah Terkumpul</h5>
                        <ul class="list-group list-group-flush">
                            <?php 
                            $no_s = 1;
                            if(mysqli_num_rows($q_top_sampah) > 0):
                                while($ds = mysqli_fetch_assoc($q_top_sampah)): 
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="badge-rank me-3 shadow-sm"><?= $no_s++; ?></div>
                                        <h6 class="fw-bold m-0 text-dark"><?= $ds['jenis_sampah']; ?></h6>
                                    </div>
                                    <h6 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= number_format($ds['total_berat'], 2, ',', '.'); ?> <span class="small text-muted">Kg</span></h6>
                                </li>
                            <?php endwhile; else: echo "<p class='text-muted'>Belum ada data.</p>"; endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Logic Sidebar Mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const overlay = document.getElementById('overlay');

    if(sidebarCollapse) {
        sidebarCollapse.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // Chart.js
    const ctx = document.getElementById('trendChart').getContext('2d');
    const dataBerat = <?= $json_data_bulan; ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Total Berat (Kg)',
                data: dataBerat,
                backgroundColor: 'rgba(154, 205, 50, 0.2)',
                borderColor: '#1A8F3A',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#1A8F3A',
                fill: true,
                tension: 0.4 
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
</body>
</html>