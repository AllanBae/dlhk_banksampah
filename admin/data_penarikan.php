<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$nama_admin = $_SESSION['nama'] ?? "Admin";

// Proses Update Status (Setuju/Tolak)
if (isset($_POST['update_status'])) {
    $id_penarikan = $_POST['id_penarikan'];
    $status_baru = $_POST['status'];
    
    // Update status di tabel penarikan
    $query_update = "UPDATE penarikan SET status = '$status_baru' WHERE id = '$id_penarikan'";
    
    if (mysqli_query($conn, $query_update)) {
        echo "<script>alert('Status penarikan berhasil diperbarui!'); window.location='data_penarikan.php';</script>";
    }
}

// Ambil data statistik sederhana
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penarikan WHERE status = 'pending'"))['total'];
$total_berhasil = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penarikan WHERE status = 'berhasil'"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penarikan | Admin EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        /* Sidebar - Konsisten dengan tema */
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; }
        #sidebar ul li a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }
        
        #content { width: 100%; min-height: 100vh; }
        .top-navbar { background: #fff; border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-berhasil { background-color: #d4edda; color: #155724; }
        .badge-ditolak { background-color: #f8d7da; color: #721c24; }

        .sidebar-overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1045; top: 0; left: 0; }
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; }
            #sidebar.active { margin-left: 0; }
            .sidebar-overlay.active { display: block; }
        }
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
                <h4 class="fw-bold m-0 text-success">Kelola Penarikan Saldo</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4 border-start border-warning border-4">
                        <h6 class="text-muted fw-bold">Menunggu Persetujuan</h6>
                        <h3 class="fw-bold text-warning m-0"><?= $total_pending; ?> Pengajuan</h3>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4 border-start border-success border-4">
                        <h6 class="text-muted fw-bold">Total Penarikan Berhasil</h6>
                        <h3 class="fw-bold text-success m-0"><?= $total_berhasil; ?> Transaksi</h3>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-muted">
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Nasabah</th>
                                <th>Jumlah Penarikan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $query = "SELECT p.*, n.nama_lengkap FROM penarikan p 
                                      JOIN data_nasabah n ON p.user_id = n.id 
                                      ORDER BY p.created_at DESC";
                            $result = mysqli_query($conn, $query);
                            while($row = mysqli_fetch_assoc($result)) :
                                $status_class = "badge-" . $row['status'];
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="fw-bold"><?= $row['nama_lengkap']; ?></td>
                                <td class="text-success fw-bold">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                <td><span class="badge rounded-pill <?= $status_class; ?> px-3 py-2"><?= ucfirst($row['status']); ?></span></td>
                                <td class="text-center">
                                    <?php if($row['status'] == 'pending') : ?>
                                        <button class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $row['id']; ?>">
                                            <i class="fas fa-edit me-1"></i> Proses
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalStatus<?= $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                                        <div class="modal-header bg-success text-white" style="border-radius: 15px 15px 0 0;">
                                            <h5 class="modal-title">Konfirmasi Penarikan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="" method="POST">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="id_penarikan" value="<?= $row['id']; ?>">
                                                <p>Nasabah: <strong><?= $row['nama_lengkap']; ?></strong></p>
                                                <p>Jumlah: <strong class="text-success">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></strong></p>
                                                <hr>
                                                <label class="form-label fw-bold">Tentukan Keputusan:</label>
                                                <select name="status" class="form-select mb-3" required>
                                                    <option value="berhasil">Setujui (Berhasil)</option>
                                                    <option value="ditolak">Tolak (Gagalkan)</option>
                                                </select>
                                                <p class="text-muted small">Catatan: Pastikan Anda telah menyerahkan uang tunai atau mentransfer dana sebelum menyetujui status berhasil.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update_status" class="btn btn-success rounded-pill px-4">Simpan Perubahan</button>
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
    // Logic Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const overlay = document.getElementById('overlay');

    sidebarCollapse.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
</script>
</body>
</html>