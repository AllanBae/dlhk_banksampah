<?php
session_start();
include '../config/db.php';

// Proteksi Halaman
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'nasabah') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id'];
$username_session = $_SESSION['username'];
$message = "";

// 1. Ambil data profil & saldo terbaru
$query_n = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE username='$username_session'");
$data = mysqli_fetch_assoc($query_n);
$saldo_aktif = (int)($data['saldo'] ?? 0);
$id_nasabah = $data['id'];

// 2. Proses Buat Janji Penarikan
if (isset($_POST['buat_janji'])) {
    $jumlah = (int)$_POST['jumlah'];
    $tgl_janji = mysqli_real_escape_string($conn, $_POST['tgl_janji']);

    if ($jumlah > $saldo_aktif) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Gagal! Saldo Anda tidak mencukupi.</div>";
    } elseif ($jumlah < 5000) {
        $message = "<div class='alert alert-warning border-0 shadow-sm'>Minimal penarikan Rp 5.000</div>";
    } else {
        $sql = "INSERT INTO penarikan (user_id, tanggal_penarikan, jumlah, status) 
                VALUES ('$id_nasabah', '$tgl_janji', '$jumlah', 'pending')";
        if (mysqli_query($conn, $sql)) {
            $message = "<div class='alert alert-success border-0 shadow-sm text-center'>
                            <i class='fas fa-check-circle me-1'></i> Janji berhasil dibuat!
                        </div>";
            
            // Refresh saldo tampilan
            $query_n = mysqli_query($conn, "SELECT saldo FROM data_nasabah WHERE id='$id_nasabah'");
            $data_refresh = mysqli_fetch_assoc($query_n);
            $saldo_aktif = (int)$data_refresh['saldo'];
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
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #2ecc71; --bg-soft: #f8fafc; }
        body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; padding-bottom: 100px; }
        
        /* Navbar Styling */
        .navbar-desktop { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid #e2e8f0; }
        .nav-link { font-weight: 600; color: #64748b !important; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--hijau-tua) !important; }

        .card-saldo {
            background: linear-gradient(135deg, var(--hijau-tua) 0%, var(--hijau-muda) 100%);
            border-radius: 24px; border: none; color: white; padding: 30px;
            box-shadow: 0 10px 20px rgba(26, 143, 58, 0.2);
        }

        .form-control { border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; font-weight: 600; }
        .bottom-nav {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #ffffff; height: 65px; display: none;
            border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 9999; justify-content: space-around; align-items: center;
        }
        .nav-item-mobile { text-decoration: none; text-align: center; color: #94a3b8; flex: 1; font-size: 9px; font-weight: 700; }
        .nav-item-mobile i { font-size: 1.2rem; display: block; }
        .nav-item-mobile.active { color: var(--hijau-tua); }

        @media (max-width: 991px) {
            .navbar-desktop { display: none; }
            .bottom-nav { display: flex; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top navbar-desktop py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold text-success" href="user_dashboard.php">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" height="40" class="me-2"> 
            <span>EL HA KA</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link active" href="saldo.php">Saldo & Penarikan</a></li>
                <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat Setoran</a></li>
                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a class="btn btn-outline-danger rounded-pill px-4 fw-bold w-100" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            
            <div class="card card-saldo mb-4">
                <p class="mb-1 opacity-75">Saldo Aktif Anda</p>
                <h1 class="fw-bold mb-0">Rp <?= number_format($saldo_aktif, 0, ',', '.'); ?></h1>
                <div class="mt-3 small">
                    <i class="fas fa-info-circle me-1"></i> Penarikan dilakukan di kantor DLHK.
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-success">Form Penarikan</h5>
                    <a href="janji_penarikan.php" class="btn btn-sm btn-light rounded-pill text-success fw-bold">Riwayat</a>
                </div>

                <?= $message; ?>

                <form action="" method="POST" id="formTarik">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOMINAL (RP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" id="jumlahInput" name="jumlah" class="form-control border-start-0" placeholder="50000" required>
                        </div>
                        <div id="noteSaldo" class="text-danger mt-2 fw-bold" style="display: none; font-size: 12px;">
                            <i class="fas fa-exclamation-circle me-1"></i> Saldo Anda tidak mencukupi!
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">RENCANA TANGGAL DATANG</label>
                        <input type="date" name="tgl_janji" class="form-control" min="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <button type="submit" id="btnKonfirmasi" name="buat_janji" class="btn btn-success w-100 fw-bold p-3 rounded-3 shadow-sm mt-2" style="background: var(--hijau-tua);">
                        Konfirmasi Janji Penarikan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="bottom-nav">
    <a href="user_dashboard.php" class="nav-item-mobile">
        <i class="fas fa-home"></i><span>Home</span>
    </a>
    <a href="riwayat.php" class="nav-item-mobile">
        <i class="fas fa-history"></i><span>Riwayat</span>
    </a>
    <a href="saldo.php" class="nav-item-mobile active">
        <i class="fas fa-wallet"></i><span>Penarikan</span>
    </a>
    <a href="profil.php" class="nav-item-mobile">
        <i class="fas fa-user"></i><span>Profil</span>
    </a>
</div>

<script>
    const saldoUser = <?= $saldo_aktif; ?>;
    const inputNominal = document.getElementById('jumlahInput');
    const noteSaldo = document.getElementById('noteSaldo');
    const btnKonfirmasi = document.getElementById('btnKonfirmasi');

    inputNominal.addEventListener('input', function() {
        const value = parseInt(this.value) || 0;

        if (value > saldoUser) {
            noteSaldo.style.display = 'block';
            this.classList.add('is-invalid');
            btnKonfirmasi.disabled = true;
            btnKonfirmasi.innerText = 'Saldo Tidak Cukup';
            btnKonfirmasi.style.opacity = '0.6';
        } else {
            noteSaldo.style.display = 'none';
            this.classList.remove('is-invalid');
            btnKonfirmasi.disabled = false;
            btnKonfirmasi.innerText = 'Konfirmasi Janji Penarikan';
            btnKonfirmasi.style.opacity = '1';
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>