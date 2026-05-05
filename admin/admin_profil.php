<?php
// ob_start mencegah error "headers already sent"
ob_start();
session_start();
include '../config/db.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Ambil identitas dari session
$session_user = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$session_id   = isset($_SESSION['idAdmin']) ? $_SESSION['idAdmin'] : null;

// 3. Query pencarian data admin (Sesuai kolom: usernameAdmin / idAdmin)
if ($session_user) {
    $query_admin = mysqli_query($conn, "SELECT * FROM admins WHERE usernameAdmin = '$session_user'");
} elseif ($session_id) {
    $query_admin = mysqli_query($conn, "SELECT * FROM admins WHERE idAdmin = '$session_id'");
} else {
    header("Location: ../auth/logout.php");
    exit();
}

$data = mysqli_fetch_assoc($query_admin);

if (!$data) {
    session_destroy();
    die("Error: Akun tidak ditemukan. Silakan <a href='../auth/login.php'>Login Kembali</a>.");
}

// --- LOGIKA UPDATE PROFIL ---
$status_msg = "";
if (isset($_POST['update_profil'])) {
    $nama_baru = mysqli_real_escape_string($conn, $_POST['namaAdmin']);
    $user_baru = mysqli_real_escape_string($conn, $_POST['usernameAdmin']);
    $pass_baru = $_POST['passwordAdmin'];
    $id_target = $data['idAdmin'];

    $sql = "UPDATE admins SET namaAdmin = '$nama_baru', usernameAdmin = '$user_baru'";
    
    if (!empty($pass_baru)) {
        // Menggunakan MD5 sesuai dengan logika login.php Anda sebelumnya
        $hash_pass = md5($pass_baru);
        $sql .= ", passwordAdmin = '$hash_pass'";
    }

    $sql .= " WHERE idAdmin = '$id_target'";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['username'] = $user_baru;
        $_SESSION['nama'] = $nama_baru;
        header("Location: admin_profil.php?status=sukses");
        exit();
    } else {
        $status_msg = "<div class='alert alert-danger'>Gagal memperbarui data: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin | EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        /* Sidebar Styling - Menyesuaikan Menu Lain */
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1040; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); padding-left: 30px; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }
        
        /* Content Layout */
        #content { width: 100%; transition: all 0.3s; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        
        /* Card & UI Elements */
        .profile-card { border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .profile-cover { background: linear-gradient(135deg, var(--hijau-tua), var(--hijau-muda)); height: 100px; }
        .avatar-wrapper { margin-top: -50px; position: relative; display: inline-block; }
        .avatar-circle { width: 100px; height: 100px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; color: var(--hijau-tua); border: 5px solid #fff; shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .form-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; margin-bottom: 8px; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #e0e0e0; background-color: #f8f9fa; }
        .form-control:focus { background-color: #fff; border-color: var(--hijau-muda); box-shadow: 0 0 0 0.25rem rgba(154, 205, 50, 0.1); }
        
        .btn-update { background: var(--hijau-tua); border: none; border-radius: 10px; padding: 12px; font-weight: 600; transition: 0.3s; }
        .btn-update:hover { background: #146e2d; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26, 143, 58, 0.3); }

        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <i class="fas fa-recycle fs-3 me-2" style="color: #9ACD32;"></i>
            <h4 class="fw-bold m-0">EL HA KA</h4>
        </div>
        <ul class="list-unstyled components">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a></li>
            <li><a href="data_setoran.php"><i class="fas fa-box me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a></li>
            <li class="active"><a href="admin_profil.php"><i class="fas fa-user-circle me-3"></i> Profil</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Profil</h4>
            </div>
            <div class="d-none d-md-block text-muted small">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d M Y'); ?>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    
                    <?= $status_msg; ?>
                    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                        <div class="alert alert-success border-0 shadow-sm rounded-3">
                            <i class="fas fa-check-circle me-2"></i> Profil Anda berhasil diperbarui!
                        </div>
                    <?php endif; ?>

                    <div class="card profile-card">
                        <div class="profile-cover"></div>
                        <div class="card-body text-center p-4">
                            <div class="avatar-wrapper">
                                <div class="avatar-circle shadow-sm">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                            
                            <h4 class="fw-bold mt-2 mb-0"><?= $data['namaAdmin']; ?></h4>
                            <p class="text-muted small mb-4">@<?= $data['usernameAdmin']; ?></p>
                            
                            <hr class="my-4 opacity-50">

                            <form method="POST" class="text-start">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">ID Administrator</label>
                                    <input type="text" class="form-control fw-bold text-muted" value="<?= $data['idAdmin']; ?>" readonly style="cursor: not-allowed;">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Lengkap</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-success"></i></span>
                                        <input type="text" name="namaAdmin" class="form-control border-start-0" value="<?= $data['namaAdmin']; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-at text-success"></i></span>
                                        <input type="text" name="usernameAdmin" class="form-control border-start-0" value="<?= $data['usernameAdmin']; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Password Baru <span class="text-lowercase font-monospace text-muted" style="font-size: 10px;">(Kosongkan jika tidak ganti)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-success"></i></span>
                                        <input type="password" name="passwordAdmin" class="form-control border-start-0" placeholder="••••••••">
                                    </div>
                                </div>

                                <button type="submit" name="update_profil" class="btn btn-success btn-update w-100 text-white">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan Profil
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Toggler
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if(sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
</script>
</body>
</html>
<?php ob_end_flush(); ?>