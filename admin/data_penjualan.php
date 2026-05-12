<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$admin_id = $_SESSION['id'] ?? 0; 
$q_admin = mysqli_query($conn, "SELECT * FROM users WHERE id='$admin_id'");

if ($q_admin && mysqli_num_rows($q_admin) > 0) {
    $d_admin = mysqli_fetch_assoc($q_admin);
    // Kita cek mana yang ada: 'nama' atau 'username'
    $nama_admin = $d_admin['nama'] ?? ($d_admin['username'] ?? 'Admin');
} else {
    $nama_admin = $_SESSION['username'] ?? 'Admin';
}
// ---------------------------------------------------------
// PROSES HAPUS DATA (DELETE)
// ---------------------------------------------------------
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $ambil_data = mysqli_query($conn, "SELECT total_harga FROM data_penjualan WHERE id='$id_hapus'");
    if ($data_terhapus = mysqli_fetch_assoc($ambil_data)) {
        $harga_min = $data_terhapus['total_harga'];
        mysqli_query($conn, "UPDATE kas_admin SET total_saldo = total_saldo - $harga_min");
        mysqli_query($conn, "DELETE FROM data_penjualan WHERE id='$id_hapus'");
    }
    header("Location: data_penjualan.php?status=hapus");
    exit;
}

// ---------------------------------------------------------
// PROSES SIMPAN DATA (CREATE)
// ---------------------------------------------------------
if (isset($_POST['simpan_penjualan'])) {
    $tanggal = $_POST['tanggal_penjualan'];
    $sampah_id = $_POST['sampah_id'];
    $berat = $_POST['berat']; 

    $ambil = mysqli_query($conn, "SELECT * FROM harga_sampah WHERE id='$sampah_id'");
    $data = mysqli_fetch_assoc($ambil);

    $harga_pengepul = $data['harga_pengepul'];
    $satuan = $data['satuan'];
    $total_harga = $berat * $harga_pengepul;

    mysqli_query($conn, "INSERT INTO data_penjualan (tanggal_penjualan, sampah_id, jumlah, satuan, harga_persatuan, total_harga) 
                        VALUES ('$tanggal', '$sampah_id', '$berat', '$satuan', '$harga_pengepul', '$total_harga')");
    
    mysqli_query($conn, "UPDATE kas_admin SET total_saldo = total_saldo + $total_harga");

    header("Location: data_penjualan.php?status=sukses");
    exit;
}

// AMBIL DATA KAS
$kas_admin = mysqli_query($conn, "SELECT * FROM kas_admin LIMIT 1");
$saldo_kas = (mysqli_num_rows($kas_admin) > 0) ? mysqli_fetch_assoc($kas_admin)['total_saldo'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjualan - EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        /* Sidebar Styling */
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); padding-left: 30px; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }
        
        #content { width: 100%; transition: all 0.3s; min-height: 100vh; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; }
            #sidebar.active { margin-left: 0; }
            .main-inner { padding: 15px; }
        }

        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .table thead th { background-color: var(--hijau-bg); color: var(--hijau-tua); border: none; }
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
            <li><a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a></li>
            <li><a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a></li>
            <li class="active"><a href="data_penjualan.php"><i class="fas fa-shopping-cart me-3"></i> Data Penjualan</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Manajemen Penjualan</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="bg-light px-3 py-2 rounded-pill shadow-sm border border-success border-opacity-25">
                    <span class="text-muted small me-1">Login Sebagai:</span>
                    <span class="fw-bold text-success"><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nama_admin); ?></span>
                </div>
            </div>
        </nav>

        <div class="main-inner">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="glass-card p-4 border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-1">Total Saldo Kas Admin</h6>
                                <h2 class="fw-bold m-0 text-success">Rp <?= number_format($saldo_kas, 0, ',', '.'); ?></h2>
                            </div>
                            <div class="icon-box bg-success bg-opacity-10 text-success">
                                <i class="fas fa-wallet fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="glass-card p-4">
                        <h5 class="fw-bold mb-4 text-success"><i class="fas fa-plus-circle me-2"></i>Tambah Data</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_penjualan" class="form-control border-0 bg-light" required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Jenis Sampah</label>
                                <select name="sampah_id" id="sampahSelect" class="form-select border-0 bg-light" required>
                                    <option value="">-- Pilih Sampah --</option>
                                    <?php
                                    $sampah = mysqli_query($conn,"SELECT * FROM harga_sampah ORDER BY jenis_sampah ASC");
                                    while($s = mysqli_fetch_assoc($sampah)): ?>
                                        <option value="<?= $s['id']; ?>" data-harga="<?= $s['harga_pengepul']; ?>">
                                            <?= $s['jenis_sampah']; ?> (<?= $s['satuan']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Harga Pengepul</label>
                                <input type="text" id="harga" class="form-control border-0 bg-light fw-bold" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Jumlah Berat/Liter</label>
                                <input type="number" step="0.01" name="berat" id="berat" class="form-control border-0 bg-light" placeholder="0.00" required>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-muted">Estimasi Total Terima</label>
                                <input type="text" id="total" class="form-control border-0 bg-success bg-opacity-10 text-success fw-bold fs-5" readonly>
                            </div>
                            <button type="submit" name="simpan_penjualan" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="fas fa-save me-2"></i>Simpan Transaksi
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="glass-card p-4">
                        <h5 class="fw-bold mb-4 text-success"><i class="fas fa-history me-2"></i>Riwayat Penjualan</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Sampah</th>
                                        <th>Jumlah</th>
                                        <th>Total</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $res = mysqli_query($conn, "SELECT dp.*, hs.jenis_sampah 
                                                                 FROM data_penjualan dp 
                                                                 JOIN harga_sampah hs ON dp.sampah_id = hs.id 
                                                                 ORDER BY dp.id DESC");
                                    while($p = mysqli_fetch_assoc($res)): ?>
                                    <tr>
                                        <td class="small"><?= date('d/m/Y', strtotime($p['tanggal_penjualan'])); ?></td>
                                        <td class="fw-bold text-dark"><?= $p['jenis_sampah']; ?></td>
                                        <td><?= $p['jumlah']; ?> <span class="text-muted small"><?= $p['satuan']; ?></span></td>
                                        <td class="fw-bold text-success">Rp <?= number_format($p['total_harga'],0,',','.'); ?></td>
                                        <td class="text-center">
                                            <a href="data_penjualan.php?hapus=<?= $p['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger border-0 rounded-circle" 
                                               onclick="return confirm('Hapus data ini? Saldo kas akan berkurang otomatis.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Logic Sidebar Mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if(sidebarCollapse) {
        sidebarCollapse.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Kalkulasi Otomatis
    const sampahSelect = document.getElementById('sampahSelect');
    const hargaInput = document.getElementById('harga');
    const beratInput = document.getElementById('berat');
    const totalInput = document.getElementById('total');
    let hargaPengepul = 0;

    sampahSelect.addEventListener('change', function(){
        const selected = this.options[this.selectedIndex];
        hargaPengepul = selected.getAttribute('data-harga') || 0;
        hargaInput.value = "Rp " + Number(hargaPengepul).toLocaleString('id-ID');
        hitung();
    });

    beratInput.addEventListener('input', hitung);

    function hitung(){
        const hasil = (beratInput.value || 0) * hargaPengepul;
        totalInput.value = "Rp " + Math.round(hasil).toLocaleString('id-ID');
    }
</script>

</body>
</html>