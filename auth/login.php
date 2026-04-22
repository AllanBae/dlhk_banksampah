<?php
session_start();
include '../config/db.php';
$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    if ($username && $password) {
        $queryAdmin = mysqli_query($conn, "SELECT * FROM admins WHERE usernameAdmin='$username'");
        $admin = mysqli_fetch_assoc($queryAdmin);

        if ($admin && md5($password) == $admin['passwordAdmin']) {
            $_SESSION['idAdmin'] = $admin['idAdmin'];
            $_SESSION['nama']    = $admin['namaAdmin']; 
            $_SESSION['role']    = 'admin';
            header("Location: ../admin/admin_dashboard.php"); 
            exit;
        } 

        else {
            $queryUser = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
            $user = mysqli_fetch_assoc($queryUser);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['id']       = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama']; 
                $_SESSION['role']     = $user['role'];
                header("Location: ../user/user_dashboard.php");
                exit;
            } else {
                $error = "Username atau Password salah!";
            }
        }
    } else { 
        $error = "Isi semua kolom!"; 
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bank Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --primary: #0ea5e9; --primary-dark: #0284c7; --biru-muda-bg: #e3f2fd; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }

        .navbar-custom { background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); border-bottom: 2px solid var(--biru-muda-bg); }
        .navbar-brand { color: var(--primary) !important; font-weight: 800; font-size: 22px; }
        .nav-link.nav-modern { color: #475569; font-weight: 600; padding: 10px 15px; }

        @media (max-width: 991.98px) {
            .navbar-collapse { 
                background: #fff; 
                margin-top: 10px; 
                border: 1px solid #e2e8f0; 
                border-radius: 12px;
                padding: 10px;
            }
            .navbar-nav { align-items: flex-start !important; }
            .nav-item { width: 100%; border-bottom: 1px solid #f1f5f9; }
            .nav-item:last-child { border-bottom: none; }
            .btn-daftar { margin-left: 0 !important; margin-top: 10px; width: 100%; text-align: left !important; }
        }

        .btn-daftar { background: var(--primary); color: #fff !important; border-radius: 8px; padding: 8px 20px !important; margin-left: 10px; }

        .main-content { min-height: 100vh; display: flex; align-items: center; padding-top: 80px; }
        .login-container { max-width: 400px; margin: auto; width: 100%; padding: 15px; }
        .login-form { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .form-title { font-weight: 800; color: var(--primary); margin-bottom: 25px; text-align: center; }
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
        <div class="login-container">
            <div class="login-form">
                <h2 class="form-title">Login</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small py-2"><?= $error ?></div>
                <?php endif; ?>
                <form action="" method="POST">
                    <div class="mb-3"><label class="form-label small fw-bold">USERNAME</label><input type="text" name="username" class="form-control form-control-custom" required></div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">PASSWORD</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control form-control-custom" required>
                            <span class="input-group-text bg-white" id="togglePassword" style="cursor:pointer; border-radius: 0 12px 12px 0;"><i class="bi bi-eye"></i></span>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn btn-custom shadow">Masuk</button>
                </form>
                <p class="text-center mt-4 mb-0 small">Belum punya akun? <a href="signup.php" class="text-primary fw-bold text-decoration-none">Daftar Sekarang</a></p>
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