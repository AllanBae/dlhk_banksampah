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
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $saldo_baru = mysqli_real_escape_string($conn, $_POST['saldo']);

    $conn->begin_transaction();
    try {
        mysqli_query($conn, "UPDATE data_nasabah SET 
            nama_lengkap = '$nama', 
            username = '$username_baru', 
            email = '$email', 
            no_telp = '$no_telp', 
            alamat = '$alamat',
            saldo = '$saldo_baru' 
            WHERE id = '$id'");

        mysqli_query($conn, "UPDATE users SET 
            nama = '$nama', 
            username = '$username_baru' 
            WHERE username = '$old_username' AND role = 'nasabah'");

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
    <title>Data Nasabah | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        #sidebar { min-width: 250px; background: #2c3e50; color: #fff; min-height: 100vh; position: sticky; top: 0; }
        #sidebar .sidebar-header { padding: 25px; background: #1a252f; text-align: center; }
        #sidebar ul li a { padding: 15px 20px; display: block; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; }
        #sidebar ul li.active > a { background: #3498db; color: #fff; }
        .main-content { width: 100%; padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .modal-profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid #3498db; }
        .info-text-foto { font-size: 0.85rem; color: #7f8c8d; margin-top: 8px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header"><h4 class="fw-bold m-0">Admin Panel</h4></div>
        <ul class="list-unstyled">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
            <li class="active"><a href="data_nasabah.php"><i class="fas fa-users me-2"></i> Data Nasabah</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-2"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-2"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-2"></i> Laporan</a></li>
            <li><a href="../auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h2 class="fw-bold">Data Nasabah</h2>
            <?php if(isset($_GET['status'])): ?>
                <div id="status-alert" class="alert alert-success py-2 px-3 m-0">
                    <?php 
                        if($_GET['status'] == 'update_berhasil') echo "Edit Data Berhasil!";
                        else if($_GET['status'] == 'hapus_berhasil') echo "Data Berhasil Dihapus!";
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card glass-card">
            <div class="table-responsive p-3">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>Nama Nasabah</th>
                            <th>Email & Kontak</th>
                            <th>Saldo</th>
                            <th class="text-center">Aksi</th>
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
                                        if (!file_exists($fotoPath) || !$row['foto']) {
                                        }
                                    ?>
                                    <img src="<?= $fotoPath; ?>" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #3498db;">
                                    <div>
                                        <span class="d-block fw-bold"><?= $row['nama_lengkap']; ?></span>
                                        <small class="text-muted">@<?= $row['username']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= $row['email']; ?></div>
                                <div class="small text-muted"><?= $row['no_telp']; ?></div>
                            </td>
                            <td><span class="badge bg-light text-success">Rp <?= number_format($row['saldo'], 0, ',', '.'); ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?hapus=<?= $row['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus nasabah ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content modal-edit-nasabah">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Profil</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <input type="hidden" name="old_username" value="<?= $row['username']; ?>">
                                            
                                            <div class="text-center mb-1">
                                                <img src="<?= $fotoPath; ?>" class="modal-profile-img shadow-sm">
                                                <p class="info-text-foto">Foto hanya dapat diubah oleh Nasabah</p>
                                            </div>

                                            <div class="mb-4 p-3 bg-light rounded border border-success mx-2">
                                                <label class="form-label fw-bold text-success mb-1">Saldo Nasabah (Rp)</label>
                                                <input type="number" name="saldo" class="form-control form-control-lg fw-bold" value="<?= $row['saldo']; ?>" required>
                                            </div>

                                            <div class="mb-3 px-2">
                                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                                <input type="text" name="nama_lengkap" class="form-control" value="<?= $row['nama_lengkap']; ?>" required>
                                            </div>
                                            <div class="mb-3 px-2">
                                                <label class="form-label small fw-semibold">Username</label>
                                                <input type="text" name="username" class="form-control" value="<?= $row['username']; ?>" required>
                                            </div>
                                            <div class="mb-3 px-2">
                                                <label class="form-label small fw-semibold">Email Aktif</label>
                                                <input type="email" name="email" class="form-control" value="<?= $row['email']; ?>" required>
                                            </div>
                                            <div class="mb-3 px-2">
                                                <label class="form-label small fw-semibold">No. HP / WhatsApp</label>
                                                <input type="text" name="no_telp" class="form-control" value="<?= $row['no_telp']; ?>" required>
                                            </div>
                                            <div class="mb-2 px-2">
                                                <label class="form-label small fw-semibold">Alamat Lengkap</label>
                                                <textarea name="alamat" class="form-control form-control-sm" rows="3"><?= $row['alamat']; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit_nasabah" class="btn btn-sm btn-primary px-3">Simpan Perubahan</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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