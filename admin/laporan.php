<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}



// Menangkap filter bulan dan tahun
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Query rekapitulasi
$q_rekap = mysqli_query($conn, "
    SELECT 
        COUNT(id) as total_transaksi, 
        SUM(berat) as total_berat, 
        SUM(total_harga) as total_uang 
    FROM transaksi 
    WHERE MONTH(tanggal) = '$bulan_pilih' AND YEAR(tanggal) = '$tahun_pilih'
");
$rekap = mysqli_fetch_assoc($q_rekap);

$total_transaksi = $rekap['total_transaksi'] ?? 0;
$total_berat = $rekap['total_berat'] ?? 0;
$total_uang = $rekap['total_uang'] ?? 0;

$nama_bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan | Admin</title>
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
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(26, 143, 58, 0.1); color: var(--hijau-tua); }

        /* Overlay & Mobile */
        .sidebar-overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1045; top: 0; left: 0; }
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; }
            #sidebar.active { margin-left: 0; }
            .sidebar-overlay.active { display: block; }
            .main-inner { padding: 15px; }
        }

        /* Print Styles */
        @media print {
            #sidebar, .top-navbar, .no-print, .sidebar-overlay { display: none !important; }
            #content { width: 100%; margin: 0; padding: 0; }
            body { background: #fff !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #eee !important; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

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
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm no-print">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Laporan Keuangan</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="d-none d-print-block text-center mb-4">
                <h2 class="fw-bold">LAPORAN BANK SAMPAH EL HA KA</h2>
                <p class="m-0">Periode: <?= $nama_bulan[(int)$bulan_pilih]; ?> <?= $tahun_pilih; ?></p>
                <hr style="border-top: 2px solid #000;">
            </div>

            <div class="glass-card p-4 mb-4 no-print border-top border-4" style="border-top-color: var(--hijau-tua) !important;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Bulan</label>
                        <select name="bulan" class="form-select">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?= ($bulan_pilih == str_pad($i, 2, '0', STR_PAD_LEFT)) ? 'selected' : ''; ?>>
                                    <?= $nama_bulan[$i]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Tahun</label>
                        <select name="tahun" class="form-select">
                            <?php 
                            $thn_skrg = date('Y');
                            for($i = $thn_skrg; $i >= $thn_skrg - 3; $i--): ?>
                                <option value="<?= $i; ?>" <?= ($tahun_pilih == $i) ? 'selected' : ''; ?>><?= $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn text-white px-4" style="background: var(--hijau-tua);">
                            <i class="fas fa-filter me-1"></i> Tampilkan
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-outline-success px-4 ms-2">
                            <i class="fas fa-print me-1"></i> Cetak PDF
                        </button>
                    </div>
                </form>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fas fa-exchange-alt"></i></div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Transaksi</h6>
                                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= $total_transaksi; ?> Kali</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100" style="border-left: 4px solid var(--hijau-muda);">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fas fa-weight-hanging"></i></div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Berat</h6>
                                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= number_format($total_berat, 2, ',', '.'); ?> Kg</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100" style="border-left: 4px solid #f39c12;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background:rgba(243,156,18,0.1); color:#f39c12;"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Saldo</h6>
                                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);">Rp <?= number_format($total_uang, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4" style="color: var(--hijau-tua);">
                    <i class="fas fa-list-alt me-2 text-muted"></i>Rincian Setoran
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nasabah</th>
                                <th>Jenis Sampah</th>
                                <th class="text-center">Berat (Kg)</th>
                                <th class="text-end">Total (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $query_detail = "
                                SELECT t.tanggal, n.nama_lengkap, s.jenis_sampah, t.berat, t.total_harga 
                                FROM transaksi t
                                JOIN data_nasabah n ON t.id_nasabah = n.id 
                                JOIN harga_sampah s ON t.id_sampah = s.id 
                                WHERE MONTH(t.tanggal) = '$bulan_pilih' AND YEAR(t.tanggal) = '$tahun_pilih'
                                ORDER BY t.tanggal DESC
                            ";
                            $result = mysqli_query($conn, $query_detail);

                            if(mysqli_num_rows($result) > 0) :
                                while($d = mysqli_fetch_assoc($result)) :
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d/m/Y', strtotime($d['tanggal'])); ?></td>
                                <td class="fw-bold"><?= $d['nama_lengkap']; ?></td>
                                <td><?= $d['jenis_sampah']; ?></td>
                                <td class="text-center"><?= number_format($d['berat'], 2, ',', '.'); ?></td>
                                <td class="text-end fw-bold text-success">Rp <?= number_format($d['total_harga'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada transaksi pada periode ini.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(mysqli_num_rows($result) > 0) : ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-center py-3">TOTAL KESELURUHAN</td>
                                <td class="text-center"><?= number_format($total_berat, 2, ',', '.'); ?> Kg</td>
                                <td class="text-end text-success" style="font-size: 1.1rem;">Rp <?= number_format($total_uang, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="d-none d-print-block mt-5 pt-4 text-end">
                <p>Pangkalpinang, <?= date('d F Y'); ?></p>
                <p class="mb-5 pb-3">Mengetahui,<br>Admin Bank Sampah</p>
                <p class="fw-bold">( __________________________ )</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const overlay = document.getElementById('overlay');

    if(sidebarCollapse) {
        sidebarCollapse.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
</script>
</body>
</html>