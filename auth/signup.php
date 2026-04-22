<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/db.php';

    $fullname = isset($_POST['nama_user']) ? trim($_POST['nama_user']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
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

                $stmt2 = $conn->prepare("INSERT INTO data_nasabah (nama_lengkap, username, email, no_telp, alamat, password, role, saldo) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt2->bind_param("sssssss", $fullname, $username, $email, $no_hp, $alamat, $passwordHash, $role);
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
        :root { --primary: #0ea5e9; --primary-dark: #0284c7; --biru-muda-bg: #e3f2fd; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }

        .navbar-custom { background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); border-bottom: 2px solid var(--biru-muda-bg); }
        .navbar-brand { color: var(--primary) !important; font-weight: 800; font-size: 22px; }
        .nav-link.nav-modern { color: #475569; font-weight: 600; padding: 10px 15px; transition: 0.3s; }
        .nav-link.nav-modern:hover { color: var(--primary); }

        @media (max-width: 991.98px) {
            .navbar-collapse { 
                background: #fff; 
                margin-top: 10px; 
                border: 1px solid #e2e8f0; 
                border-radius: 12px;
                padding: 10px;
            }
            .navbar-nav { align-items: flex-start !important; } /* Menu di kiri */
            .nav-item { width: 100%; border-bottom: 1px solid #f1f5f9; } /* Border bawah menu */
            .nav-item:last-child { border-bottom: none; }
            .btn-daftar { margin-left: 0 !important; margin-top: 10px; width: 100%; text-align: left !important; }
        }

        .btn-daftar { background: var(--primary); color: #fff !important; border-radius: 8px; padding: 8px 20px !important; margin-left: 10px; display: inline-block; }

        .main-content { min-height: 100vh; display: flex; align-items: center; padding: 100px 0 50px; }
        .signup-form { background: #fff; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-title { font-weight: 800; color: var(--primary); margin-bottom: 25px; }
        .form-control-custom { border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; }
        .btn-custom { border-radius: 12px; background: var(--primary); color: #fff; border: none; padding: 12px; font-weight: 600; width: 100%; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top navbar-custom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <i class="bi bi-recycle me-2"></i><span>Bank Sampah</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
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
                        <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?> border-0 shadow-sm mb-4"><?= $error ?: $success ?></div>
                    <?php endif; ?>
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3"><label class="form-label fw-bold small">NAMA LENGKAP</label><input type="text" name="nama_user" class="form-control form-control-custom" required></div>
                                <div class="mb-3"><label class="form-label fw-bold small">NO. WHATSAPP</label><input type="number" name="no_hp" class="form-control form-control-custom" required></div>
                                <div class="mb-3"><label class="form-label fw-bold small">ALAMAT</label><textarea name="alamat" class="form-control form-control-custom" style="height: 100px;" required></textarea></div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3"><label class="form-label fw-bold small">USERNAME</label><input type="text" name="username" class="form-control form-control-custom" required></div>
                                <div class="mb-3"><label class="form-label fw-bold small">EMAIL</label><input type="email" name="email" class="form-control form-control-custom" required></div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">PASSWORD</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control form-control-custom" required>
                                        <span class="input-group-text bg-white" id="togglePassword" style="cursor:pointer; border-radius: 0 12px 12px 0;"><i class="bi bi-eye"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-custom mt-3 shadow">Buat Akun</button>
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