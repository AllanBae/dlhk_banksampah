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
    <title>Daftar Nasabah - Bank Sampah EL HA KA</title>
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
        }

        /* NAVBAR */
        .navbar-custom { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(15px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .navbar-brand img { height: 45px; }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white; margin-top: 15px; border-radius: 20px; padding: 15px;
                box-shadow: 0 15px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);
            }
            .nav-item { width: 100%; border-bottom: 1px solid #f8fafc; }
            .nav-link { padding: 12px !important; }
            .btn-login-nav { background: var(--hijau-tua) !important; color: white !important; margin-top: 5px; text-align: center !important; border-radius: 12px; }
        }

        /* SIGNUP CARD */
        .main-content { min-height: 100vh; display: flex; align-items: center; padding: 120px 0 60px; }
        
        .signup-card {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.02);
        }

        .form-title { font-weight: 800; color: var(--hijau-tua); margin-bottom: 10px; }
        .form-subtitle { color: #64748b; margin-bottom: 40px; }

        .form-label { font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 8px; letter-spacing: 0.5px; }

        .input-group-custom {
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 15px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            padding: 0 15px;
            margin-bottom: 20px;
        }

        .input-group-custom:focus-within {
            border-color: var(--hijau-muda);
            background: white;
            box-shadow: 0 0 0 4px rgba(154, 205, 50, 0.1);
        }

        .input-group-custom i { color: #94a3b8; font-size: 1.1rem; }
        
        .form-control-blank {
            background: transparent;
            border: none;
            padding: 12px 10px;
            width: 100%;
            outline: none;
            color: #1e293b;
            font-weight: 500;
        }

        .btn-signup {
            background: linear-gradient(45deg, var(--hijau-tua), var(--hijau-muda));
            color: white;
            border: none;
            border-radius: 15px;
            padding: 15px;
            width: 100%;
            font-weight: 700;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(26, 143, 58, 0.2);
        }

        .btn-signup:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(26, 143, 58, 0.3); color: white; }

        .toggle-pass { cursor: pointer; color: #94a3b8; }
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
                <li class="nav-item"><a class="nav-link fw-bold mx-2" href="login.php">Masuk</a></li>
                <li class="nav-item ms-lg-3"><a class="nav-link btn-login-nav px-4 fw-bold" href="signup.php" style="color: var(--hijau-tua);">Daftar</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="signup-card">
                    <div class="text-center">
                        <h2 class="form-title">Daftar Nasabah Baru</h2>
                        <p class="form-subtitle">Mulai menabung cerdas dengan sampah rumah tangga</p>
                    </div>

                    <?php if ($error || $success): ?>
                        <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?> border-0 rounded-4 py-3 mb-4 text-center">
                            <i class="bi <?= $error ? 'bi-exclamation-triangle' : 'bi-check-circle' ?> me-2"></i>
                            <?= $error ?: $success ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label text-uppercase">Nama Lengkap</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-person"></i>
                                    <input type="text" name="nama_user" class="form-control-blank" placeholder="Nama sesuai KTP" required>
                                </div>

                                <label class="form-label text-uppercase">No. WhatsApp</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-whatsapp"></i>
                                    <input type="number" name="no_hp" class="form-control-blank" placeholder="0812xxxx" required>
                                </div>

                                <label class="form-label text-uppercase">Alamat</label>
                                <div class="input-group-custom align-items-start pt-2">
                                    <i class="bi bi-geo-alt mt-1"></i>
                                    <textarea name="alamat" class="form-control-blank" placeholder="Alamat lengkap rumah" style="height: 100px;" required></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-uppercase">Username</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-at"></i>
                                    <input type="text" name="username" class="form-control-blank" placeholder="username mudah diingat" required>
                                </div>

                                <label class="form-label text-uppercase">Dinas / Instansi <span class="text-muted" style="font-size: 0.7rem;">(Opsional)</span></label>
                                <div class="input-group-custom">
                                    <i class="bi bi-building"></i>
                                    <input type="text" name="instansi" class="form-control-blank" placeholder="Nama kantor/instansi">
                                </div>

                                <label class="form-label text-uppercase">Password</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" name="password" id="password" class="form-control-blank" placeholder="••••••••" required>
                                    <i class="bi bi-eye toggle-pass" id="togglePassword"></i>
                                </div>

                                <div class="mt-4 pt-2">
                                    <button type="submit" class="btn btn-signup">
                                        Buat Akun Sekarang <i class="bi bi-person-plus-fill ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted small">Sudah punya akun?</span> 
                        <a href="login.php" class="text-success fw-bold text-decoration-none small ms-1">Login di sini</a>
                    </div>
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
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>