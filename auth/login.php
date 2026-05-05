<?php
session_start();
include '../config/db.php';
$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // 1. CEK KE TABEL ADMINS DULU
        $queryAdmin = mysqli_query($conn, "SELECT * FROM admins WHERE usernameAdmin='$username'");
        $admin = mysqli_fetch_assoc($queryAdmin);

        if ($admin && md5($password) == $admin['passwordAdmin']) {
            $_SESSION['idAdmin']  = $admin['idAdmin'];
            $_SESSION['username'] = $admin['usernameAdmin']; 
            $_SESSION['nama']     = $admin['namaAdmin'];
            $_SESSION['role']     = 'admin'; 
            header("Location: ../admin/admin_dashboard.php");
            exit();
        } else {
            // 2. JIKA BUKAN ADMIN, CEK KE TABEL USERS
            $queryUser = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
            $user = mysqli_fetch_assoc($queryUser);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                if ($user['role'] == 'nasabah') {
                    $username_nasabah = $user['username'];
                    $queryNasabah = mysqli_query($conn, "SELECT id, nama_lengkap FROM data_nasabah WHERE username='$username_nasabah'");
                    $nasabah = mysqli_fetch_assoc($queryNasabah);
                    if ($nasabah) {
                        $_SESSION['id']   = $nasabah['id']; 
                        $_SESSION['nama'] = $nasabah['nama_lengkap']; 
                    } else {
                        $_SESSION['id']   = $user['id'];
                        $_SESSION['nama'] = $user['nama'];
                    }
                } else {
                    $_SESSION['id']   = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                }
                header("Location: ../user/user_dashboard.php");
                exit();
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
    <title>Masuk - Bank Sampah EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --hijau-tua: #1A8F3A;     
            --hijau-muda: #9ACD32;    
            --soft-bg: #f8fafc; 
        }

        body { 
            background-color: var(--soft-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* NAVBAR MODERN */
        .navbar-custom { 
            background: rgba(255, 255, 255, 0.9) !important; 
            backdrop-filter: blur(15px); 
            border-bottom: 1px solid rgba(0,0,0,0.05); 
        }
        .navbar-brand img { height: 45px; }

        /* NAVBAR MOBILE (STYLE BARU) */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white;
                margin-top: 15px;
                border-radius: 20px;
                padding: 15px;
                box-shadow: 0 15px 30px rgba(0,0,0,0.1);
                border: 1px solid rgba(0,0,0,0.05);
            }
            .nav-item { width: 100%; border-bottom: 1px solid #f8fafc; }
            .nav-item:last-child { border-bottom: none; }
            .nav-link { padding: 12px !important; border-radius: 10px; }
            .btn-daftar-nav { background: var(--hijau-tua) !important; color: white !important; margin-top: 5px; text-align: center !important; }
        }

        .btn-daftar-nav { 
            background: var(--hijau-tua); 
            color: #fff !important; 
            border-radius: 12px; 
            padding: 10px 25px !important; 
            font-weight: 600; 
            transition: 0.3s;
        }

        /* LOGIN CARD */
        .login-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px;
        }

        .login-card {
            background: white;
            padding: 45px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .login-header { text-align: center; margin-bottom: 35px; }
        .login-header h2 { font-weight: 800; color: var(--hijau-tua); margin-bottom: 10px; }
        .login-header p { color: #64748b; font-size: 0.95rem; }

        .form-label { font-weight: 600; font-size: 0.85rem; color: #475569; margin-bottom: 8px; letter-spacing: 0.5px; }
        
        .input-group-custom {
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 15px;
            transition: 0.3s;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 0 15px;
        }

        .input-group-custom:focus-within {
            border-color: var(--hijau-muda);
            background: white;
            box-shadow: 0 0 0 4px rgba(154, 205, 50, 0.1);
        }

        .input-group-custom i { color: #94a3b8; font-size: 1.2rem; }
        
        .form-control-blank {
            background: transparent;
            border: none;
            padding: 12px 10px;
            width: 100%;
            outline: none;
            color: #1e293b;
            font-weight: 500;
        }

        .btn-login {
            background: linear-gradient(45deg, var(--hijau-tua), var(--hijau-muda));
            color: white;
            border: none;
            border-radius: 15px;
            padding: 14px;
            width: 100%;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 10px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(26, 143, 58, 0.2);
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(26, 143, 58, 0.3); color: white; }

        .toggle-pass { cursor: pointer; color: #94a3b8; transition: 0.3s; }
        .toggle-pass:hover { color: var(--hijau-tua); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top navbar-custom py-3">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" alt="Logo">
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-grid-fill text-success fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link fw-bold mx-2" href="../index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link fw-bold mx-2" href="login.php" style="color: var(--hijau-tua);">Masuk</a></li>
                <li class="nav-item ms-lg-3"><a class="nav-link btn-daftar-nav px-4" href="signup.php">Daftar</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="login-section">
    <div class="login-card">
        <div class="login-header">
            <h2>Selamat Datang</h2>
            <p>Silakan masuk ke akun Bank Sampah Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 rounded-4 small py-3 mb-4 d-flex align-items-center">
                <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="form-label text-uppercase">Username</label>
                <div class="input-group-custom">
                    <i class="bi bi-person"></i>
                    <input type="text" name="username" class="form-control-blank" placeholder="Masukkan username" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-uppercase">Password</label>
                <div class="input-group-custom">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" id="password" class="form-control-blank" placeholder="••••••••" required>
                    <i class="bi bi-eye toggle-pass" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-login">
                Masuk Sekarang <i class="bi bi-arrow-right-short ms-1"></i>
            </button>
        </form>

        <div class="text-center mt-5">
            <span class="text-muted small">Belum punya akun?</span> 
            <a href="signup.php" class="text-success fw-bold text-decoration-none small ms-1">Buat Akun Baru</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Password Visibility
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    
    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>