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

// --- LOGIKA EDIT ---
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
        #content { width: 100%; transition: all 0.3s; overflow-x: hidden; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .modal-profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--hijau-muda); }
        .mini-stat-box { background: #f8faf9; border-left: 4px solid var(--hijau-tua); border-radius: 8px; padding: 12px; }
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } .sidebar-overlay.active { display: block; position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1030; } }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="d-flex"> 
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <i class="fas fa-recycle fs-3 me-2" style="color: #9ACD32;"></i>
            <h4 class="fw-bold m-0">EL HA KA</h4>
        </div>
        <ul class="list-unstyled components">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a></li>
            <li class="active"><a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a></li>
            <li><a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a></li>
            <li><a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a></li>
            <li><a href="data_penjualan.php"><i class="fas fa-shopping-cart me-3"></i> Data Penjualan</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
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
                        <input type="text" name="cari" class="form-control" placeholder="Cari..." value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
                        <button class="btn btn-success" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>

            <div class="card glass-card p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Nasabah</th>
                                <th style="width: 25%;">Instansi</th>
                                <th style="width: 20%;">Saldo</th>
                                <th class="text-center" style="width: 20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
                            $query = "SELECT * FROM data_nasabah WHERE role = 'nasabah' AND (nama_lengkap LIKE '%$cari%' OR username LIKE '%$cari%') ORDER BY id DESC";
                            $res = mysqli_query($conn, $query);
                            
                            // Variabel penampung HTML Modal agar tidak merusak susunan tabel <tbody>
                            $modal_storage = "";

                            if(mysqli_num_rows($res) > 0) :
                                while ($row = mysqli_fetch_assoc($res)) :
                                    $id_nasabah = $row['id'];
                                    $foto = "../assets/uploads/" . ($row['foto'] ? $row['foto'] : 'default.png');

                                    // Hitung statistik detail secara berkala
                                    $q_stat = mysqli_query($conn, "SELECT SUM(berat) as total_b, SUM(total_harga) as total_u FROM transaksi WHERE id_nasabah = '$id_nasabah'");
                                    $stat = mysqli_fetch_assoc($q_stat);
                                    $total_berat_terkumpul = $stat['total_b'] ?? 0;
                                    $total_akumulasi_uang = $stat['total_u'] ?? 0;
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
                                    <div class="btn-group gap-1">
                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#detailModal<?= $row['id'] ?>" title="Lihat Detail"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus nasabah ini?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                    // Pindahkan pembuatan HTML modal ke dalam string buffer buffer penyimpanan
                                    ob_start();
                                    ?>
                                    <div class="modal fade" id="detailModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg">
                                                <div class="modal-header text-white" style="background: var(--hijau-tua);">
                                                    <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Detail Informasi Nasabah</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="text-center mb-3">
                                                        <img src="<?= $foto ?>" class="modal-profile-img shadow-sm mb-2">
                                                        <h5 class="fw-bold m-0 text-dark"><?= $row['nama_lengkap']; ?></h5>
                                                        <span class="badge bg-secondary-subtle text-secondary">ID Nasabah: #<?= $row['id']; ?></span>
                                                    </div>
                                                    
                                                    <div class="row g-2 mb-4">
                                                        <div class="col-6">
                                                            <div class="mini-stat-box">
                                                                <small class="text-muted d-block fw-semibold" style="font-size:0.75rem;">TOTAL BERAT SAMPAH</small>
                                                                <span class="fw-bold text-success fs-5"><?= number_format($total_berat_terkumpul, 2, ',', '.'); ?> Kg</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="mini-stat-box" style="border-left-color: #f39c12;">
                                                                <small class="text-muted d-block fw-semibold" style="font-size:0.75rem;">TOTAL HASIL SETORAN</small>
                                                                <span class="fw-bold text-warning fs-5">Rp <?= number_format($total_akumulasi_uang, 0, ',', '.'); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <h6 class="fw-bold border-bottom pb-2 mb-2 text-success"><i class="fas fa-id-card me-2"></i>Biodata Profil</h6>
                                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.9rem;">
                                                        <tr><td class="text-muted" style="width:35%;">Username</td><td class="fw-semibold">: @<?= $row['username']; ?></td></tr>
                                                        <tr><td class="text-muted">Instansi / Dinas</td><td>: <?= $row['dinas_instansi'] ?: '-'; ?></td></tr>
                                                        <tr><td class="text-muted">No. Telepon</td><td>: <?= $row['no_telp']; ?></td></tr>
                                                        <tr><td class="text-muted">Alamat Rumah</td><td>: <?= $row['alamat'] ?: '-'; ?></td></tr>
                                                        <tr>
                                                            <td class="text-muted align-middle">Sisa Saldo Saat Ini</td>
                                                            <td class="align-middle">: <span class="badge bg-success text-white px-2 py-1 fs-6 fw-bold">Rp <?= number_format($row['saldo'], 0, ',', '.'); ?></span></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
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
                                    <?php
                                    $modal_storage .= ob_get_clean();
                                endwhile; 
                            else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Data nasabah tidak ditemukan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $modal_storage; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const overlay = document.getElementById('overlay');

    if(sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // Alert Auto Hide
    setTimeout(() => {
        const alert = document.getElementById('status-alert');
        if(alert) {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }
    }, 2500);
</script>
</body>
</html>