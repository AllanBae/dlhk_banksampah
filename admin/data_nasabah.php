<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// --- LOGIKA HAPUS ---
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
            echo "<script>alert('Gagal menghapus: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// --- LOGIKA EDIT (SALDO DIKUNCI / READ-ONLY) ---
if (isset($_POST['edit_nasabah'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $old_username = mysqli_real_escape_string($conn, $_POST['old_username']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $dinas_instansi = mysqli_real_escape_string($conn, $_POST['dinas_instansi']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $password_baru = $_POST['password']; 

    $conn->begin_transaction();
    try {
        // Query Update TANPA mengubah kolom saldo
        $sql_nasabah = "UPDATE data_nasabah SET 
                nama_lengkap = '$nama', 
                username = '$username_baru', 
                dinas_instansi = '$dinas_instansi', 
                no_telp = '$no_telp', 
                alamat = '$alamat'";

        if (!empty($password_baru)) {
            $passwordHash = password_hash($password_baru, PASSWORD_DEFAULT);
            $sql_nasabah .= ", password = '$passwordHash'";
        }
        $sql_nasabah .= " WHERE id = '$id'";
        
        if (!mysqli_query($conn, $sql_nasabah)) {
            throw new Exception("Gagal update data_nasabah: " . mysqli_error($conn));
        }

        // Query untuk tabel users (agar login tetap sinkron)
        $sql_user = "UPDATE users SET nama = '$nama', username = '$username_baru'";
        if (!empty($password_baru)) {
            $sql_user .= ", password = '$passwordHash'";
        }
        $sql_user .= " WHERE username = '$old_username' AND role = 'nasabah'";
        
        if (!mysqli_query($conn, $sql_user)) {
            throw new Exception("Gagal update users: " . mysqli_error($conn));
        }

        $conn->commit();
        echo "<script>window.location.href='data_nasabah.php?status=update_berhasil';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal: " . addslashes($e->getMessage()) . "'); window.location.href='data_nasabah.php';</script>";
        exit;
    }
}

// Fungsi Highlight Pencarian
function highlight_keyword($text, $keyword) {
    if ($keyword != '') {
        return str_ireplace($keyword, "<span style='background-color: yellow; padding: 0 2px;'>$keyword</span>", $text);
    }
    return $text;
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
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1040; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); padding-left: 30px; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }
        #content { width: 100%; transition: all 0.3s; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .modal-profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--hijau-muda); }
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } .sidebar-overlay.active { display: block; position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1030; } }
    </style>
</head>
<body>

<div class="d-flex"> 
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
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'data_penarikan.php' ? 'active' : ''; ?>">
                <a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a>
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
            <li>
                <a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a>
            </li>
        </ul>
    </nav>


    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Manajemen Nasabah</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <?php if(isset($_GET['status'])): ?>
                        <div id="status-alert" class="alert alert-success py-2 px-3 m-0 border-0 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> Berhasil memperbarui data!
                        </div>
                    <?php endif; ?>
                </div>
                <form method="GET" class="d-flex gap-2 w-50 justify-content-end">
                    <div class="input-group shadow-sm" style="max-width: 300px;">
                        <input type="text" name="cari" class="form-control" placeholder="Cari..." value="<?= @$_GET['cari'] ?>">
                        <button class="btn btn-success" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>

            <div class="card glass-card p-3">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nasabah</th>
                                <th>Instansi</th>
                                <th>Saldo</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
                            $query = "SELECT * FROM data_nasabah WHERE role = 'nasabah' AND (nama_lengkap LIKE '%$cari%' OR username LIKE '%$cari%') ORDER BY id DESC";
                            $res = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($res)) :
                                $foto = "../assets/uploads/" . ($row['foto'] ? $row['foto'] : 'default.png');
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $foto ?>" class="rounded-circle me-3" style="width: 40px; height:40px; object-fit:cover;">
                                        <div>
                                            <div class="fw-bold"><?= highlight_keyword($row['nama_lengkap'], $cari) ?></div>
                                            <small class="text-muted">@<?= $row['username'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $row['dinas_instansi'] ?: '-' ?></td>
                                <td><span class="badge bg-success-subtle text-success px-3 py-2">Rp <?= number_format($row['saldo'], 0, ',', '.') ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="fas fa-edit"></i></button>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">Edit Data Nasabah</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="old_username" value="<?= $row['username'] ?>">
                                                
                                                <div class="text-center mb-3">
                                                    <img src="<?= $foto ?>" class="modal-profile-img">
                                                </div>

                                                <div class="mb-3 p-3 rounded border" style="background-color: #f8f9fa;">
                                                    <label class="form-label fw-bold text-muted mb-1">Saldo Nasabah Saat Ini</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white border-end-0 text-success fw-bold">Rp</span>
                                                        <input type="text" name="saldo" class="form-control bg-white border-start-0 fw-bold text-success" 
                                                               value="<?= number_format($row['saldo'], 0, ',', '.'); ?>" readonly>
                                                    </div>
                                                    <small class="text-danger" style="font-size: 0.8rem;">*Saldo hanya dapat berubah melalui transaksi setoran/penarikan.</small>
                                                </div>

                                                <div class="row g-2">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="small fw-bold">Nama Lengkap</label>
                                                        <input type="text" name="nama_lengkap" class="form-control" value="<?= $row['nama_lengkap'] ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="small fw-bold">Username</label>
                                                        <input type="text" name="username" class="form-control" value="<?= $row['username'] ?>" required>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="small fw-bold">Dinas / Instansi</label>
                                                    <input type="text" name="dinas_instansi" class="form-control" value="<?= $row['dinas_instansi'] ?>">
                                                </div>

                                                <div class="mb-2">
                                                    <label class="small fw-bold">No. Telp</label>
                                                    <input type="text" name="no_telp" class="form-control" value="<?= $row['no_telp'] ?>" required>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="small fw-bold">Alamat</label>
                                                    <textarea name="alamat" class="form-control" rows="2"><?= $row['alamat'] ?></textarea>
                                                </div>

                                                <div class="mb-0">
                                                    <label class="form-label small fw-semibold text-muted">Ubah Password (Opsional)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password baru">
                                                    <small class="text-danger" style="font-size: 0.8rem;">*Kosongkan jika tidak ingin mengubah password nasabah.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_nasabah" class="btn btn-success px-4">Simpan Perubahan</button>
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
    // Toggle Sidebar
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    });
    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('active');
        document.getElementById('sidebarOverlay').classList.remove('active');
    });

    // Alert Auto Hide
    setTimeout(() => {
        const alert = document.getElementById('status-alert');
        if(alert) alert.style.display = 'none';
    }, 3000);
</script>
</body>
</html>