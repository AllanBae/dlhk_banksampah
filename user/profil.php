<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();

include '../config/db.php';

// Proteksi Halaman
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'nasabah') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id'];
$message = "";
$redirect = false;

// 1. Ambil data profil
$username_session = $_SESSION['username'];
$query_nasabah = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE username='$username_session'");
$data = mysqli_fetch_assoc($query_nasabah);

// 2. Auto-insert jika data profil belum ada
if (!$data) {
    $nama_awal = $_SESSION['nama'];
    mysqli_query($conn, "INSERT INTO data_nasabah (nama_lengkap, username, dinas_instansi, role, saldo, foto) 
                         VALUES ('$nama_awal', '$username_session', '', 'nasabah', 0, 'default.png')");
    $query_nasabah = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE username='$username_session'");
    $data = mysqli_fetch_assoc($query_nasabah);
}

// 3. Logika Update Profil
if (isset($_POST['update_profil'])) {
    $nama_lengkap   = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username_baru  = mysqli_real_escape_string($conn, $_POST['username']);
    $dinas_instansi = mysqli_real_escape_string($conn, $_POST['dinas_instansi']);
    $no_telp        = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    $id_nasabah = $data['id'];
    $foto_db = $data['foto'];

    // 1. CEK APAKAH USERNAME DIUBAH?
    // Jika username baru tidak sama dengan username lama di DB
    if ($username_baru !== $data['username']) {
        $cek_username = mysqli_query($conn, "SELECT id FROM data_nasabah WHERE username='$username_baru' AND id != '$id_nasabah'");
        if (mysqli_num_rows($cek_username) > 0) {
            $message = "<div id='alert-msg' class='alert alert-danger'>Username sudah digunakan!</div>";
            $username_baru = $data['username']; // Kembalikan ke username lama jika duplikat
        }
    }

    // 2. LOGIKA FOTO (Hanya proses jika ada file diunggah)
    if ($_FILES['foto']['name'] != "") {
        $ekstensi_diperbolehkan = ['png', 'jpg', 'jpeg'];
        $x = explode('.', $_FILES['foto']['name']);
        $ekstensi = strtolower(end($x));
        
        if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
            $foto_baru = "user_" . $id_nasabah . "_" . time() . "." . $ekstensi;
            
            // Hapus foto fisik lama
            if ($foto_db != 'default.png' && file_exists("../assets/uploads/" . $foto_db)) {
                unlink("../assets/uploads/" . $foto_db);
            }
            
            move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/uploads/" . $foto_baru);
            $foto_db = $foto_baru;
        }
    }

    // 3. UPDATE DATABASE
    $sql_update = "UPDATE data_nasabah SET 
                    nama_lengkap='$nama_lengkap', 
                    username='$username_baru', 
                    dinas_instansi='$dinas_instansi', 
                    no_telp='$no_telp', 
                    alamat='$alamat',
                    foto='$foto_db' 
                   WHERE id='$id_nasabah'";
    
    if (mysqli_query($conn, $sql_update)) {
        // Update tabel utama users agar sinkron
        mysqli_query($conn, "UPDATE users SET username='$username_baru', nama='$nama_lengkap' WHERE id='$id_user'");
        
        // Perbarui Session
        $_SESSION['username'] = $username_baru;
        $_SESSION['nama'] = $nama_lengkap;
        
        $message = "<div id='alert-msg' class='alert alert-success'>Profil Berhasil Diperbarui!</div>";
        $redirect = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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

        /* CARD STYLE */ 
        .profile-card { border-radius: 25px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        .avatar-wrapper { position: relative; display: inline-block; }
        .avatar-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .info-label { color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-bottom: 8px; display: block; margin-top: 15px; }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 1px solid #e2e8f0; font-weight: 500; background-color: #fbfcfd; }
        .form-control:focus { border-color: var(--hijau-tua); box-shadow: none; background-color: #fff; }

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
        <a class="navbar-brand d-flex align-items-center fw-bold text-success" href="user_dahsboard.php">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" height="40" class="me-2"> EL HA KA
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="saldo.php">Saldo & Penarikan</a></li>
                <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat Setoran</a></li>
                <li class="nav-item ms-3"><a class="btn btn-outline-danger rounded-pill px-4 fw-bold" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a></li>
            </ul>
        </div>
    </div>
</nav>
           
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

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div id="alert-container"><?= $message; ?></div>

            <div class="card profile-card p-4 mb-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <div class="avatar-wrapper">
                            <img src="../assets/uploads/<?= ($data['foto'] ?: 'default.png'); ?>" class="avatar-preview" id="preview" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($data['username']); ?>'">
                            <label for="foto" class="btn btn-success btn-sm position-absolute bottom-0 end-0 rounded-circle shadow border-2 border-white p-2">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                            <input type="file" name="foto" id="foto" class="d-none" onchange="previewImg()">
                        </div>
                        <h4 class="fw-bold mt-3 mb-0">@<?= htmlspecialchars($data['username']); ?></h4>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 mt-2">Nasabah Aktif</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="info-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data['nama_lengkap']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="info-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']); ?>" required>
                        </div>
                    </div>

                    <label class="info-label">Dinas / Instansi</label>
                    <input type="text" name="dinas_instansi" class="form-control" placeholder="Contoh: DLHK Kota" value="<?= htmlspecialchars($data['dinas_instansi']); ?>">

                    <label class="info-label">No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($data['no_telp']); ?>">

                    <label class="info-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap..."><?= htmlspecialchars($data['alamat']); ?></textarea>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="update_profil" class="btn btn-success fw-bold p-3 rounded-3 shadow-sm border-0" style="background: var(--hijau-tua);">
                            Simpan Perubahan
                        </button>
                        <a href="user_dashboard.php" class="btn btn-light text-muted fw-bold py-2 border-0">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImg() {
        const foto = document.querySelector('#foto');
        const imgPreview = document.querySelector('#preview');
        const fileFoto = new FileReader();
        if (foto.files[0]) {
            fileFoto.readAsDataURL(foto.files[0]);
            fileFoto.onload = function(e) { imgPreview.src = e.target.result; }
        }
    }

    const alertMsg = document.getElementById('alert-msg');
    if (alertMsg) {
        setTimeout(() => {
            alertMsg.style.transition = "opacity 0.5s ease";
            alertMsg.style.opacity = "0";
            setTimeout(() => alertMsg.remove(), 500);
        }, 3000);
    }

    <?php if ($redirect): ?>
    setTimeout(() => { window.location.href = 'user_dashboard.php'; }, 2000);
    <?php endif; ?>
</script>
</body>
</html>