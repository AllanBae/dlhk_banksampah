<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id_nasabah = mysqli_real_escape_string($conn, $_GET['hapus']);

    $query_get_user = mysqli_query($conn, "SELECT username, foto FROM data_nasabah WHERE id = '$id_nasabah'");
    $data = mysqli_fetch_assoc($query_get_user);

    if ($data) {
        $username = $data['username'];
        $foto_lama = $data['foto'];

        $conn->begin_transaction();
        try {
            if ($foto_lama && $foto_lama != 'default.png') {
                $path_foto = "../assets/uploads/" . $foto_lama;
                if (file_exists($path_foto)) { unlink($path_foto); }
            }

            mysqli_query($conn, "DELETE FROM data_nasabah WHERE id = '$id_nasabah'");
            mysqli_query($conn, "DELETE FROM users WHERE username = '$username' AND role = 'nasabah'");

            $conn->commit();
            echo "<script>window.location.href='data_nasabah.php?status=hapus_berhasil';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal menghapus: " . $e->getMessage() . "');</script>";
        }
    }
}

if (isset($_POST['edit_nasabah'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $old_username = mysqli_real_escape_string($conn, $_POST['old_username']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $dinas_instansi = mysqli_real_escape_string($conn, $_POST['dinas_instansi']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $saldo_baru = mysqli_real_escape_string($conn, $_POST['saldo']);
    
    // Menangkap input password baru
    $password_baru = $_POST['password']; 

    $conn->begin_transaction();
    try {
        if (!empty($password_baru)) {
            // Jika password diisi, enkripsi password baru dan update semuanya
            $passwordHash = password_hash($password_baru, PASSWORD_DEFAULT);
            
            mysqli_query($conn, "UPDATE data_nasabah SET 
                nama_lengkap = '$nama', 
                username = '$username_baru', 
                dinas_instansi = '$dinas_instansi', 
                no_telp = '$no_telp', 
                alamat = '$alamat',
                saldo = '$saldo_baru',
                password = '$passwordHash'
                WHERE id = '$id'");

            mysqli_query($conn, "UPDATE users SET 
                nama = '$nama', 
                username = '$username_baru',
                password = '$passwordHash'
                WHERE username = '$old_username' AND role = 'nasabah'");
        } else {
            // Jika password dikosongkan, update data kecuali password
            mysqli_query($conn, "UPDATE data_nasabah SET 
                nama_lengkap = '$nama', 
                username = '$username_baru', 
                dinas_instansi = '$dinas_instansi', 
                no_telp = '$no_telp', 
                alamat = '$alamat',
                saldo = '$saldo_baru' 
                WHERE id = '$id'");

            mysqli_query($conn, "UPDATE users SET 
                nama = '$nama', 
                username = '$username_baru' 
                WHERE username = '$old_username' AND role = 'nasabah'");
        }

        $conn->commit();
        
        echo "<script>window.location.href='data_nasabah.php?status=update_berhasil';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal update: " . $e->getMessage() . "'); window.location.href='data_nasabah.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Nasabah | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { 
            --hijau-tua: #1A8F3A;     
            --hijau-muda: #9ACD32;    
            --hijau-bg: #f4f9f5; 
        }

        body {
            background-color: var(--hijau-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* --- Sidebar Styling --- */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            min-height: 100vh;
            background: var(--hijau-tua);
            color: #fff;
            transition: all 0.3s;
            z-index: 1040;
        }

        #sidebar .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.1);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 15px 25px;
            display: block;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: 0.3s;
            font-weight: 500;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
            padding-left: 30px;
        }

        #sidebar ul li.active > a {
            background: var(--hijau-muda);
            color: #fff;
            border-radius: 0 30px 30px 0;
            margin-right: 20px;
            box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4);
        }

        /* --- Main Content & Top Navbar --- */
        #content {
            width: 100%;
            transition: all 0.3s;
        }

        .top-navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e9ecef;
            padding: 15px 25px;
        }

        .main-inner {
            padding: 30px;
        }

        /* --- Card & Table --- */
        .glass-card { 
            background: #fff; 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(154, 205, 50, 0.05);
        }

        .modal-profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--hijau-muda); }
        .info-text-foto { font-size: 0.85rem; color: #7f8c8d; margin-top: 8px; margin-bottom: 15px; }

        /* --- Mobile Responsiveness --- */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -260px;
                position: fixed;
                height: 100vh;
            }
            #sidebar.active {
                margin-left: 0;
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            .main-inner { padding: 15px; }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: 1030;
                top: 0;
                left: 0;
            }
            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <i class="fas fa-leaf fs-3 me-2"></i>
            <h4 class="fw-bold m-0">EL HA KA</h4>
        </div>
            <ul class="list-unstyled components">
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'data_nasabah.php' ? 'active' : ''; ?>">
                    <a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'data_setoran.php' ? 'active' : ''; ?>">
                    <a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_kelolasampah.php' ? 'active' : ''; ?>">
                    <a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_kelolaberita.php' ? 'active' : ''; ?>">
                    <a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
                    <a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a>
                </li>
                <li class="mt-4">
                    <a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a>
                </li>
            </ul>
    </nav>

    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light shadow-sm d-md-none me-3">
                    <i class="fas fa-bars" style="color: var(--hijau-tua);"></i>
                </button>
                <h4 class="fw-bold m-0 d-none d-md-block" style="color: var(--hijau-tua);">Data Nasabah</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="mb-4 d-flex justify-content-between align-items-center">

                <?php if(isset($_GET['status'])): ?>
                    <div id="status-alert" class="alert alert-success py-2 px-3 m-0 shadow-sm border-0">
                        <?php 
                            if($_GET['status'] == 'update_berhasil') echo "<i class='fas fa-check-circle me-1'></i> Edit Data Berhasil!";
                            else if($_GET['status'] == 'hapus_berhasil') echo "<i class='fas fa-check-circle me-1'></i> Data Berhasil Dihapus!";
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card glass-card">
                <div class="table-responsive p-3">
                    <table class="table align-middle table-hover">
                        <thead style="border-bottom: 2px solid var(--hijau-tua);">
                            <tr>
                                <th class="text-muted pb-3">Nama Nasabah</th>
                                <th class="text-muted pb-3">Instansi & Kontak</th>
                                <th class="text-muted pb-3">Saldo</th>
                                <th class="text-center text-muted pb-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM data_nasabah WHERE role = 'nasabah' ORDER BY id DESC");
                            while ($row = mysqli_fetch_assoc($res)) :
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php 
                                            $fotoPath = "../assets/uploads/" . ($row['foto'] ? $row['foto'] : 'default.png');
                                        ?>
                                        <img src="<?= $fotoPath; ?>" class="rounded-circle me-3 shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid var(--hijau-muda);">
                                        <div>
                                            <span class="d-block fw-bold text-dark"><?= $row['nama_lengkap']; ?></span>
                                            <small class="text-muted">@<?= $row['username']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        <i class="fas fa-building me-1 text-muted"></i> 
                                        <?= !empty($row['dinas_instansi']) ? $row['dinas_instansi'] : 'Nasabah Umum'; ?>
                                    </div>
                                    <div class="small text-muted mt-1"><i class="fas fa-phone me-1"></i> <?= $row['no_telp']; ?></div>
                                </td>
                                <td><span class="badge" style="background-color: rgba(154, 205, 50, 0.2); color: var(--hijau-tua); font-size: 0.9rem;">Rp <?= number_format($row['saldo'], 0, ',', '.'); ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm me-1 shadow-sm" style="background: var(--hijau-bg); color: var(--hijau-tua); border: 1px solid var(--hijau-tua);" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?hapus=<?= $row['id']; ?>" class="btn btn-outline-danger btn-sm shadow-sm" onclick="return confirm('Hapus nasabah ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content modal-edit-nasabah border-0 shadow">
                                        <form method="POST">
                                            <div class="modal-header" style="background-color: var(--hijau-tua); color: white;">
                                                <h5 class="modal-title fw-bold">Edit Profil Nasabah</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="old_username" value="<?= $row['username']; ?>">
                                                
                                                <div class="text-center mb-1 mt-3">
                                                    <img src="<?= $fotoPath; ?>" class="modal-profile-img shadow-sm">
                                                </div>

                                                <div class="mb-4 p-3 rounded mx-2" style="background-color: rgba(154, 205, 50, 0.1); border: 1px dashed var(--hijau-tua);">
                                                    <label class="form-label fw-bold mb-1" style="color: var(--hijau-tua);">Saldo Nasabah (Rp)</label>
                                                    <input type="number" name="saldo" class="form-control form-control-lg fw-bold text-success" value="<?= $row['saldo']; ?>" required>
                                                </div>

                                                <div class="mb-3 px-2">
                                                    <label class="form-label small fw-semibold text-muted">Nama Lengkap</label>
                                                    <input type="text" name="nama_lengkap" class="form-control" value="<?= $row['nama_lengkap']; ?>" required>
                                                </div>
                                                <div class="mb-3 px-2">
                                                    <label class="form-label small fw-semibold text-muted">Username</label>
                                                    <input type="text" name="username" class="form-control" value="<?= $row['username']; ?>" required>
                                                </div>
                                                <div class="mb-3 px-2">
                                                    <label class="form-label small fw-semibold text-muted">Dinas/Instansi</label>
                                                    <input type="text" name="dinas_instansi" class="form-control" value="<?= $row['dinas_instansi']; ?>">
                                                </div>
                                                <div class="mb-3 px-2">
                                                    <label class="form-label small fw-semibold text-muted">No. HP / WhatsApp</label>
                                                    <input type="text" name="no_telp" class="form-control" value="<?= $row['no_telp']; ?>" required>
                                                </div>
                                                <div class="mb-3 px-2">
                                                    <label class="form-label small fw-semibold text-muted">Alamat Lengkap</label>
                                                    <textarea name="alamat" class="form-control form-control-sm" rows="3"><?= $row['alamat']; ?></textarea>
                                                </div>
                                                <div class="mb-2 px-2">
                                                    <label class="form-label small fw-semibold text-muted">Ubah Password (Opsional)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password baru">
                                                    <small class="text-danger" style="font-size: 0.8rem;">*Kosongkan jika tidak ingin mengubah password nasabah.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light border-0">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_nasabah" class="btn btn-sm px-4 fw-bold shadow-sm" style="background: var(--hijau-tua); color: white;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script untuk Toggle Sidebar di Mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    if(sidebarCollapse) sidebarCollapse.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);

    // Animasi hilang untuk alert
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('status-alert');
        if (alert) {
            setTimeout(function() {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(function() {
                    alert.remove();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 500);
            }, 2000);
        }
    });
</script>
</body>
</html>