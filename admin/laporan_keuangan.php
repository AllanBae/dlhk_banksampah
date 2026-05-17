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
$where_transaksi = "";
$where_penarikan = "";
if ($nasabah_pilih != "") {
    $where_transaksi = " AND id_nasabah = '$nasabah_pilih' ";
    $where_penarikan = " AND user_id = '$nasabah_pilih' "; // Disesuaikan dengan kolom user_id
}

// 1. Hitung Total Uang Masuk dari Setoran Sampah (Tabel Transaksi)
$q_masuk = mysqli_query($conn, "
    SELECT SUM(total_harga) as total_masuk 
    FROM transaksi 
    WHERE MONTH(tanggal) = '$bulan_pilih' AND YEAR(tanggal) = '$tahun_pilih' $where_transaksi
");
$d_masuk = mysqli_fetch_assoc($q_masuk);
$total_masuk = $d_masuk['total_masuk'] ?? 0;

// 2. Hitung Total Uang Keluar dari Penarikan Saldo (Kolom disesuaikan ke tanggal_penarikan & user_id)
$q_keluar = mysqli_query($conn, "
    SELECT SUM(jumlah) as total_keluar 
    FROM penarikan 
    WHERE MONTH(tanggal_penarikan) = '$bulan_pilih' AND YEAR(tanggal_penarikan) = '$tahun_pilih' $where_penarikan
");
$d_keluar = mysqli_fetch_assoc($q_keluar);
$total_keluar = $d_keluar['total_keluar'] ?? 0;

// 3. Hitung Saldo Bersih Berjalan pada Periode Tersebut
$saldo_periode = $total_masuk - $total_keluar;

$nama_bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');

// Ambil nama nasabah yang sedang dipilih untuk keperluan judul cetak PDF
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
    <title>Laporan Keuangan Nasabah | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', Arial, sans-serif; overflow-x: hidden; }
        
        /* Sidebar Styling */
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        /* Menu Utama Sidebar (Teks Label) */
        .menu-label { padding: 15px 25px 5px 25px; font-size: 0.75rem; text-uppercase: true; letter-spacing: 1px; color: rgba(255,255,255,0.5); font-weight: bold; display: block; }
        
        #sidebar ul li a { padding: 12px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; font-size: 0.95rem; }
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

        @media print {
            @page { size: A4 portrait; margin: 12mm 15mm; }
            #sidebar, .top-navbar, .no-print, .sidebar-overlay, .navbar { display: none !important; }
            #content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            body { background-color: #ffffff !important; font-size: 10pt !important; color: #2c3e50 !important; }
            .main-inner { padding: 0 !important; }
            .glass-card { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; }
            
            .row { display: table !important; width: 100% !important; table-layout: fixed !important; margin: 0 0 15px 0 !important; }
            .col-md-4 { display: table-cell !important; width: 33.333% !important; padding: 0 8px !important; }
            
            .glass-card.p-4 { background-color: #f8faf9 !important; border-radius: 8px !important; border-left: 4px solid var(--hijau-tua) !important; padding: 10px 12px !important; }
            .icon-box { display: none !important; }
            
            .table { width: 100% !important; margin-bottom: 15px !important; border-collapse: collapse !important; }
            .table th { background-color: #1A8F3A !important; color: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 6px 10px !important; font-size: 9.5pt !important; border: 1px solid #1A8F3A !important; }
            .table td { padding: 6px 10px !important; font-size: 9.5pt !important; border-bottom: 1px solid #e8edea !important; }
            .table-light { background-color: #f1f6f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            
            .ttd-section { margin-top: 30px !important; display: table !important; width: 100% !important; border-collapse: collapse !important; page-break-inside: avoid !important; }
            .ttd-row { display: table-row !important; }
            .ttd-cell-left { display: table-cell !important; width: 65% !important; }
            .ttd-cell-right { display: table-cell !important; width: 35% !important; text-align: center !important; }
            .ttd-space { height: 65px !important; }
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
            
            <span class="menu-label">MANAJEMEN DATA</span>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a></li>
            <li><a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li><a href="data_penjualan.php"><i class="fas fa-shopping-cart me-3"></i> Data Penjualan</a></li>

            <span class="menu-label">KELOLA</span>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>

            <span class="menu-label">LAPORAN</span>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan Setoran</a></li>
            <li class="active"><a href="laporan_keuangan.php"><i class="fas fa-university me-3"></i> Keuangan Nasabah</a></li>

            <span class="menu-label">PENGGUNA</span>
            <li><a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar top-navbar sticky-top d-flex justify-content-between align-items-center shadow-sm no-print">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3"><i class="fas fa-bars"></i></button>
                <h4 class="fw-bold m-0 text-success">Laporan Keuangan Nasabah</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="d-none d-print-block text-center pb-2 mb-3" style="border-bottom: 3px double var(--hijau-tua);">
                <h3 class="fw-bold m-0 text-uppercase" style="color: var(--hijau-tua); letter-spacing: 0.5px;">LAPORAN KEUANGAN KAS NASABAH</h3>
                <p class="m-0 small text-muted">Bank Sampah El Ha Ka - Log Keluar Masuk Dana Operasional Nasabah</p>
            </div>

            <div class="d-none d-print-block mb-3">
                <table style="width: 100%; font-size: 9.5pt;">
                    <tr>
                        <td style="width: 15%; color: #7f8c8d;">Nama Nasabah</td>
                        <td style="font-weight: 600;">: <?= $nama_nasabah_cetak; ?></td>
                        <td style="text-align: right; color: #7f8c8d;">Tanggal Cetak: <?= date('d/m/Y'); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #7f8c8d;">Periode Laporan</td>
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
                    <div class="glass-card p-4 h-100" style="border-left: 4px solid #27ae60;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(39,174,96,0.1); color: #27ae60;"><i class="fas fa-arrow-down"></i></div>
                            <div>
                                <h6 class="m-0 text-muted small fw-bold mb-1">Total Uang Masuk (Setoran)</h6>
                                <h5 class="fw-bold m-0 text-success">Rp <?= number_format($total_masuk, 0, ',', '.'); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100" style="border-left: 4px solid #c0392b;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(192,57,43,0.1); color: #c0392b;"><i class="fas fa-arrow-up"></i></div>
                            <div>
                                <h6 class="m-0 text-muted small fw-bold mb-1">Total Uang Keluar (Tarik)</h6>
                                <h5 class="fw-bold m-0 text-danger">Rp <?= number_format($total_keluar, 0, ',', '.'); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100" style="border-left: 4px solid #f39c12;">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3" style="background: rgba(243,156,18,0.1); color: #f39c12;"><i class="fas fa-scale-balanced"></i></div>
                            <div>
                                <h6 class="m-0 text-muted small fw-bold mb-1">Selisih Kas Periode Ini</h6>
                                <h5 class="fw-bold m-0 <?= $saldo_periode >= 0 ? 'text-primary' : 'text-warning' ?>">
                                    Rp <?= number_format($saldo_periode, 0, ',', '.'); ?>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3 no-print" style="color: var(--hijau-tua);">
                    <i class="fas fa-list-alt me-2 text-muted"></i>Rincian Arus Mutasi Keuangan
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 7%;">No</th>
                                <th style="width: 15%;">Tanggal</th>
                                <th style="width: 30%;">Nama Nasabah</th>
                                <th style="width: 28%;">Keterangan Transaksi</th>
                                <th class="text-center" style="width: 10%;">Jenis</th>
                                <th class="text-end" style="width: 10%;">Nominal (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            
                            // Query UNION yang disesuaikan secara utuh dengan database asli Anda
                            $query_mutasi = "
                                (SELECT t.tanggal as tanggal, n.nama_lengkap, CONCAT('Setoran Sampah (', s.jenis_sampah, ')') as keterangan, 'Masuk' as tipe, t.total_harga as jumlah
                                 FROM transaksi t
                                 JOIN data_nasabah n ON t.id_nasabah = n.id 
                                 JOIN harga_sampah s ON t.id_sampah = s.id 
                                 WHERE MONTH(t.tanggal) = '$bulan_pilih' AND YEAR(t.tanggal) = '$tahun_pilih' $where_transaksi)
                                UNION ALL
                                (SELECT p.tanggal_penarikan as tanggal, n.nama_lengkap, 'Penarikan Saldo Tunai' as keterangan, 'Keluar' as tipe, p.jumlah as jumlah
                                 FROM penarikan p
                                 JOIN data_nasabah n ON p.user_id = n.id
                                 WHERE MONTH(p.tanggal_penarikan) = '$bulan_pilih' AND YEAR(p.tanggal_penarikan) = '$tahun_pilih' $where_penarikan)
                                ORDER BY tanggal DESC
                            ";
                            
                            $result = mysqli_query($conn, $query_mutasi);

                            if($result && mysqli_num_rows($result) > 0) :
                                while($d = mysqli_fetch_assoc($result)) :
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= date('d/m/Y', strtotime($d['tanggal'])); ?></td>
                                <td class="fw-bold"><?= $d['nama_lengkap']; ?></td>
                                <td><?= $d['keterangan']; ?></td>
                                <td class="text-center">
                                    <?php if($d['tipe'] == 'Masuk'): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1 rounded no-print">Masuk</span>
                                        <span class="d-none d-print-block text-success">Masuk</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded no-print">Keluar</span>
                                        <span class="d-none d-print-block text-danger">Keluar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?= $d['tipe'] == 'Masuk' ? 'text-success' : 'text-danger'; ?>">
                                    <?= $d['tipe'] == 'Masuk' ? '+' : '-'; ?> Rp <?= number_format($d['jumlah'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada rekaman arus keuangan pada periode ini.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if($result && mysqli_num_rows($result) > 0) : ?>
                        <tfoot class="table-light fw-bold" style="border-top: 2px solid var(--hijau-tua);">
                            <tr>
                                <td colspan="5" class="text-end py-2">TOTAL MASUK (KREDIT):</td>
                                <td class="text-end text-success">Rp <?= number_format($total_masuk, 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end py-2">TOTAL KELUAR (DEBIT):</td>
                                <td class="text-end text-danger">Rp <?= number_format($total_keluar, 0, ',', '.'); ?></td>
                            </tr>
                            <tr class="table-secondary">
                                <td colspan="5" class="text-end py-2">SELISIH BERSIH PERIODE:</td>
                                <td class="text-end <?= $saldo_periode >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    Rp <?= number_format($saldo_periode, 0, ',', '.'); ?>
                                </td>
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
                        <p class="fw-bold" style="color: #444;">Bendahara / Admin Bank Sampah</p>
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