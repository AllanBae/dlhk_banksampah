<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'nasabah') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id'];
$username_session = $_SESSION['username'];
$message = "";

// Ambil data profil & saldo
$query_n = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE username='$username_session'");
$data = mysqli_fetch_assoc($query_n);
$saldo_aktif = $data['saldo'] ?? 0;
$id_nasabah = $data['id'];

// Proses Buat Janji Penarikan
if (isset($_POST['buat_janji'])) {
    $jumlah = mysqli_real_escape_string($conn, $_POST['jumlah']);
    $tgl_janji = mysqli_real_escape_string($conn, $_POST['tgl_janji']);

    if ($jumlah > $saldo_aktif) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Saldo tidak mencukupi!</div>";
    } elseif ($jumlah < 5000) {
        $message = "<div class='alert alert-warning border-0 shadow-sm'>Minimal penarikan Rp 5.000</div>";
    } else {
        $sql = "INSERT INTO penarikan (user_id, tanggal_penarikan, jumlah, status) 
                VALUES ('$id_nasabah', '$tgl_janji', '$jumlah', 'pending')";
        if (mysqli_query($conn, $sql)) {
            $message = "<div class='alert alert-success border-0 shadow-sm'>Janji berhasil dibuat! Silahkan datang pada tanggal tersebut.</div>";
            // Refresh data saldo tampil
            $saldo_aktif = $data['saldo']; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Saldo & Penarikan | EL HA KA</title>
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

        
        /* BOTTOM NAV MOBILE (FLOATING) */
        .bottom-nav {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #ffffff; height: 65px; display: none;
            border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 9999; justify-content: space-around; align-items: center;
            border: 1px solid #f1f5f9;
        }
        .nav-item-mobile { text-decoration: none; text-align: center; color: #94a3b8; flex: 1; transition: 0.3s; }
        .nav-item-mobile i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
        .nav-item-mobile span { font-size: 9px; font-weight: 700; text-transform: uppercase; display: block; }
        .nav-item-mobile.active { color: var(--hijau-tua); }

        /* HEADER MOBILE */
        .header-mobile { display: none; padding: 15px 0; align-items: center; justify-content: space-between; }
        .btn-logout-mobile { 
            width: 35px; height: 35px; border-radius: 10px; background: #fff1f2; color: #e11d48; 
            display: flex; align-items: center; justify-content: center; text-decoration: none; border: none;
        }

        .card-saldo {
            background: linear-gradient(135deg, #1A8F3A 0%, #2ecc71 100%);
            border-radius: 24px; border: none; color: white; padding: 30px;
        }

        .form-control { border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; }

        @media (max-width: 991px) {
            .navbar-desktop { display: none; }
            .bottom-nav { display: flex; }
            .header-mobile { display: flex; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top navbar-desktop py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="#">EL HA KA</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="saldo.php">Saldo</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                <li class="nav-item ms-3"><a class="btn btn-outline-danger rounded-pill px-4" href="../auth/logout.php">Keluar</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-2 mt-lg-4">
    <div class="header-mobile">
        <div class="d-flex align-items-center">
            <img src="../assets/uploads/<?= $data['foto']; ?>" class="rounded-circle border border-2 border-white shadow-sm" width="40" height="40" style="object-fit: cover;">
            <div class="ms-2">
                <h6 class="fw-bold mb-0"><?= $data['nama_lengkap']; ?></h6>
            </div>
        </div>
            <a href="janji_penarikan.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
            <i class="fas fa-calendar-check me-1"></i> Janji Saya
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card card-saldo shadow-lg mb-4">
                <p class="mb-1 opacity-75">Saldo Anda</p>
                <h1 class="fw-800 mb-0">Rp <?= number_format($saldo_aktif, 0, ',', '.'); ?></h1>
                <div class="mt-3 small">
                    <i class="fas fa-info-circle me-1"></i> Saldo dapat ditarik tunai di kantor.
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4">
                <h5 class="fw-bold mb-3">Buat Janji Penarikan</h5>
                <?= $message; ?>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOMINAL PENARIKAN (RP)</label>
                        <input type="number" name="jumlah" class="form-control fw-bold" placeholder="Contoh: 50000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TANGGAL KEDATANGAN</label>
                        <input type="date" name="tgl_janji" class="form-control" min="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="info-box bg-light p-3 rounded-3 mb-4" style="font-size: 11px;">
                        <p class="mb-0 text-muted italic">*) Dengan menekan tombol di bawah, Anda membuat janji temu dengan admin untuk pengambilan uang tunai di lokasi.</p>
                    </div>
                    <button type="submit" name="buat_janji" class="btn btn-success w-100 fw-bold p-3 rounded-3 shadow-sm" style="background: var(--hijau-tua);">
                        Konfirmasi Janji Penarikan
                    </button>
                </form>
            </div>
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

</body>
</html>