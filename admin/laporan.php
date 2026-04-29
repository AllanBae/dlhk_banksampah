<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Menangkap filter bulan dan tahun dari URL (jika tidak ada, gunakan bulan & tahun saat ini)
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// --- ASUMSI STRUKTUR DATABASE TRANSAKSI ---
// Pastikan nama tabel "transaksi" dan kolomnya sesuai dengan yang ada di database kamu.
// Query untuk mengambil rekapitulasi berdasarkan bulan dan tahun
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

// Array nama bulan untuk tampilan
$nama_bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan & Inventaris | Admin Bank Sampah</title>
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

        /* --- Sidebar Styling (Sama seperti sebelumnya) --- */
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1040; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul.components { padding: 20px 0; }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); padding-left: 30px; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }

        #content { width: 100%; transition: all 0.3s; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }

        /* --- Cards & Reports --- */
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-stat { transition: transform 0.3s; border-left: 5px solid var(--hijau-tua); }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-box { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(154, 205, 50, 0.2); color: var(--hijau-tua); }
        .table-hover tbody tr:hover { background-color: rgba(154, 205, 50, 0.05); }

        /* --- Print Styles --- */
        @media print {
            body { background: white !important; }
            #sidebar, .top-navbar, .btn-print, .no-print { display: none !important; }
            #content { width: 100%; margin: 0; padding: 0; }
            .main-inner { padding: 0; }
            .glass-card { box-shadow: none !important; border: 1px solid #ddd; margin-bottom: 20px; }
            .card-stat { border-left: 2px solid #000; padding: 15px !important;}
            .icon-box { display: none; }
            .text-muted { color: #000 !important; }
            .print-title { text-align: center; margin-bottom: 20px; }
            .col-md-4 { width: 33.333% !important; float: left; }
            .row { display: block; content: ""; clear: both; }
        }
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
        <nav class="navbar top-navbar sticky-top shadow-sm no-print">
            <div class="d-flex align-items-center">
                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);">Laporan Keuangan</h4>
            </div>
        </nav>

        <div class="main-inner">
            
            <div class="d-none d-print-block print-title">
                <h2>Laporan Bank Sampah EL HA KA</h2>
                <p>Periode: <?= $nama_bulan[(int)$bulan_pilih]; ?> <?= $tahun_pilih; ?></p>
                <hr>
            </div>

            <div class="glass-card p-3 mb-4 no-print border-top border-4" style="border-top-color: var(--hijau-tua) !important;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Pilih Bulan</label>
                        <select name="bulan" class="form-select">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?= ($bulan_pilih == str_pad($i, 2, '0', STR_PAD_LEFT)) ? 'selected' : ''; ?>>
                                    <?= $nama_bulan[$i]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Pilih Tahun</label>
                        <select name="tahun" class="form-select">
                            <?php 
                            $tahun_sekarang = date('Y');
                            for($i = $tahun_sekarang; $i >= $tahun_sekarang - 3; $i--): ?>
                                <option value="<?= $i; ?>" <?= ($tahun_pilih == $i) ? 'selected' : ''; ?>><?= $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn text-white me-2" style="background-color: var(--hijau-tua);">
                            <i class="fas fa-filter me-1"></i> Tampilkan
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-outline-success">
                            <i class="fas fa-print me-1"></i> Cetak PDF / Print
                        </button>
                    </div>
                </form>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="glass-card card-stat p-4 h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fas fa-exchange-alt fa-2x"></i></div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Transaksi</h6>
                                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= $total_transaksi; ?> Kali</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card card-stat p-4 h-100" style="border-left-color: var(--hijau-muda);">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(26, 143, 58, 0.1);"><i class="fas fa-weight-hanging fa-2x"></i></div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Berat Sampah</h6>
                                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= number_format($total_berat, 2, ',', '.'); ?> Kg</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card card-stat p-4 h-100" style="border-left-color: #f39c12;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;"><i class="fas fa-money-bill-wave fa-2x"></i></div>
                            <div>
                                <h6 class="m-0 text-muted fw-bold mb-1">Total Perputaran Uang</h6>
                                <h4 class="fw-bold m-0" style="color: var(--hijau-tua);">Rp <?= number_format($total_uang, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="color: var(--hijau-tua);">
                        <i class="fas fa-list-alt me-2 text-muted"></i>Rincian Setoran (<?= $nama_bulan[(int)$bulan_pilih] . " " . $tahun_pilih; ?>)
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead style="border-bottom: 2px solid var(--hijau-tua);">
                            <tr>
                                <th class="text-muted pb-3">No</th>
                                <th class="text-muted pb-3">Tanggal</th>
                                <th class="text-muted pb-3">Nama Nasabah</th>
                                <th class="text-muted pb-3">Jenis Sampah</th>
                                <th class="text-center text-muted pb-3">Berat (Kg)</th>
                                <th class="text-end text-muted pb-3">Total (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // --- QUERY UNTUK TABEL DETAIL ---
                            // Sesuaikan nama tabel dan JOIN-nya dengan struktur databasemu
                            $no = 1;
                            $query_detail = "
                                SELECT t.tanggal, n.nama_lengkap, s.jenis_sampah, t.berat, t.total_harga 
                                FROM transaksi t
                                JOIN data_nasabah n ON t.id_nasabah = n.id /* Ganti 'id' dengan primary key nasabahmu */
                                JOIN harga_sampah s ON t.id_sampah = s.id /* Ganti 'id' dengan primary key sampahmu */
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
                                <td class="fw-bold text-dark"><?= $d['nama_lengkap']; ?></td>
                                <td><?= $d['jenis_sampah']; ?></td>
                                <td class="text-center"><?= number_format($d['berat'], 2, ',', '.'); ?></td>
                                <td class="text-end fw-bold" style="color: var(--hijau-tua);">Rp <?= number_format($d['total_harga'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted"><i>Tidak ada transaksi pada bulan ini.</i></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(mysqli_num_rows($result) > 0) : ?>
                        <tfoot class="fw-bold">
                            <tr style="background-color: var(--hijau-bg);">
                                <td colspan="4" class="text-center py-3">TOTAL KESELURUHAN</td>
                                <td class="text-center py-3"><?= number_format($total_berat, 2, ',', '.'); ?> Kg</td>
                                <td class="text-end py-3" style="color: var(--hijau-tua); font-size: 1.1rem;">Rp <?= number_format($total_uang, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="mt-4 text-center text-muted no-print">
                <small>&copy; <?= date('Y'); ?> Bank Sampah EL HA KA</small>
            </div>
            
            <div class="d-none d-print-block mt-5 pt-4 text-end">
                <p>Pangkalpinang, <?= date('d F Y'); ?></p>
                <p class="mb-5">Mengetahui,<br>Admin Bank Sampah</p>
                <p class="fw-bold">( ......................................... )</p>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>