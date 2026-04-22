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

// 1. Ambil data profil dari data_nasabah
$username_session = $_SESSION['username'];
$query_nasabah = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE username='$username_session'");
$data = mysqli_fetch_assoc($query_nasabah);

// 2. Auto-insert jika data profil belum ada
if (!$data) {
    $nama_awal = $_SESSION['nama'];
    mysqli_query($conn, "INSERT INTO data_nasabah (nama_lengkap, username, email, role, saldo, foto) 
                         VALUES ('$nama_awal', '$username_session', '', 'nasabah', 0, 'default.png')");
    $query_nasabah = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE username='$username_session'");
    $data = mysqli_fetch_assoc($query_nasabah);
}

// 3. Logika Update Profil & Foto (dengan fitur Auto-Delete foto lama)
if (isset($_POST['update_profil'])) {
    $nama_lengkap  = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $email         = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp       = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat        = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    $id_nasabah = $data['id'];
    
    $cek_username = mysqli_query($conn, "SELECT id FROM data_nasabah WHERE username='$username_baru' AND id != '$id_nasabah'");
    
    if (mysqli_num_rows($cek_username) > 0) {
        $message = "<div id='alert-msg' class='alert alert-danger border-0 shadow-sm'>Username sudah digunakan!</div>";
    } else {
        // --- LOGIKA MANAJEMEN FOTO ---
        $foto_db = $data['foto'] ?? 'default.png'; // Ambil nama file lama dari DB

        if ($_FILES['foto']['name'] != "") {
            $ekstensi_diperbolehkan = ['png', 'jpg', 'jpeg'];
            $nama_asli = $_FILES['foto']['name'];
            $x = explode('.', $nama_asli);
            $ekstensi = strtolower(end($x));
            $file_tmp = $_FILES['foto']['tmp_name'];
            
            // Buat nama file unik agar tidak kena cache browser
            $foto_baru = "user_" . $id_nasabah . "_" . time() . "." . $ekstensi;

            if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
                
                // PROSES HAPUS FOTO LAMA (Clean up)
                if ($foto_db != 'default.png' && !empty($foto_db)) {
                    $path_foto_lama = "../assets/uploads/" . $foto_db;
                    if (file_exists($path_foto_lama)) {
                        unlink($path_foto_lama); // Menghapus file fisik dari folder uploads
                    }
                }

                // Upload file baru
                move_uploaded_file($file_tmp, "../assets/uploads/" . $foto_baru);
                $foto_db = $foto_baru; // Set nama file baru untuk disimpan ke DB
            }
        }

        // --- UPDATE DATABASE ---
        $sql_update = "UPDATE data_nasabah SET 
                        nama_lengkap='$nama_lengkap', 
                        username='$username_baru', 
                        email='$email', 
                        no_telp='$no_telp', 
                        alamat='$alamat',
                        foto='$foto_db' 
                       WHERE id='$id_nasabah'";
        
        if (mysqli_query($conn, $sql_update)) {
            mysqli_query($conn, "UPDATE users SET username='$username_baru', nama='$nama_lengkap' WHERE id='$id_user'");
            
            $_SESSION['username'] = $username_baru;
            $_SESSION['nama'] = $nama_lengkap;
            
            $message = "<div id='alert-msg' class='alert alert-success border-0 shadow-sm'>
                            <i class='bi bi-check-circle-fill'></i> Berhasil Memperbarui Profil..
                        </div>";
            $redirect = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - Bank Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .navbar { background: #fff; border-bottom: 1px solid #e2e8f0; }
        @media (max-width: 991px) {
            .navbar-nav { text-align: left !important; padding: 10px 0; align-items: flex-start !important; }
            .nav-item { width: 100%; border-bottom: 1px solid #f8fafc; }
        }
        .profile-card { border-radius: 24px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: #fff; padding: 30px; }
        .avatar-preview { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .info-label { color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; display: block; }
        .form-control { border-radius: 12px; padding: 10px 15px; border: 1px solid #e2e8f0; margin-bottom: 15px; font-weight: 500; }
        .info-box { background-color: #f8fafc; border-radius: 15px; padding: 15px; border: 1px solid #f1f5f9; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="user_dashboard.php">♻️ Bank Sampah</a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-start">
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="saldo.php">Saldo</a></li>
                <li class="nav-item active"><a class="nav-link text-primary fw-bold" href="profil.php">Profil</a></li>
                <li class="nav-item ms-lg-3"><a class="btn btn-outline-danger btn-sm rounded-pill px-4" href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div id="alert-container"><?= $message; ?></div>

            <div class="card profile-card">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <img src="../assets/uploads/<?= ($data['foto'] ?: 'default.png'); ?>" class="avatar-preview" id="preview" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($data['username']); ?>'">
                            <label for="foto" class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle shadow border-2 border-white"><i class="bi bi-camera"></i></label>
                            <input type="file" name="foto" id="foto" class="d-none" onchange="previewImg()">
                        </div>
                        <h3 class="fw-bold mt-3 mb-0">@<?= htmlspecialchars($data['username']); ?></h3>
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

                    <label class="info-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']); ?>">

                    <label class="info-label">No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($data['no_telp']); ?>">

                    <label class="info-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($data['alamat']); ?></textarea>

                    <div class="info-box mb-4">
                        <div class="info-label">Status</div>
                        <span class="badge bg-info text-white rounded-pill px-3">Nasabah</span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update_profil" class="btn btn-primary fw-bold p-3 rounded-3 shadow-sm">Simpan Perubahan</button>
                        <a href="user_dashboard.php" class="btn btn-light text-secondary py-2">Kembali</a>
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
    setTimeout(() => { window.location.href = 'user_dashboard.php'; }, 3000);
    <?php endif; ?>
</script>
</body>
</html>