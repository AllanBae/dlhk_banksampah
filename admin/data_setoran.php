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

// 1. PROSES TAMBAH DATA (Multi-Item, Pilih Tanggal, & Gabung Item yang Sama)
if (isset($_POST['tambah_setoran'])) {
    $id_nasabah = $_POST['id_nasabah'];
    $tanggal = $_POST['tanggal']; 
    
    $id_sampah_array = $_POST['id_sampah'];
    $berat_array = $_POST['berat'];

    // Wadah penampung untuk menggabungkan jika ada id_sampah yang sama di form inputan
    $gabung_inputan = [];

    foreach ($id_sampah_array as $key => $id_sampah) {
        $berat = floatval($berat_array[$key]);
        if ($berat <= 0) continue;

        if (isset($gabung_inputan[$id_sampah])) {
            $gabung_inputan[$id_sampah] += $berat; // Jika jenis sampah sama, jumlahkan beratnya
        } else {
            $gabung_inputan[$id_sampah] = $berat;
        }
    }

    $sukses = true;
    $total_semua_harga = 0;

    // Lakukan proses INSERT dari data yang sudah bersih/tergabung
    foreach ($gabung_inputan as $id_sampah => $berat) {
        // Ambil harga sampah dari database
        $q_harga = mysqli_query($conn, "SELECT harga FROM harga_sampah WHERE id = '$id_sampah'");
        $data_harga = mysqli_fetch_assoc($q_harga);
        
        // Hitung total harga per item
        $total_harga = $berat * $data_harga['harga'];
        $total_semua_harga += $total_harga;

        // Insert ke tabel transaksi
        $insert = mysqli_query($conn, "INSERT INTO transaksi (tanggal, id_nasabah, id_sampah, berat, total_harga) VALUES ('$tanggal', '$id_nasabah', '$id_sampah', '$berat', '$total_harga')");
        
        if (!$insert) {
            $sukses = false;
        }
    }

    if ($sukses && $total_semua_harga > 0) {
        // Update saldo nasabah akumulasi dari semua sampah yang disetor
        mysqli_query($conn, "UPDATE data_nasabah SET saldo = saldo + $total_semua_harga WHERE id = '$id_nasabah'");
        echo "<script>alert('Data setoran berhasil ditambahkan!'); window.location='data_setoran.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data setoran!'); window.location='data_setoran.php';</script>";
    }
}

// 2. PROSES HAPUS DATA
if (isset($_POST['hapus_setoran'])) {
    $id_nasabah = $_POST['id_nasabah'];
    $tanggal = $_POST['tanggal'];

    // Ambil total harga akumulasi dari tanggal & nasabah tersebut untuk mengurangi saldo
    $q_trx = mysqli_query($conn, "SELECT SUM(total_harga) as total_kembali FROM transaksi WHERE id_nasabah = '$id_nasabah' AND tanggal = '$tanggal'");
    $trx = mysqli_fetch_assoc($q_trx);
    $total_kembali = $trx['total_kembali'] ? $trx['total_kembali'] : 0;

    // Hapus data transaksi pada tanggal dan nasabah yang bersangkutan
    $delete = mysqli_query($conn, "DELETE FROM transaksi WHERE id_nasabah = '$id_nasabah' AND tanggal = '$tanggal'");

    if ($delete) {
        // Kurangi saldo nasabah karena transaksi dibatalkan/dihapus
        mysqli_query($conn, "UPDATE data_nasabah SET saldo = saldo - $total_kembali WHERE id = '$id_nasabah'");
        echo "<script>alert('Data setoran pada tanggal tersebut berhasil dihapus!'); window.location='data_setoran.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!'); window.location='data_setoran.php';</script>";
    }
}

// ==========================================
// AMBIL DATA UNTUK FORM DROPDOWN & TABEL
// ==========================================

// Ambil data nasabah untuk dropdown form
$nasabah_dropdown = mysqli_query($conn, "SELECT id, nama_lengkap FROM data_nasabah WHERE role = 'nasabah' ORDER BY nama_lengkap ASC");

// Ambil data sampah beserta satuan untuk dropdown form 
$sampah_options = [];
$sampah_dropdown = mysqli_query($conn, "SELECT id, jenis_sampah, harga, satuan FROM harga_sampah ORDER BY jenis_sampah ASC");
while($row = mysqli_fetch_assoc($sampah_dropdown)) {
    $sampah_options[] = $row;
}

// Query TAMPIL DATA (Menggabungkan nama sampah & berat, serta menjumlahkan jika jenis sampahnya sama pada hari itu)
$query_setoran = mysqli_query($conn, "
    SELECT 
        t.tanggal, 
        t.id_nasabah,
        n.nama_lengkap, 
        GROUP_CONCAT(CONCAT(s.jenis_sampah, ' (', sub.total_berat_item, ' ', s.satuan, ')') SEPARATOR ', ') as gabung_sampah,
        SUM(t.berat) as total_berat, 
        SUM(t.total_harga) as total_harga 
    FROM transaksi t
    JOIN data_nasabah n ON t.id_nasabah = n.id
    JOIN harga_sampah s ON t.id_sampah = s.id
    JOIN (
        SELECT id_nasabah, id_sampah, tanggal, SUM(berat) as total_berat_item 
        FROM transaksi 
        GROUP BY id_nasabah, id_sampah, tanggal
    ) sub ON t.id_nasabah = sub.id_nasabah AND t.id_sampah = sub.id_sampah AND t.tanggal = sub.tanggal
    GROUP BY t.tanggal, t.id_nasabah
    ORDER BY t.tanggal DESC, n.nama_lengkap ASC
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
        .badge-sampah { background-color: #e8f5e9; color: var(--hijau-tua); padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; display: inline-block; }
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; } #sidebar.active { margin-left: 0; } .sidebar-overlay.active { display: block; position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1030; } }
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
            <li class="active"><a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan Setoran</a></li>
            <li><a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a></li>
            <li><a href="data_penjualan.php"><i class="fas fa-shopping-cart me-3"></i> Data Penjualan</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
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
                        <i class="fas fa-plus-circle me-1"></i> Tambah Setorand
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead style="border-bottom: 2px solid var(--hijau-tua);">
                            <tr>
                                <th class="text-muted pb-3">No</th>
                                <th class="text-muted pb-3">Tanggal Setoran</th>
                                <th class="text-muted pb-3">Nama Penyetor</th>
                                <th class="text-muted pb-3">Daftar Sampah (Jumlah Takaran)</th>
                                <th class="text-end text-muted pb-3">Total Pendapatan</th>
                                <th class="text-center text-muted pb-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_setoran) > 0) :
                                while($d = mysqli_fetch_assoc($query_setoran)) :
                                    $modal_id = $d['id_nasabah'] . date('Ymd', strtotime($d['tanggal']));
                                    
                                    // Menghilangkan list duplikat teks hasil GROUP_CONCAT di MySQL
                                    $array_items = array_unique(explode(', ', $d['gabung_sampah']));
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><span class="fw-medium text-dark"><i class="far fa-calendar-alt text-muted me-1"></i> <?= date('d M Y', strtotime($d['tanggal'])); ?></span></td>
                                <td class="fw-bold text-dark"><?= $d['nama_lengkap']; ?></td>
                                <td>
                                    <?php 
                                        foreach($array_items as $item) {
                                            echo "<span class='badge-sampah mb-1 me-1'>$item</span>";
                                        }
                                    ?>
                                </td>
                                <td class="text-end fw-bold" style="color: var(--hijau-tua);">Rp <?= number_format($d['total_harga'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $modal_id; ?>" title="Hapus Data">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalHapus<?= $modal_id; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="">
                                            <div class="modal-header border-0 pb-0">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center pb-4">
                                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                                <h5 class="fw-bold">Hapus Seluruh Data Setoran Ini?</h5>
                                                <p class="text-muted">Semua setoran milik <b><?= $d['nama_lengkap']; ?></b> pada tanggal <b><?= date('d M Y', strtotime($d['tanggal'])); ?></b> akan dihapus.<br>Saldo nasabah akan dikurangi otomatis sebesar <b>Rp <?= number_format($d['total_harga'], 0, ',', '.'); ?></b>.</p>
                                                
                                                <input type="hidden" name="id_nasabah" value="<?= $d['id_nasabah']; ?>">
                                                <input type="hidden" name="tanggal" value="<?= $d['tanggal']; ?>">
                                                
                                                <div class="mt-4">
                                                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="hapus_setoran" class="btn btn-danger">Ya, Hapus Semua</button>
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
                            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>Belum ada data setoran sampah.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background-color: var(--hijau-tua); color: white;">
                <h5 class="modal-title fw-bold" id="modalTambahLabel"><i class="fas fa-plus-circle me-2"></i>Input Setoran Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">Tanggal Setoran <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">Pilih Nasabah <span class="text-danger">*</span></label>
                            <select name="id_nasabah" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Nama Nasabah --</option>
                                <?php while($row_n = mysqli_fetch_assoc($nasabah_dropdown)): ?>
                                    <option value="<?= $row_n['id']; ?>"><?= $row_n['nama_lengkap']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="text-muted">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-success m-0"><i class="fas fa-cubes me-1"></i> Daftar Item Sampah</h6>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="tambahBarisSampah()">
                            <i class="fas fa-plus me-1"></i> Tambah Jenis Sampah
                        </button>
                    </div>

                    <div id="container-sampah">
                        <div class="row baris-sampah align-items-end mb-2">
                            <div class="col-md-5">
                                <label class="form-label text-muted small fw-bold">Jenis Sampah</label>
                                <select name="id_sampah[]" class="form-select pilih-sampah" required onchange="hitungTotalSemua()">
                                    <option value="" disabled selected data-harga="0" data-satuan="Kg">-- Pilih Jenis Sampah --</option>
                                    <?php foreach($sampah_options as $s): ?>
                                        <option value="<?= $s['id']; ?>" data-harga="<?= $s['harga']; ?>" data-satuan="<?= $s['satuan']; ?>">
                                            <?= $s['jenis_sampah']; ?> (Rp <?= number_format($s['harga'],0,',','.'); ?>/<?= $s['satuan']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">Jumlah (<span class="label-satuan">Kg</span>)</label>
                                <input type="number" step="0.01" min="0.1" name="berat[]" class="form-control input-berat" placeholder="0.00" required oninput="hitungTotalSemua()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">Subtotal</label>
                                <input type="text" class="form-control bg-light tampil-subtotal" value="Rp 0" readonly>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-danger btn-sm h-100" onclick="hapusBarisSampah(this)"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6">
                            <div class="card p-2 border-success bg-light text-end">
                                <span class="text-muted small fw-bold">TOTAL ESTIMASI KESELURUHAN</span>
                                <h4 class="fw-bold text-success m-0" id="total_semua_estimasi">Rp 0</h4>
                            </div>
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

    // --- Ambil data sampah dari server PHP ke JSON Javascript ---
    const dataSampahJs = <?= json_encode($sampah_options); ?>;

    // --- Fungsi Menambah Baris Form Sampah Baru ---
    function tambahBarisSampah() {
        var container = document.getElementById("container-sampah");
        
        var optionsHtml = '<option value="" disabled selected data-harga="0" data-satuan="Kg">-- Pilih Jenis Sampah --</option>';
        dataSampahJs.forEach(function(s) {
            var hargaFormated = new Intl.NumberFormat('id-ID').format(s.harga);
            optionsHtml += `<option value="${s.id}" data-harga="${s.harga}" data-satuan="${s.satuan}">${s.jenis_sampah} (Rp ${hargaFormated}/${s.satuan})</option>`;
        });

        var newRow = document.createElement("div");
        newRow.className = "row baris-sampah align-items-end mb-2";
        newRow.innerHTML = `
            <div class="col-md-5">
                <select name="id_sampah[]" class="form-select pilih-sampah" required onchange="hitungTotalSemua()">
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" min="0.1" name="berat[]" class="form-control input-berat" placeholder="0.00" required oninput="hitungTotalSemua()">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control bg-light tampil-subtotal" value="Rp 0" readonly>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-danger btn-sm h-100" onclick="hapusBarisSampah(this)"><i class="fas fa-trash-alt"></i></button>
            </div>
        `;
        container.appendChild(newRow);
    }

    // --- Fungsi Menghapus Baris Form Sampah ---
    function deleteBarisSampah(btn) {
        var rows = document.getElementsByClassName('baris-sampah');
        if(rows.length > 1) {
            btn.closest('.baris-sampah').remove();
            hitungTotalSemua();
        } else {
            alert('Minimal harus menginputkan 1 item jenis sampah.');
        }
    }
    // Alias mapper fallback agar click aman
    window.hapusBarisSampah = deleteBarisSampah;

    // --- Kalkulator Otomatis Akumulasi & Deteksi Satuan Dinamis ---
    function hitungTotalSemua() {
        var baris = document.getElementsByClassName('baris-sampah');
        var grandTotal = 0;
        var formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

        for (var i = 0; i < baris.length; i++) {
            var selectSampah = baris[i].getElementsByClassName('pilih-sampah')[0];
            var inputBerat = baris[i].getElementsByClassName('input-berat')[0].value;
            var inputSubtotal = baris[i].getElementsByClassName('tampil-subtotal')[0];
            var labelSatuan = baris[i].querySelector('.label-satuan');

            var hargaPerKg = selectSampah.options[selectSampah.selectedIndex].getAttribute("data-harga");
            var satuanTerpilih = selectSampah.options[selectSampah.selectedIndex].getAttribute("data-satuan");
            
            hargaPerKg = hargaPerKg ? parseFloat(hargaPerKg) : 0;
            satuanTerpilih = satuanTerpilih ? satuanTerpilih : 'Kg';
            inputBerat = inputBerat ? parseFloat(inputBerat) : 0;

            // Merubah label text (Kg / Liter) di form secara real-time berdasarkan DB
            if (labelSatuan) {
                labelSatuan.innerText = satuanTerpilih;
            }

            var subtotal = hargaPerKg * inputBerat;
            grandTotal += subtotal;

            inputSubtotal.value = formatter.format(subtotal);
        }

        document.getElementById("total_semua_estimasi").innerText = formatter.format(grandTotal);
    }
</script>
</body>
</html>