<?php
session_start();
include '../config/db.php';

// Proteksi Halaman
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'nasabah') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id'];

// Query Riwayat: Menggabungkan transaksi dengan jenis sampah dari tabel harga_sampah
// Sesuaikan t.id_sampah jika nama kolom penghubung di tabel transaksi berbeda
$query_riwayat = mysqli_query($conn, "SELECT t.*, h.jenis_sampah 
                                      FROM transaksi t 
                                      LEFT JOIN harga_sampah h ON t.id_sampah = h.id 
                                      WHERE t.id_nasabah = '$id_user' 
                                      ORDER BY t.tanggal DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Riwayat Setoran | EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { 
            --hijau-tua: #1A8F3A; 
            --bg-soft: #f8fafc; 
            --slate-600: #475569;
            --slate-900: #0f172a;
        }

        body { 
            background-color: var(--bg-soft); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            padding-bottom: 100px; 
            color: var(--slate-900);
        }

        /* NAVBAR DESKTOP */
        .navbar-desktop { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px); 
            border-bottom: 1px solid #e2e8f0; 
        }
        .nav-link { 
            font-weight: 600; 
            color: #64748b !important; 
            transition: 0.3s; 
            padding: 10px 15px !important; 
        }
        .nav-link.active, .nav-link:hover { color: var(--hijau-tua) !important; }

        /* BOTTOM NAV MOBILE */
        .bottom-nav {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #ffffff; height: 65px; display: none;
            border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 9999; justify-content: space-around; align-items: center; border: 1px solid #f1f5f9;
        }
        .nav-item-mobile { text-decoration: none; text-align: center; color: #94a3b8; flex: 1; transition: 0.3s; }
        .nav-item-mobile i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
        .nav-item-mobile span { font-size: 9px; font-weight: 700; display: block; }
        .nav-item-mobile.active { color: var(--hijau-tua); }

        /* CARD RIWAYAT PREMIUM */
        .card-transaksi { 
            border: none; 
            border-radius: 24px; 
            transition: all 0.3s ease; 
            margin-bottom: 16px; 
            background: #ffffff;
            border: 1px solid #f1f5f9;
        }
        .card-transaksi:active { transform: scale(0.97); }

        .icon-box { 
            width: 52px; 
            height: 52px; 
            border-radius: 16px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.4rem;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: var(--hijau-tua);
        }

        .text-jenis {
            font-size: 0.95rem;
            color: var(--slate-900);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-weight: 700;
        }

        .text-amount {
            font-size: 1.1rem;
            font-weight: 800;
            color: #166534;
        }

        .meta-info { font-size: 0.8rem; color: #64748b; font-weight: 600; }

        .weight-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        @media (max-width: 991px) {
            .navbar-desktop { display: none !important; }
            .bottom-nav { display: flex; }
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
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="saldo.php">Saldo & Penarikan</a></li>
                <li class="nav-item"><a class="nav-link active" href="riwayat.php">Riwayat Setoran</a></li>
                <li class="nav-item ms-3">
                    <a class="btn btn-outline-danger rounded-pill px-4 fw-bold" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 px-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h5 class="fw-800 mb-0">Riwayat Setoran</h5>
        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;">
            Total: <?= mysqli_num_rows($query_riwayat); ?> Transaksi
        </span>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            
            <?php if (mysqli_num_rows($query_riwayat) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                    <div class="card card-transaksi shadow-sm p-3">
                        <div class="d-flex align-items-start">
                            <div class="icon-box me-3 flex-shrink-0">
                                <i class="fas fa-recycle"></i>
                            </div>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="text-jenis pe-2">
                                        <?= htmlspecialchars($row['jenis_sampah'] ?? 'Setoran Sampah'); ?>
                                    </div>
                                    <div class="text-amount text-nowrap">
                                        +<?= number_format($row['total_harga'] ?? 0, 0, ',', '.'); ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="meta-info">
                                        <i class="far fa-calendar-alt me-1 text-success"></i> 
                                        <?= date('d M Y', strtotime($row['tanggal'])); ?>
                                    </div>
                                    <div class="weight-badge">
                                        <i class="fas fa-weight-hanging me-1"></i>
                                        <?= number_format($row['berat'], 1, ',', '.'); ?> Kg
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-4x text-light mb-3"></i>
                    <h6 class="fw-bold text-muted">Belum ada transaksi</h6>
                    <p class="small text-muted">Data setoran sampah Anda akan muncul di sini.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="bottom-nav">
    <a href="user_dashboard.php" class="nav-item-mobile">
        <i class="fas fa-home"></i><span>Home</span>
    </a>
    <a href="riwayat.php" class="nav-item-mobile active">
        <i class="fas fa-history"></i><span>Riwayat</span>
    </a>
    <a href="saldo.php" class="nav-item-mobile">
        <i class="fas fa-wallet"></i><span>Tarik</span>
    </a>
    <a href="profil.php" class="nav-item-mobile">
        <i class="fas fa-user"></i><span>Profil</span>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>