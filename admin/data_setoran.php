<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Jakarta');
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ==========================================
// BLOK PROSES CRUD (CREATE, UPDATE, DELETE)
// ==========================================

// 1. PROSES TAMBAH DATA
if (isset($_POST['tambah_setoran'])) {
    $id_nasabah = $_POST['id_nasabah'];
    $id_sampah = $_POST['id_sampah'];
    $berat = $_POST['berat'];
    $tanggal = date('Y-m-d'); 

    // Ambil harga sampah dari database berdasarkan id_sampah yang dipilih
    $q_harga = mysqli_query($conn, "SELECT harga FROM harga_sampah WHERE id = '$id_sampah'");
    $data_harga = mysqli_fetch_assoc($q_harga);
    
    // Hitung total harga
    $total_harga = $berat * $data_harga['harga'];

    // Insert ke tabel transaksi
    $insert = mysqli_query($conn, "INSERT INTO transaksi (tanggal, id_nasabah, id_sampah, berat, total_harga) VALUES ('$tanggal', '$id_nasabah', '$id_sampah', '$berat', '$total_harga')");

    if ($insert) {
        // Update saldo nasabah (tambah saldo)
        mysqli_query($conn, "UPDATE data_nasabah SET saldo = saldo + $total_harga WHERE id = '$id_nasabah'");
        echo "<script>alert('Data setoran berhasil ditambahkan!'); window.location='data_setoran.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data!'); window.location='data_setoran.php';</script>";
    }
}

// 2. PROSES HAPUS DATA
if (isset($_POST['hapus_setoran'])) {
    $id_transaksi = $_POST['id_transaksi'];

    // Ambil data transaksi dulu untuk mengurangi saldo nasabah
    $q_trx = mysqli_query($conn, "SELECT id_nasabah, total_harga FROM transaksi WHERE id = '$id_transaksi'");
    $trx = mysqli_fetch_assoc($q_trx);

    // Hapus dari tabel transaksi
    $delete = mysqli_query($conn, "DELETE FROM transaksi WHERE id = '$id_transaksi'");

    if ($delete) {
        // Kurangi saldo nasabah karena transaksi dibatalkan/dihapus
        mysqli_query($conn, "UPDATE data_nasabah SET saldo = saldo - {$trx['total_harga']} WHERE id = '{$trx['id_nasabah']}'");
        echo "<script>alert('Data setoran berhasil dihapus!'); window.location='data_setoran.php';</script>";
    }
}

// ==========================================
// AMBIL DATA UNTUK FORM DROPDOWN & TABEL
// ==========================================

// Ambil data nasabah untuk dropdown form
$nasabah_dropdown = mysqli_query($conn, "SELECT id, nama_lengkap FROM data_nasabah WHERE role = 'nasabah' ORDER BY nama_lengkap ASC");

// Ambil data sampah untuk dropdown form
$sampah_dropdown = mysqli_query($conn, "SELECT id, jenis_sampah, harga FROM harga_sampah ORDER BY jenis_sampah ASC");

// Query untuk menampilkan data di tabel (JOIN)
$query_setoran = mysqli_query($conn, "
    SELECT 
        t.id,
        t.tanggal, 
        n.nama_lengkap, 
        s.jenis_sampah, 
        s.harga as harga_per_kg, 
        t.berat, 
        t.total_harga 
    FROM transaksi t
    JOIN data_nasabah n ON t.id_nasabah = n.id
    JOIN harga_sampah s ON t.id_sampah = s.id
    ORDER BY t.tanggal DESC, t.id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Setoran | Admin</title>
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
        #content { width: 100%; transition: all 0.3s; }
        .top-navbar { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 15px 25px; }
        .main-inner { padding: 30px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .modal-profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--hijau-muda); }
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } .sidebar-overlay.active { display: block; position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1030; } }
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
                <h4 class="fw-bold m-0 text-success">Daftar Setoran Nasabah</h4>
        </div>
        </nav>

        <div class="main-inner">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold m-0" style="color: var(--hijau-tua);">
                            <i class="fas fa-list me-2"></i>Riwayat Setoran
                        </h5>
                    </div>
                    <button class="btn text-white fw-medium shadow-sm" style="background-color: var(--hijau-tua);" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus-circle me-1"></i> Tambah Setoran
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead style="border-bottom: 2px solid var(--hijau-tua);">
                            <tr>
                                <th class="text-muted pb-3">No</th>
                                <th class="text-muted pb-3">Tanggal Setoran</th>
                                <th class="text-muted pb-3">Nama Penyetor</th>
                                <th class="text-muted pb-3">Nama Sampah</th>
                                <th class="text-center text-muted pb-3">Berat (KG)</th>
                                <th class="text-end text-muted pb-3">Total Harga</th>
                                <th class="text-center text-muted pb-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_setoran) > 0) :
                                while($d = mysqli_fetch_assoc($query_setoran)) :
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><span class="fw-medium text-dark"><i class="far fa-calendar-alt text-muted me-1"></i> <?= date('d M Y', strtotime($d['tanggal'])); ?></span></td>
                                <td class="fw-bold text-dark"><?= $d['nama_lengkap']; ?></td>
                                <td><span class="badge-sampah"><?= $d['jenis_sampah']; ?></span></td>
                                <td class="text-center fw-bold text-dark"><?= number_format($d['berat'], 2, ',', '.'); ?> <small class="text-muted fw-normal">Kg</small></td>
                                <td class="text-end fw-bold" style="color: var(--hijau-tua);">Rp <?= number_format($d['total_harga'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $d['id']; ?>" title="Hapus Data">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalHapus<?= $d['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="">
                                            <div class="modal-header border-0 pb-0">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center pb-4">
                                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                                <h5 class="fw-bold">Hapus Data Setoran?</h5>
                                                <p class="text-muted">Setoran <b><?= $d['nama_lengkap']; ?></b> sebesar <b><?= $d['berat']; ?> Kg</b> akan dihapus.<br>Saldo nasabah juga akan dikurangi secara otomatis.</p>
                                                <input type="hidden" name="id_transaksi" value="<?= $d['id']; ?>">
                                                <div class="mt-4">
                                                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="hapus_setoran" class="btn btn-danger">Ya, Hapus Data</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>Belum ada data setoran sampah.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 text-center text-muted">
                <small>&copy; <?= date('Y'); ?> Bank Sampah EL HA KA</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background-color: var(--hijau-tua); color: white;">
                <h5 class="modal-title fw-bold" id="modalTambahLabel"><i class="fas fa-plus-circle me-2"></i>Input Setoran Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Tanggal Setoran</label>
                        <input type="text" class="form-control bg-light" value="<?= date('d F Y'); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Pilih Nasabah <span class="text-danger">*</span></label>
                        <select name="id_nasabah" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Nama Nasabah --</option>
                            <?php while($n = mysqli_fetch_assoc($nasabah_dropdown)): ?>
                                <option value="<?= $n['id']; ?>"><?= $n['nama_lengkap']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Jenis Sampah <span class="text-danger">*</span></label>
                        <select name="id_sampah" id="pilih_sampah" class="form-select" required onchange="hitungOtomatis()">
                            <option value="" disabled selected data-harga="0">-- Pilih Jenis Sampah --</option>
                            <?php while($s = mysqli_fetch_assoc($sampah_dropdown)): ?>
                                <option value="<?= $s['id']; ?>" data-harga="<?= $s['harga']; ?>">
                                    <?= $s['jenis_sampah']; ?> (Rp <?= number_format($s['harga'],0,',','.'); ?>/Kg)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold text-muted small">Berat (Kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.1" name="berat" id="input_berat" class="form-control" placeholder="0.00" required oninput="hitungOtomatis()">
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-bold text-muted small">Total Estimasi Harga</label>
                            <input type="text" id="tampil_total" class="form-control bg-light fw-bold text-success" value="Rp 0" readonly>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_setoran" class="btn text-white fw-bold" style="background-color: var(--hijau-tua);">
                        <i class="fas fa-save me-1"></i> Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Toggle Sidebar Mobile ---
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if(sidebarCollapse) {
        sidebarCollapse.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // --- Kalkulator Otomatis Form Tambah ---
    function hitungOtomatis() {
        // Ambil elemen dropdown sampah dan input berat
        var selectSampah = document.getElementById("pilih_sampah");
        var inputBerat = document.getElementById("input_berat").value;

        // Ambil atribut 'data-harga' dari option yang sedang dipilih
        var hargaPerKg = selectSampah.options[selectSampah.selectedIndex].getAttribute("data-harga");
        
        // Pastikan nilainya bukan null/undefined
        hargaPerKg = hargaPerKg ? parseFloat(hargaPerKg) : 0;
        inputBerat = inputBerat ? parseFloat(inputBerat) : 0;

        // Hitung total
        var total = hargaPerKg * inputBerat;

        // Format angka ke format Rupiah dan tampilkan
        var formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
        document.getElementById("tampil_total").value = formatter.format(total);
    }
</script>
</body>
</html>