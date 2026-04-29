<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/db.php';

    $fullname = isset($_POST['nama_user']) ? trim($_POST['nama_user']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $instansi = isset($_POST['instansi']) ? trim($_POST['instansi']) : ''; 
    $no_hp    = isset($_POST['no_hp']) ? trim($_POST['no_hp']) : '';
    $alamat   = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($fullname && $username && $password) {
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'nasabah';
            $conn->begin_transaction();

            try {
                $stmt1 = $conn->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt1->bind_param("ssss", $fullname, $username, $passwordHash, $role);
                $stmt1->execute();

                $stmt2 = $conn->prepare("INSERT INTO data_nasabah (nama_lengkap, username, dinas_instansi, no_telp, alamat, password, role, saldo) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt2->bind_param("sssssss", $fullname, $username, $instansi, $no_hp, $alamat, $passwordHash, $role);
                $stmt2->execute();

                $conn->commit();
                $success = "Registrasi Berhasil! Silakan Login.";
                header("refresh:2;url=login.php");
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Bank Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { 
            --hijau-tua: #1A8F3A;     
            --hijau-muda: #9ACD32;    
            --hijau-bg-muda: #f0f8f1; 
        }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .navbar-custom { background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); border-bottom: 2px solid var(--hijau-bg-muda); }
        .navbar-brand { color: var(--hijau-tua) !important; font-weight: 800; font-size: 22px; }
        .nav-link.nav-modern { color: #475569; font-weight: 600; padding: 10px 15px; transition: 0.3s; }
        .btn-daftar { background: var(--hijau-tua); color: #fff !important; border-radius: 8px; padding: 8px 20px !important; margin-left: 10px; display: inline-block; transition: 0.3s; }
        .main-content { min-height: 100vh; display: flex; align-items: center; padding: 100px 0 50px; }
        .signup-form { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-title { font-weight: 800; color: var(--hijau-tua); margin-bottom: 30px; }
        .form-control-custom { border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; transition: 0.3s; }
        .form-control-custom:focus { border-color: var(--hijau-muda); box-shadow: 0 0 0 0.25rem rgba(154, 205, 50, 0.25); outline: none; }
        .btn-custom { border-radius: 12px; background: var(--hijau-tua); color: #fff; border: none; padding: 14px; font-weight: 600; width: 100%; transition: 0.3s; }
        .btn-custom:hover { background: var(--hijau-muda); transform: translateY(-2px); }
        .text-hijau { color: var(--hijau-tua); }
        .form-control-custom::placeholder {
        color: #a0a0a0; 
        opacity: 1;}
        .form-control-custom {
        color: #6c757d; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top navbar-custom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" alt="Logo" height="80" class="me-2">
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link nav-modern" href="../index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link nav-modern" href="login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link btn-daftar shadow-sm" href="signup.php">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="signup-form">
                    <h2 class="text-center form-title">Daftar Nasabah Baru</h2>
                    
                    <?php if ($error || $success): ?>
                        <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?> border-0 shadow-sm mb-4 text-center rounded-3">
                            <?= $error ?: $success ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div><label class="form-label fw-bold small text-muted mb-1">NAMA LENGKAP</label><input type="text" name="nama_user" class="form-control form-control-custom" placeholder="Masukkan nama lengkap" required></div>
                                <div class="mt-3"><label class="form-label fw-bold small text-muted mb-1">NO. WHATSAPP</label><input type="number" name="no_hp" class="form-control form-control-custom" placeholder="Contoh: 08123456789" required></div>
                                <div class="mt-3"><label class="form-label fw-bold small text-muted mb-1">ALAMAT</label><textarea name="alamat" class="form-control form-control-custom" placeholder="Alamat lengkap" style="height: 115px;" required></textarea></div>
                            </div>
                            <div class="col-md-6">
                                <div><label class="form-label fw-bold small text-muted mb-1">USERNAME</label><input type="text" name="username" class="form-control form-control-custom" placeholder="Buat username" required></div>
                                <div class="mt-3">
                                    <label class="form-label fw-bold small text-muted mb-1">DINAS/INSTANSI (Opsional)</label>
                                    <input type="text" name="instansi" class="form-control form-control-custom text-secondary" placeholder="Kosongkan jika tidak ada">
                                </div>                               
                                <div class="mt-3">
                                    <label class="form-label fw-bold small text-muted mb-1">PASSWORD</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control form-control-custom" placeholder="Buat kata sandi" required style="border-right: none;">
                                        <span class="input-group-text bg-white form-control-custom" id="togglePassword" style="cursor:pointer; border-radius: 0 12px 12px 0; border-left: none;">
                                            <i class="bi bi-eye text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 text-center">
                            <button type="submit" class="btn btn-custom shadow-sm fs-5">Buat Akun Sekarang</button>
                            <p class="mt-3 small">Sudah punya akun? <a href="login.php" class="text-hijau fw-bold text-decoration-none">Login di sini</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>