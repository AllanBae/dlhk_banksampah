<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$nama_admin = $_SESSION['nama'] ?? "Admin";

// --- PROSES UPDATE STATUS (Diterima / Ditolak / Selesai) ---
if (isset($_POST['update_status'])) {
    $id_penarikan = mysqli_real_escape_string($conn, $_POST['id_penarikan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan_tolak'] ?? '');

    // Ambil data penarikan untuk mendapatkan user_id dan jumlah nominal
    $query_data = mysqli_query($conn, "SELECT user_id, jumlah FROM penarikan WHERE id = '$id_penarikan'");
    $data_p = mysqli_fetch_assoc($query_data);
    
    if ($data_p) {
        $user_id = $data_p['user_id'];
        $jumlah_tarik = $data_p['jumlah'];

        // Mulai Transaksi Database
        $conn->begin_transaction();

        try {
            // JIKA STATUS DIUBAH KE SELESAI, POTONG SALDO
            if ($status_baru == 'selesai') {
                // Cek saldo nasabah terakhir
                $query_saldo = mysqli_query($conn, "SELECT saldo FROM data_nasabah WHERE id = '$user_id'");
                $nasabah = mysqli_fetch_assoc($query_saldo);

                if ($nasabah['saldo'] < $jumlah_tarik) {
                    throw new Exception("Saldo nasabah tidak mencukupi (Saldo: Rp " . number_format($nasabah['saldo'], 0, ',', '.') . ")");
                }

                // Perintah potong saldo
                $update_saldo = "UPDATE data_nasabah SET saldo = saldo - $jumlah_tarik WHERE id = '$user_id'";
                if (!mysqli_query($conn, $update_saldo)) {
                    throw new Exception("Gagal memperbarui saldo nasabah.");
                }
            }

            // Update status pada tabel penarikan
            $query_update = "UPDATE penarikan SET 
                             status = '$status_baru', 
                             alasan_tolak = '$alasan' 
                             WHERE id = '$id_penarikan'";
            
            if (!mysqli_query($conn, $query_update)) {
                throw new Exception("Gagal memperbarui status penarikan.");
            }

            // Jika semua oke, simpan permanen
            $conn->commit();
            echo "<script>alert('Berhasil! Status telah diperbarui.'); window.location='data_penarikan.php';</script>";

        } catch (Exception $e) {
            // Jika ada yang gagal, batalkan semua perubahan
            $conn->rollback();
            echo "<script>alert('Gagal: " . addslashes($e->getMessage()) . "'); window.location='data_penarikan.php';</script>";
        }
    }
}

// Statistik
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penarikan WHERE status = 'pending'"))['total'];
$total_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penarikan WHERE status = 'selesai'"))['total'];
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
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; }
        #sidebar ul li a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* Badge Status Styles */
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-diterima { background-color: #cff4fc; color: #055160; }
        .badge-selesai { background-color: #d1e7dd; color: #0f5132; }
        .badge-ditolak { background-color: #f8d7da; color: #842029; }
        
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } }
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
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_profil.php' ? 'active' : ''; ?>">
                <a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a>
            </li>
            <li>
                <a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a>
            </li>
        </ul>
    </nav>

    <div id="content" class="w-100">
        <nav class="navbar top-navbar sticky-top bg-white border-bottom p-3 shadow-sm">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-2"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Kelola Penarikan Saldo</h4>
            </div>
        </nav>

        <div class="p-4">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4 border-start border-warning border-4">
                        <h6 class="text-muted fw-bold small text-uppercase">Menunggu Respon</h6>
                        <h3 class="fw-bold text-warning m-0"><?= $total_pending; ?> Permintaan</h3>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4 border-start border-success border-4">
                        <h6 class="text-muted fw-bold small text-uppercase">Berhasil Dicairkan</h6>
                        <h3 class="fw-bold text-success m-0"><?= $total_selesai; ?> Transaksi</h3>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th>NASABAH</th>
                                <th>NOMINAL</th>
                                <th>RENCANA TGL</th>
                                <th>STATUS</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "SELECT p.*, n.nama_lengkap FROM penarikan p 
                                      JOIN data_nasabah n ON p.user_id = n.id 
                                      ORDER BY p.id DESC";
                            $result = mysqli_query($conn, $query);
                            while($row = mysqli_fetch_assoc($result)) :
                                $status_class = "badge-" . $row['status'];
                            ?>
                            <tr>
                                <td><span class="fw-bold"><?= $row['nama_lengkap']; ?></span></td>
                                <td><span class="text-success fw-bold">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></span></td>
                                <td><i class="far fa-calendar-check me-1"></i> <?= date('d/m/Y', strtotime($row['tanggal_penarikan'])); ?></td>
                                <td><span class="badge rounded-pill <?= $status_class; ?> px-3 py-2"><?= ucfirst($row['status']); ?></span></td>
                                <td class="text-center">
                                    <?php if($row['status'] == 'pending' || $row['status'] == 'diterima') : ?>
                                        <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>">
                                            <i class="fas fa-check-circle me-1"></i> Respon
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>">
                                            <i class="fas fa-search me-1"></i> Detail
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Update Status Penarikan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="" method="POST">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="id_penarikan" value="<?= $row['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <p class="text-muted mb-1">Nama Nasabah:</p>
                                                    <h6><?= $row['nama_lengkap']; ?></h6>
                                                </div>
                                                <div class="mb-3">
                                                    <p class="text-muted mb-1">Jumlah Penarikan:</p>
                                                    <h5 class="text-success fw-bold">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></h5>
                                                </div>

                                                <?php if($row['status'] != 'selesai' && $row['status'] != 'ditolak') : ?>
                                                    <hr>
                                                    <label class="form-label fw-bold">Pilih Status Baru:</label>
                                                    <select name="status" class="form-select mb-3" onchange="handleStatusChange(this, <?= $row['id']; ?>)" required>
                                                        <option value="" disabled selected>-- Pilih Tindakan --</option>
                                                        <option value="diterima" <?= $row['status'] == 'diterima' ? 'selected' : ''; ?>>Terima Permintaan (Booking)</option>
                                                        <option value="selesai">Selesai (Cairkan & Potong Saldo)</option>
                                                        <option value="ditolak">Tolak Permintaan</option>
                                                    </select>

                                                    <div id="areaAlasan<?= $row['id']; ?>" style="display:none;">
                                                        <label class="form-label fw-bold text-danger">Alasan Penolakan:</label>
                                                        <textarea name="alasan_tolak" class="form-control" placeholder="Tulis alasan mengapa ditolak..."></textarea>
                                                    </div>

                                                    <div id="areaInfoSelesai<?= $row['id']; ?>" class="alert alert-warning mt-2 d-flex align-items-center" style="display:none;">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <small>Status <strong>Selesai</strong> akan langsung memotong saldo nasabah secara permanen.</small>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-light border">
                                                        <strong>Detail Transaksi:</strong><br>
                                                        Status: <span class="badge <?= $status_class ?>"><?= ucfirst($row['status']) ?></span><br>
                                                        <?php if($row['alasan_tolak']): ?>
                                                            Keterangan: <?= $row['alasan_tolak'] ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                                                <?php if($row['status'] != 'selesai' && $row['status'] != 'ditolak') : ?>
                                                    <button type="submit" name="update_status" class="btn btn-success rounded-pill px-4" onclick="return confirm('Konfirmasi perubahan status?')">Update Sekarang</button>
                                                <?php endif; ?>
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
    });

    // Menangani tampilan input tambahan berdasarkan status yang dipilih
    function handleStatusChange(select, id) {
        const areaAlasan = document.getElementById('areaAlasan' + id);
        const areaInfo = document.getElementById('areaInfoSelesai' + id);
        const textarea = areaAlasan.querySelector('textarea');

        // Reset
        areaAlasan.style.display = 'none';
        areaInfo.style.display = 'none';
        textarea.required = false;

        if (select.value === 'ditolak') {
            areaAlasan.style.display = 'block';
            textarea.required = true;
        } else if (select.value === 'selesai') {
            areaInfo.style.display = 'block';
        }
    }
</script>
</body>
</html>