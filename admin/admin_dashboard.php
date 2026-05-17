<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// =========================================================================
// --- FITUR AJAX API INTERNAL (AGAR TIDAK REFRESH & TIDAK BUTUH FILE LAIN) ---
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'fetch_filter_sampah') {
    header('Content-Type: application/json');
    
    $filter_mode = isset($_GET['mode']) ? $_GET['mode'] : 'tahun';
    $filter_tahun = isset($_GET['thn_jenis']) ? $_GET['thn_jenis'] : date('Y');
    $filter_bulan = isset($_GET['bln_jenis']) ? $_GET['bln_jenis'] : date('m');

    $labels_api = [];
    $values_api = [];
    $total_akumulasi_api = 0;
    $judul_grafik_api = "";

    if ($filter_mode == 'semua') {
        $judul_grafik_api = "Seluruh Waktu";
        $query = "SELECT hs.jenis_sampah, SUM(t.berat) as total FROM transaksi t JOIN harga_sampah hs ON t.id_sampah = hs.id GROUP BY t.id_sampah ORDER BY total DESC";
    } elseif ($filter_mode == 'bulan' && $filter_bulan != '') {
        $bulan_nama_list = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $judul_grafik_api = "Bulan " . $bulan_nama_list[(int)$filter_bulan] . " $filter_tahun";
        $query = "SELECT hs.jenis_sampah, SUM(t.berat) as total FROM transaksi t JOIN harga_sampah hs ON t.id_sampah = hs.id WHERE MONTH(t.tanggal) = '$filter_bulan' AND YEAR(t.tanggal) = '$filter_tahun' GROUP BY t.id_sampah ORDER BY total DESC";
    } else {
        $judul_grafik_api = "Tahun $filter_tahun";
        $query = "SELECT hs.jenis_sampah, SUM(t.berat) as total FROM transaksi t JOIN harga_sampah hs ON t.id_sampah = hs.id WHERE YEAR(t.tanggal) = '$filter_tahun' GROUP BY t.id_sampah ORDER BY total DESC";
    }

    $q_jenis = mysqli_query($conn, $query);
    while($r = mysqli_fetch_assoc($q_jenis)) {
        $labels_api[] = $r['jenis_sampah'];
        $values_api[] = (float)$r['total'];
        $total_akumulasi_api += (float)$r['total'];
    }

    echo json_encode([
        'labels' => $labels_api,
        'values' => $values_api,
        'total_format' => number_format($total_akumulasi_api, 2, ',', '.'),
        'judul' => $judul_grafik_api
    ]);
    exit; // Hentikan script di sini agar HTML di bawah tidak ikut ter-render saat request AJAX
}
// =========================================================================

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : "Admin";

// --- PENGAMBILAN DATA STATISTIK ---
$total_nasabah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM data_nasabah WHERE role = 'nasabah'"))['total'];
$total_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(saldo) as total FROM data_nasabah WHERE role = 'nasabah'"))['total'];
$query_kas = mysqli_query($conn, "SELECT SUM(total_harga) as total_kas FROM data_penjualan");
$data_kas = mysqli_fetch_assoc($query_kas);
$total_uang_kas = $data_kas['total_kas'] ?? 0;

// --- DATA TREN BERAT BULANAN (GRAFIK 1) ---
$data_berat_bulan = array_fill(0, 12, 0); 
$tahun_ini = date('Y');
$q_tren_bulan = mysqli_query($conn, "SELECT MONTH(tanggal) as bulan, SUM(berat) as total_berat FROM transaksi WHERE YEAR(tanggal) = '$tahun_ini' GROUP BY MONTH(tanggal)");
while($row = mysqli_fetch_assoc($q_tren_bulan)){
    $data_berat_bulan[$row['bulan'] - 1] = $row['total_berat'];
}
$json_data_bulan = json_encode($data_berat_bulan); 

// --- DATA TABUNGAN (GRAFIK 3) ---
$data_tabungan_bulan = array_fill(0, 12, 0);
$q_tabungan_bulan = mysqli_query($conn, "SELECT MONTH(tanggal) as bln, SUM(total_harga) as total FROM transaksi WHERE YEAR(tanggal) = '$tahun_ini' GROUP BY MONTH(tanggal)");
while($r = mysqli_fetch_assoc($q_tabungan_bulan)) {
    $data_tabungan_bulan[$r['bln'] - 1] = (float)$r['total'];
}
$json_tabungan_bulan = json_encode($data_tabungan_bulan);

$q_tahun_awal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(YEAR(tanggal)) as awal FROM transaksi"));
$tahun_awal = $q_tahun_awal['awal'] ?? date('Y');
$labels_tahun = [];
$values_uang_tahun = [];
for ($th = $tahun_awal; $th <= $tahun_ini; $th++) {
    $labels_tahun[] = "Tahun " . $th;
    $values_uang_tahun[$th] = 0;
}
$q_tahunan = mysqli_query($conn, "SELECT YEAR(tanggal) as thn, SUM(total_harga) as total FROM transaksi GROUP BY YEAR(tanggal) ORDER BY thn ASC");
while($r = mysqli_fetch_assoc($q_tahunan)) {
    $values_uang_tahun[$r['thn']] = (float)$r['total'];
}
$json_values_tahun = json_encode(array_values($values_uang_tahun));
$json_labels_tahun = json_encode($labels_tahun);

// Top data
$q_top_sampah = mysqli_query($conn, "SELECT hs.jenis_sampah, SUM(t.berat) as total_berat FROM transaksi t JOIN harga_sampah hs ON t.id_sampah = hs.id GROUP BY t.id_sampah ORDER BY total_berat DESC LIMIT 5");
$q_top_nasabah = mysqli_query($conn, "SELECT dn.nama_lengkap, dn.username, COUNT(t.id) as frekuensi, SUM(t.berat) as total_berat FROM transaksi t JOIN data_nasabah dn ON t.id_nasabah = dn.id GROUP BY t.id_nasabah ORDER BY frekuensi DESC, total_berat DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --hijau-tua: #1e7936; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; }
        #content { width: 100%; min-height: 100vh; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .badge-rank { width: 30px; height: 30px; background: var(--hijau-bg); color: var(--hijau-tua); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } .main-inner { padding: 15px; } }
    </style>
</head>
<body>

<div class="d-flex"> 
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <img src="../assets/img/LOGO BANK SAMPAH EL HA KA.png" height="40" class="me-2">
            <h4 class="fw-bold m-0">El Ha Ka</h4>
        </div>
        <ul class="list-unstyled components">
            <li class="active"><a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a></li>
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
                <h4 class="fw-bold m-0 text-success">Dashboard Statistik</h4>
            </div>
            <div class="bg-light px-3 py-2 rounded-pill shadow-sm border">
                <span class="fw-bold" style="color: var(--hijau-tua);"><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nama_admin); ?></span>
            </div>
        </nav>

        <div class="main-inner">
            <div class="row g-4 mb-4">
                <div class="col-md-4"><div class="glass-card p-4 h-100"><div class="d-flex align-items-center"><div class="icon-box me-3" style="background: rgba(154, 205, 50, 0.2); color: var(--hijau-tua);"><i class="fas fa-users"></i></div><div><h6 class="m-0 text-muted fw-bold mb-1">Total Nasabah Aktif</h6><h3 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= $total_nasabah; ?> Orang</h3></div></div></div></div>
                <div class="col-md-4"><div class="glass-card p-4 h-100"><div class="d-flex align-items-center"><div class="icon-box me-3" style="background: rgba(26, 143, 58, 0.1); color: var(--hijau-tua);"><i class="fas fa-wallet"></i></div><div><h6 class="m-0 text-muted fw-bold mb-1">Total Saldo Nasabah</h6><h3 class="fw-bold m-0" style="color: var(--hijau-tua);">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></h3></div></div></div></div>
                <div class="col-md-4"><div class="glass-card p-4 h-100 border-start border-4 border-primary"><div class="d-flex align-items-center"><div class="icon-box me-3" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;"><i class="fas fa-money-bill-wave"></i></div><div><h6 class="m-0 text-muted fw-bold mb-1">Total Uang Kas EL HA KA</h6><h3 class="fw-bold m-0" style="color: #0d6efd;">Rp <?= number_format($total_uang_kas, 0, ',', '.'); ?></h3></div></div></div></div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-12"><div class="glass-card p-4"><h5 class="fw-bold mb-4" style="color: var(--hijau-tua);"><i class="fas fa-chart-area me-2"></i>Tren Setoran (<?= $tahun_ini; ?>)</h5><canvas id="trendChart" height="80"></canvas></div></div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="glass-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <h5 class="fw-bold m-0" style="color: var(--hijau-tua);"><i class="fas fa-recycle me-2"></i>Total Berat Berdasarkan Jenis Sampah</h5>
                            
                            <div class="d-flex gap-2 align-items-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success btn-filter-mode active" data-mode="semua">Semua</button>
                                    <button class="btn btn-outline-success btn-filter-mode" data-mode="tahun">Pilih Tahun</button>
                                    <button class="btn btn-outline-success btn-filter-mode" data-mode="bulan">Pilih Bulan</button>
                                </div>

                                <select id="selectTahun" class="form-select form-select-sm d-none" style="width: 100px;">
                                    <?php
                                    for($y=date('Y'); $y>=2020; $y--) {
                                        echo "<option value='$y'>$y</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <canvas id="jenisChart" height="100"></canvas>

                        <div id="containerFilterBulan" class="d-flex justify-content-center flex-wrap gap-1 mt-3 d-none">
                            <?php
                            $bulan_singkat = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            for($m=1; $m<=12; $m++) {
                                $val_m = sprintf('%02d', $m);
                                $btn_class = ($val_m == date('m')) ? 'btn-success text-white' : 'btn-outline-success';
                                echo "<button class='btn btn-sm btn-filter-bulan {$btn_class}' data-bulan='{$val_m}'>{$bulan_singkat[$m]}</button>";
                            }
                            ?>
                        </div>
                        
                        <div id="infoBeratJenis" class="mt-4 p-3 rounded text-center" style="background: var(--hijau-bg); border: 1px dashed var(--hijau-tua);">
                            <h5 class="m-0 fw-bold" style="color: var(--hijau-tua);" id="labelTotalBerat">Total Akumulasi Berat Sampah: Menghitung...</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="glass-card p-4 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0" style="color: #0d6efd;"><i class="fas fa-chart-line me-2"></i>Analisis Tabungan Nasabah</h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary active" id="btnTahun">Pilih Tahun</button>
                                <button class="btn btn-outline-primary" id="btnBulan">Pilih Bulan (<?= $tahun_ini ?>)</button>
                            </div>
                        </div>
                        <div style="height: 350px; position: relative;">
                            <canvas id="tabunganChart"></canvas>
                        </div>
                        <div id="infoTabungan" class="mt-4 p-3 rounded text-center" style="background: #f8fbff; border: 1px solid #d0e3ff;">
                            <h5 class="m-0 fw-bold" style="color: #0d6efd;" id="textTabungan">Klik titik pada grafik "Per Tahun" untuk melihat detail</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 border-top border-4" style="border-top-color: var(--hijau-tua) !important;">
                        <h5 class="fw-bold mb-4" style="color: var(--hijau-tua);"><i class="fas fa-medal me-2 text-warning"></i>Nasabah Terajin</h5>
                        <ul class="list-group list-group-flush">
                            <?php $no_n = 1; if(mysqli_num_rows($q_top_nasabah) > 0): while($dn = mysqli_fetch_assoc($q_top_nasabah)): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div class="d-flex align-items-center"><div class="badge-rank me-3 shadow-sm"><?= $no_n++; ?></div><div><h6 class="fw-bold m-0 text-dark"><?= $dn['nama_lengkap']; ?></h6><small class="text-muted">@<?= $dn['username']; ?></small></div></div>
                                    <div class="text-end"><span class="badge rounded-pill bg-success mb-1"><?= $dn['frekuensi']; ?>x Setor</span><br><small class="fw-bold text-muted"><?= number_format($dn['total_berat'], 2, ',', '.'); ?> Kg</small></div>
                                </li>
                            <?php endwhile; else: echo "<p class='text-muted'>Belum ada data.</p>"; endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 border-top border-4" style="border-top-color: var(--hijau-muda) !important;">
                        <h5 class="fw-bold mb-4" style="color: var(--hijau-tua);"><i class="fas fa-box-open me-2 text-info"></i>Sampah Terkumpul</h5>
                        <ul class="list-group list-group-flush">
                            <?php $no_s = 1; if(mysqli_num_rows($q_top_sampah) > 0): while($ds = mysqli_fetch_assoc($q_top_sampah)): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div class="d-flex align-items-center"><div class="badge-rank me-3 shadow-sm"><?= $no_s++; ?></div><h6 class="fw-bold m-0 text-dark"><?= $ds['jenis_sampah']; ?></h6></div>
                                    <h6 class="fw-bold m-0" style="color: var(--hijau-tua);"><?= number_format($ds['total_berat'], 2, ',', '.'); ?> <span class="small text-muted">Kg</span></h6>
                                </li>
                            <?php endwhile; else: echo "<p class='text-muted'>Belum ada data.</p>"; endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarCollapse').addEventListener('click', () => { 
        document.getElementById('sidebar').classList.toggle('active'); 
    });

    // --- GRAFIK 1: TREN BERAT (LAMA) ---
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], datasets: [{ label: 'Total Berat (Kg)', data: <?= $json_data_bulan; ?>, backgroundColor: 'rgba(154, 205, 50, 0.2)', borderColor: '#1A8F3A', borderWidth: 3, fill: true, tension: 0.4 }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });


    // =========================================================================
    // --- SYSTEM AJAX UTK FILTER SAMPAH (TANPA FILE TAMBAHAN & REFRESH) ---
    // =========================================================================
    let currentSampahMode = 'semua';
    let currentSampahTahun = new Date().getFullYear();
    let currentSampahBulan = ("0" + (new Date().getMonth() + 1)).slice(-2);

    const jenisChart = new Chart(document.getElementById('jenisChart'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Berat (Kg)', data: [], backgroundColor: '#9ACD32', borderRadius: 5 }] },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
    });

    function updateJenisChartRealtime() {
        const url = `admin_dashboard.php?action=fetch_filter_sampah&mode=${currentSampahMode}&thn_jenis=${currentSampahTahun}&bln_jenis=${currentSampahBulan}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                jenisChart.data.labels = data.labels;
                jenisChart.data.datasets[0].data = data.values;
                jenisChart.update();
                document.getElementById('labelTotalBerat').innerHTML = `Total Akumulasi Berat Sampah ${data.judul}: ${data.total_format} Kg`;
            })
            .catch(error => console.error('Gagal memuat data:', error));
    }

    document.querySelectorAll('.btn-filter-mode').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-filter-mode').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            currentSampahMode = this.getAttribute('data-mode');
            const selectTahun = document.getElementById('selectTahun');
            const containerBulan = document.getElementById('containerFilterBulan');

            if (currentSampahMode === 'semua') {
                selectTahun.classList.add('d-none');
                containerBulan.classList.add('d-none');
            } else if (currentSampahMode === 'tahun') {
                selectTahun.classList.remove('d-none');
                containerBulan.classList.add('d-none');
            } else if (currentSampahMode === 'bulan') {
                selectTahun.classList.remove('d-none');
                containerBulan.classList.remove('d-none');
            }

            updateJenisChartRealtime();
        });
    });

    document.getElementById('selectTahun').addEventListener('change', function() {
        currentSampahTahun = this.value;
        updateJenisChartRealtime();
    });

    document.querySelectorAll('.btn-filter-bulan').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-filter-bulan').forEach(b => {
                b.classList.remove('btn-success', 'text-white');
                b.classList.add('btn-outline-success');
            });
            this.classList.remove('btn-outline-success');
            this.classList.add('btn-success', 'text-white');

            currentSampahBulan = this.getAttribute('data-bulan');
            updateJenisChartRealtime();
        });
    });

    updateJenisChartRealtime();
    // =========================================================================


    // --- GRAFIK 3: TABUNGAN (DIPERBAIKI AGAR INTERAKTIF DI KEDUA MODE) ---
    const ctxTabungan = document.getElementById('tabunganChart').getContext('2d');
    const dataTahunan = { labels: <?= $json_labels_tahun; ?>, values: <?= $json_values_tahun; ?> };
    const dataBulanan = { labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], values: <?= $json_tabungan_bulan; ?> };

    let currentMode = 'tahun'; 

    const tabunganChart = new Chart(ctxTabungan, {
        type: 'line',
        data: {
            labels: dataTahunan.labels,
            datasets: [{ label: 'Total Tabungan', data: dataTahunan.values, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', fill: true, tension: 0.4, pointRadius: 6, pointHoverRadius: 10, pointBackgroundColor: '#fff', pointBorderWidth: 3 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            onClick: (event, elements) => {
                // Berlaku untuk klik mode Tahunan maupun Bulanan
                if (elements.length > 0) {
                    const idx = elements[0].index;
                    if (currentMode === 'tahun') {
                        const val = dataTahunan.values[idx];
                        const lbl = dataTahunan.labels[idx];
                        document.getElementById('textTabungan').innerHTML = `Detail ${lbl}: <strong>Rp ${val.toLocaleString('id-ID')}</strong>`;
                    } else if (currentMode === 'bulan') {
                        const val = dataBulanan.values[idx];
                        const lbl = dataBulanan.labels[idx];
                        document.getElementById('textTabungan').innerHTML = `Detail Bulan ${lbl} (<?= $tahun_ini ?>): <strong>Rp ${val.toLocaleString('id-ID')}</strong>`;
                    }
                }
            },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ` Rp ${ctx.parsed.y.toLocaleString('id-ID')}` } } },
            scales: { y: { beginAtZero: true, ticks: { callback: (val) => 'Rp ' + val.toLocaleString('id-ID') } } }
        }
    });

    document.getElementById('btnTahun').addEventListener('click', function() {
        currentMode = 'tahun'; 
        this.classList.add('active'); 
        document.getElementById('btnBulan').classList.remove('active');
        document.getElementById('textTabungan').innerHTML = 'Klik titik pada grafik "Per Tahun" untuk melihat detail';
        tabunganChart.data.labels = dataTahunan.labels; 
        tabunganChart.data.datasets[0].data = dataTahunan.values; 
        tabunganChart.update();
    });

    document.getElementById('btnBulan').addEventListener('click', function() {
        currentMode = 'bulan'; 
        this.classList.add('active'); 
        document.getElementById('btnTahun').classList.remove('active');
        document.getElementById('textTabungan').innerHTML = 'Klik titik pada grafik "Per Bulan" untuk melihat detail tabungan';
        tabunganChart.data.labels = dataBulanan.labels; 
        tabunganChart.data.datasets[0].data = dataBulanan.values; 
        tabunganChart.update();
    });
</script>
</body>
</html>