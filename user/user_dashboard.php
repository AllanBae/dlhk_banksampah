<?php
session_start();
include '../config/db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'nasabah') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id']; 

// 1. Ambil data profil & saldo
$query_profil = mysqli_query($conn, "SELECT nama_lengkap, saldo, foto FROM data_nasabah WHERE id = '$user_id'");
$data_profil = mysqli_fetch_assoc($query_profil);

if ($data_profil) {
    $nama_user = $data_profil['nama_lengkap'];
    $total_saldo = $data_profil['saldo'];
    $foto_user = !empty($data_profil['foto']) ? $data_profil['foto'] : 'default.png';
} else {
    $nama_user = $_SESSION['nama'];
    $total_saldo = 0;
    $foto_user = 'default.png';
}

// 2. Statistik Sampah
$query_sampah = mysqli_query($conn, "SELECT SUM(berat) as total_berat FROM transaksi WHERE id_nasabah = '$user_id'");
$data_sampah = mysqli_fetch_assoc($query_sampah);
$total_berat = $data_sampah['total_berat'] ?? 0;

// 3. Statistik Penarikan Bulan Ini
$query_penarikan = mysqli_query($conn, "SELECT COUNT(*) as total FROM penarikan WHERE user_id = '$user_id' AND MONTH(tanggal_penarikan) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_penarikan) = YEAR(CURRENT_DATE())");
$data_penarikan = mysqli_fetch_assoc($query_penarikan);
$transaksi_bulan_ini = $data_penarikan['total'] ?? 0;

// 4. Data Grafik (6 Bulan Terakhir)
$labels_grafik = []; $data_grafik = [];
for ($i = 5; $i >= 0; $i--) {
    $tgl = date('Y-m', strtotime("-$i month"));
    $labels_grafik[] = date('M', strtotime("-$i month"));
    $q = mysqli_query($conn, "SELECT SUM(berat) as b FROM transaksi WHERE id_nasabah = '$user_id' AND DATE_FORMAT(tanggal, '%Y-%m') = '$tgl'");
    $r = mysqli_fetch_assoc($q);
    $data_grafik[] = $r['b'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Dashboard | EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --bg-soft: #f8fafc; }
        body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; padding-bottom: 100px; }

        /* NAVBAR DESKTOP */
        .navbar-desktop { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid #e2e8f0; }
        .nav-link { font-weight: 600; color: #64748b !important; transition: 0.3s; padding: 10px 15px !important; }
        .nav-link.active, .nav-link:hover { color: var(--hijau-tua) !important; }

        /* BOTTOM NAV MOBILE */
        .bottom-nav {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #ffffff; height: 65px; display: none;
            border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 9999; justify-content: space-around; align-items: center;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .nav-item-mobile { text-decoration: none; text-align: center; color: #94a3b8; flex: 1; transition: 0.3s; }
        .nav-item-mobile i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
        .nav-item-mobile span { font-size: 9px; font-weight: 700; display: block; }
        .nav-item-mobile.active { color: var(--hijau-tua); }

        /* CARDS */
        .header-mobile { display: none; padding: 15px 0; align-items: center; justify-content: space-between; }
        .btn-logout-mobile { 
            width: 35px; height: 35px; border-radius: 10px; background: #fff1f2; color: #e11d48; 
            display: flex; align-items: center; justify-content: center; text-decoration: none;
        }
        .stat-card { border: none; border-radius: 20px; background: white; transition: 0.4s; }
        .icon-circle { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }

        @media (max-width: 991px) {
            .navbar-desktop { display: none !important; }
            .bottom-nav { display: flex; }
            .header-mobile { display: flex; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top navbar-desktop py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold text-success" href="user_dashboard.php">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" height="40" class="me-2"> EL HA KA
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link active" href="user_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="saldo.php">Saldo & Penarikan</a></li>
                <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat Setoran</a></li>
                <li class="nav-item ms-3"><a class="btn btn-outline-danger rounded-pill px-4 fw-bold" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="bottom-nav">
    <a href="user_dashboard.php" class="nav-item-mobile active">
        <i class="fas fa-home"></i><span>Home</span>
    </a>
    <a href="riwayat.php" class="nav-item-mobile">
        <i class="fas fa-history"></i><span>Riwayat</span>
    </a>
    <a href="saldo.php" class="nav-item-mobile">
        <i class="fas fa-wallet"></i><span>Penarikan</span>
    </a>
    <a href="profil.php" class="nav-item-mobile">
        <i class="fas fa-user"></i><span>Profil</span>
    </a>
</div>

<div class="container mt-2 mt-lg-4">
    <div class="header-mobile">
        <div class="d-flex align-items-center">
            <a href="profil.php">
                <img src="../assets/uploads/<?= $foto_user; ?>" 
                class="rounded-circle border border-2 border-white shadow-sm" width="50" height="50" style="object-fit: cover;" 
                onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>'">
            </a>
                <div class="ms-2">
                    <p class="text-muted small mb-0">Halo, Selamat Datang</p>
                    <h6 class="fw-bold mb-0"><?= $nama_user; ?></h6>
                </div>
        </div>
        <a href="../auth/logout.php" class="btn-logout-mobile shadow-sm" onclick="return confirm('Yakin ingin keluar?')">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card stat-card p-3 shadow-sm h-100">
                <div class="icon-circle bg-success bg-opacity-10 text-success mb-2"><i class="fas fa-wallet"></i></div>
                <p class="text-muted small fw-bold mb-1 text-uppercase">Saldo</p>
                <h4 class="fw-bold m-0 text-success">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></h4>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card stat-card p-3 shadow-sm h-100">
                <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-2"><i class="fas fa-recycle"></i></div>
                <p class="text-muted small fw-bold mb-1 text-uppercase">Setoran</p>
                <h4 class="fw-bold m-0 text-dark"><?= number_format($total_berat, 1, ',', '.'); ?> <small class="fs-6 text-muted">Kg</small></h4>
            </div>
        </div>

        <div class="col-12 col-lg-4 mb-3"> 
            <div class="card stat-card p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small fw-bold mb-1 text-uppercase">Penarikan Bulan Ini</p>
                        <h4 class="fw-bold m-0 text-dark"><?= $transaksi_bulan_ini; ?> <small class="fs-6 text-muted">Kali</small></h4>
                    </div>
                    <div class="icon-circle bg-info bg-opacity-10 text-info">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

    <div class="card stat-card p-4 mb-5 shadow-sm">
        <h6 class="fw-bold mb-4 text-muted"><i class="fas fa-chart-line me-2 text-success"></i>PROGRES SETORAN (Kg)</h6>
        <div style="height: 250px;"><canvas id="chartNasabah"></canvas></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('chartNasabah').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_grafik); ?>,
            datasets: [{
                label: 'Berat Sampah (Kg)',
                data: <?= json_encode($data_grafik); ?>,
                borderColor: '#1A8F3A',
                backgroundColor: 'rgba(26, 143, 58, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#1A8F3A'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
</body>
</html>