<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Menangkap filter bulan, tahun, dan nasabah
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$nasabah_pilih = isset($_GET['id_nasabah']) ? $_GET['id_nasabah'] : '';

// Menyusun kondisi WHERE tambahan jika nasabah tertentu dipilih
$where_nasabah = "";
if ($nasabah_pilih != "") {
    $where_nasabah = " AND id_nasabah = '$nasabah_pilih' ";
}

// Query rekapitulasi (disesuaikan dengan filter nasabah)
$q_rekap = mysqli_query($conn, "
    SELECT 
        COUNT(id) as total_transaksi, 
        SUM(berat) as total_berat, 
        SUM(total_harga) as total_uang 
    FROM transaksi 
    WHERE MONTH(tanggal) = '$bulan_pilih' AND YEAR(tanggal) = '$tahun_pilih' $where_nasabah
");
$rekap = mysqli_fetch_assoc($q_rekap);

$total_transaksi = $rekap['total_transaksi'] ?? 0;
$total_berat = $rekap['total_berat'] ?? 0;
$total_uang = $rekap['total_uang'] ?? 0;

$nama_bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');

// Ambil nama nasabah yang sedang dipilih untuk keperluan judul cetak cetak PDF
$nama_nasabah_cetak = "Semua Nasabah";
if ($nasabah_pilih != "") {
    $q_nama = mysqli_query($conn, "SELECT nama_lengkap FROM data_nasabah WHERE id = '$nasabah_pilih'");
    $d_nama = mysqli_fetch_assoc($q_nama);
    if ($d_nama) {
        $nama_nasabah_cetak = $d_nama['nama_lengkap'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', Arial, sans-serif; overflow-x: hidden; }
        
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

        /* ==========================================================================
           CSS KHUSUS CETAK (PRINT & SAVE PDF) - DIJAMIN PAS 1 HALAMAN A4 PORTRAIT
           ========================================================================== */
        @media print {
            @page { 
                size: A4 portrait; 
                margin: 12mm 15mm; 
            }
            /* Sembunyikan elemen navigasi & komponen web UI */
            #sidebar, .top-navbar, .no-print, .sidebar-overlay, .navbar { display: none !important; }
            #content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            body { background-color: #ffffff !important; font-size: 10pt !important; color: #2c3e50 !important; }
            .main-inner { padding: 0 !important; }
            
            /* Reset Card styling untuk tampilan lembar cetak formal */
            .glass-card { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; }
            
            /* Penataan Baris & KPI Blocks Agar Tetap Sejajar Horizontal */
            .row { display: table !important; width: 100% !important; table-layout: fixed !important; margin: 0 0 15px 0 !important; }
            .col-md-4 { display: table-cell !important; width: 33.333% !important; padding: 0 8px !important; }
            .col-md-4:first-child { padding-left: 0 !important; }
            .col-md-4:last-child { padding-right: 0 !important; }
            
            /* Ringkas tampilan card rekap */
            .glass-card.p-4 { background-color: #f8faf9 !important; border-radius: 8px !important; border-left: 4px solid var(--hijau-tua) !important; padding: 10px 12px !important; }
            .icon-box { display: none !important; } /* Menyembunyikan ikon agar hasil cetak bersih */
            
            /* Kompaksi ukuran tabel data laporan */
            .table { width: 100% !important; margin-bottom: 15px !important; border-collapse: collapse !important; }
            .table th { background-color: #1A8F3A !important; color: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 6px 10px !important; font-size: 9.5pt !important; border: 1px solid #1A8F3A !important; }
            .table td { padding: 6px 10px !important; font-size: 9.5pt !important; border-bottom: 1px solid #e8edea !important; }
            .table-light { background-color: #f1f6f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            
            /* STRUKTUR BAGIAN TANDA TANGAN PAS PROPORSIONAL */
            .ttd-section { 
                margin-top: 30px !important; 
                display: table !important; 
                width: 100% !important; 
                border-collapse: collapse !important;
                page-break-inside: avoid !important; 
            }
            .ttd-row { display: table-row !important; }
            .ttd-cell-left { display: table-cell !important; width: 65% !important; }
            .ttd-cell-right { display: table-cell !important; width: 35% !important; text-align: center !important; }
            .ttd-space { height: 65px !important; } /* Tinggi ruang kosong yang ideal untuk tanda tangan pulpen & stempel */
            .ttd-section p { margin: 0 !important; font-size: 10pt !important; line-height: 1.4 !important; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="d-flex"> 
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" height="40" class="me-2">
            <h4 class="fw-bold m-0">El Ha Ka</h4>
        </div>
        <ul class="list-unstyled components">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a></li>
            <li><a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>
            <li class="active"><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a></li>
            <li><a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a></li>
            <li><a href="data_penjualan.php"><i class="fas fa-shopping-cart me-3"></i> Data Penjualan</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm no-print">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Laporan Setoran</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="d-none d-print-block text-center pb-2 mb-3" style="border-bottom: 3px double var(--hijau-tua);">
                <h3 class="fw-bold m-0 text-uppercase" style="color: var(--hijau-tua); letter-spacing: 0.5px;">LAPORAN BANK SAMPAH EL HA KA</h3>
                <p class="m-0 small text-muted">Sistem Manajemen Distribusi & Rekapitulasi Setoran Nasabah</p>
            </div>

            <div class="d-none d-print-block mb-3">
                <table style="width: 100%; font-size: 9.5pt;">
                    <tr>
                        <td style="width: 12%; color: #7f8c8d;">Nasabah</td>
                        <td style="font-weight: 600;">: <?= $nama_nasabah_cetak; ?></td>
                        <td style="text-align: right; color: #7f8c8d;">Hari Ini: <?= date('d/m/Y'); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #7f8c8d;">Periode</td>
                        <td style="font-weight: 600;">: <?= $nama_bulan[(int)$bulan_pilih]; ?> <?= $tahun_pilih; ?></td>
                        <td></td>
                    </tr>
                </table>
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
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Tahun</label>
                        <select name="tahun" class="form-select">
                            <?php 
                            $thn_skrg = date('Y');
                            for($i = $thn_skrg; $i >= $thn_skrg - 3; $i--): ?>
                                <option value="<?= $i; ?>" <?= ($tahun_pilih == $i) ? 'selected' : ''; ?>><?= $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Nasabah</label>
                        <select name="id_nasabah" class="form-select">
                            <option value="">-- Semua Nasabah --</option>
                            <?php 
                            $q_nasabah = mysqli_query($conn, "SELECT id, nama_lengkap FROM data_nasabah ORDER BY nama_lengkap ASC");
                            while($rn = mysqli_fetch_assoc($q_nasabah)) :
                            ?>
                                <option value="<?= $rn['id']; ?>" <?= ($nasabah_pilih == $rn['id']) ? 'selected' : ''; ?>>
                                    <?= $rn['nama_lengkap']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn text-white px-3 flex-fill" style="background: var(--hijau-tua);">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-outline-success px-3 flex-fill">
                            <i class="fas fa-print me-1"></i> Cetak
                        </button>
                    </div>
                </form>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fas fa-exchange-alt"></i></div>
                            <div>
                                <h6 class="m-0 text-muted small fw-bold mb-1">Total Transaksi</h6>
                                <h5 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= $total_transaksi; ?> Kali</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100" style="border-left-color: var(--hijau-muda) !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fas fa-weight-hanging"></i></div>
                            <div>
                                <h6 class="m-0 text-muted small fw-bold mb-1">Total Berat</h6>
                                <h5 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= number_format($total_berat, 2, ',', '.'); ?> Kg</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100" style="border-left-color: #f39c12 !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background:rgba(243,156,18,0.1); color:#f39c12;"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <h6 class="m-0 text-muted small fw-bold mb-1">Total Saldo</h6>
                                <h5 class="fw-bold m-0" style="color: #d35400;"><?= ($total_uang > 0) ? 'Rp '.number_format($total_uang, 0, ',', '.') : 'Rp 0'; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3 no-print" style="color: var(--hijau-tua);">
                    <i class="fas fa-list-alt me-2 text-muted"></i>Rincian Setoran
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 7%;">No</th>
                                <th style="width: 18%;">Tanggal</th>
                                <th style="width: 35%;">Nama Nasabah</th>
                                <th style="width: 20%;">Jenis Sampah</th>
                                <th class="text-center" style="width: 10%;">Berat (Kg)</th>
                                <th class="text-end" style="width: 10%;">Total (Rp)</th>
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
                                WHERE MONTH(t.tanggal) = '$bulan_pilih' AND YEAR(t.tanggal) = '$tahun_pilih' $where_nasabah
                                ORDER BY t.tanggal DESC
                            ";
                            $result = mysqli_query($conn, $query_detail);

                            if(mysqli_num_rows($result) > 0) :
                                while($d = mysqli_fetch_assoc($result)) :
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
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
                        <tfoot class="table-light fw-bold" style="border-top: 2px solid var(--hijau-tua);">
                            <tr>
                                <td colspan="4" class="text-center py-2">TOTAL KESELURUHAN</td>
                                <td class="text-center"><?= number_format($total_berat, 2, ',', '.'); ?> Kg</td>
                                <td class="text-end text-success">Rp <?= number_format($total_uang, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="d-none d-print-block ttd-section">
                <div class="ttd-row">
                    <div class="ttd-cell-left"></div>
                    
                    <div class="ttd-cell-right">
                        <p>Pangkalpinang, <?= date('d F Y'); ?></p>
                        <p class="fw-bold" style="color: #444;">Mengetahui,<br>Admin Bank Sampah</p>
                        
                        <div class="ttd-space"></div>
                        
                        <p class="fw-bold" style="color: #2c3e50;">( __________________________ )</p>
                    </div>
                </div>
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