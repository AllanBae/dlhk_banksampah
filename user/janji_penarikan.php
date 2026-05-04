<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'nasabah') {
    header("Location: ../auth/login.php");
    exit();
}

$username_session = $_SESSION['username'];
$query_n = mysqli_query($conn, "SELECT id, nama_lengkap FROM data_nasabah WHERE username='$username_session'");
$data_n = mysqli_fetch_assoc($query_n);
$id_nasabah = $data_n['id'];

$query_p = mysqli_query($conn, "SELECT * FROM penarikan WHERE user_id='$id_nasabah' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Janji Penarikan | EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --hijau-utama: #1A8F3A;
            --bg-canvas: #F8FAFC;
        }
        body { 
            background-color: var(--bg-canvas); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #334155;
            padding-bottom: 100px;
        }

        /* Top Header */
        .header-section {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #E2E8F0;
            margin-bottom: 25px;
        }
        .btn-back {
            color: #64748B;
            text-decoration: none;
            font-size: 1.2rem;
            transition: 0.2s;
        }

        /* Card Styling */
        .status-box { 
            border-radius: 20px; 
            border: 1px solid #F1F5F9; 
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            overflow: hidden;
        }
        .status-box:active { transform: scale(0.98); }

        /* Left Accent Border */
        .accent-pending { border-left: 6px solid #FACC15; }
        .accent-diterima { border-left: 6px solid #22C55E; }
        .accent-ditolak { border-left: 6px solid #EF4444; }
        .accent-berhasil { border-left: 6px solid #3B82F6; }

        /* Badge Custom */
        .badge-status {
            font-weight: 700;
            padding: 6px 12px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Content Text */
        .amount-text { font-size: 1.1rem; font-weight: 800; color: #1E293B; }
        .date-text { font-size: 0.85rem; color: #64748B; font-weight: 500; }
        
        /* Box Alasan */
        .reason-box {
            background: #FFF1F2;
            border-radius: 12px;
            padding: 12px;
            margin-top: 15px;
        }

        /* Bottom Nav (Sesuai tema menu lain) */
        .bottom-nav {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #fff; height: 65px; border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 9999; display: flex; justify-content: space-around; align-items: center;
        }
        .nav-item-mobile { text-decoration: none; text-align: center; color: #94A3B8; flex: 1; }
        .nav-item-mobile i { font-size: 1.2rem; display: block; }
        .nav-item-mobile span { font-size: 9px; font-weight: 700; display: block; }
        .nav-item-mobile.active { color: var(--hijau-utama); }
    </style>
</head>
<body>

<div class="header-section shadow-sm">
    <div class="container d-flex align-items-center">
        <a href="saldo.php" class="btn-back me-3"><i class="fas fa-chevron-left"></i></a>
        <h5 class="fw-800 mb-0">Status Janji Penarikan</h5>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <?php if(mysqli_num_rows($query_p) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($query_p)): ?>
                
                <div class="card status-box p-3 mb-3 accent-<?= $row['status']; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 date-text">
                                <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($row['tanggal_penarikan'])); ?>
                            </p>
                            <h4 class="amount-text mb-0">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></h4>
                        </div>
                        
                        <?php
                            // Logic warna badge
                            $bg_badge = 'bg-warning text-dark';
                            if($row['status'] == 'diterima') $bg_badge = 'bg-success text-white';
                            if($row['status'] == 'ditolak') $bg_badge = 'bg-danger text-white';
                            if($row['status'] == 'berhasil') $bg_badge = 'bg-primary text-white';
                        ?>
                        <span class="badge rounded-pill badge-status <?= $bg_badge; ?>">
                            <?= ucfirst($row['status']); ?>
                        </span>
                    </div>

                    <?php if($row['status'] == 'ditolak'): ?>
                        <div class="reason-box">
                            <small class="text-danger fw-bold d-block mb-1"><i class="fas fa-info-circle me-1"></i> ALASAN PENOLAKAN:</small>
                            <p class="small mb-0 text-dark"><?= $row['alasan_tolak']; ?></p>
                        </div>
                    <?php elseif($row['status'] == 'diterima'): ?>
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex align-items-center text-success small fw-600">
                                <i class="fas fa-check-circle me-2"></i>
                                <span>Janji disetujui. Silahkan datang ke kantor.</span>
                            </div>
                        </div>
                    <?php elseif($row['status'] == 'pending'): ?>
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex align-items-center text-muted small fw-500">
                                <i class="fas fa-clock me-2"></i>
                                <span>Menunggu respon admin...</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-file-invoice-dollar fa-4x text-light"></i>
                    </div>
                    <h6 class="text-muted fw-bold">Belum ada riwayat janji</h6>
                    <p class="small text-muted">Janji penarikan yang Anda buat akan muncul di sini.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="bottom-nav">
    <a href="user_dashboard.php" class="nav-item-mobile active">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="riwayat.php" class="nav-item-mobile">
        <i class="fas fa-history"></i>
        <span>Riwayat Setoran</span>
    </a>
    <a href="saldo.php" class="nav-item-mobile">
        <i class="fas fa-wallet"></i>
        <span>Penarikan</span>
    </a>
    <a href="profil.php" class="nav-item-mobile">
        <i class="fas fa-user"></i>
        <span>Profil</span>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>