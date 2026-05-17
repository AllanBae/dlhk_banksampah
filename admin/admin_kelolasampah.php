<?php
session_start();
include '../config/db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// --- LOGIKA PAGINATION (TETAP ADA) ---
$limit = 5; 
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

$query_hitung = mysqli_query($conn, "SELECT * FROM harga_sampah");
$jumlah_data = mysqli_num_rows($query_hitung);
$total_halaman = ceil($jumlah_data / $limit);

// --- PROSES SIMPAN / UPDATE (TAMBAH SATUAN) ---
if (isset($_POST['simpan_sampah'])) {
    $id = $_POST['id_sampah'];
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_sampah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $harga = $_POST['harga_sampah'];
    $harga_pengepul = $_POST['harga_pengepul'];
    $satuan = $_POST['satuan']; // Fitur Baru: Satuan
    $gambar_lama = $_POST['gambar_lama'];

    $target_dir = "../assets/sampah_img/";
    $nama_gambar = $_FILES['gambar']['name'];

    if ($nama_gambar != "") {
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $nama_gambar_baru = rand(1, 999) . '-' . $nama_gambar;
        if (move_uploaded_file($file_tmp, $target_dir . $nama_gambar_baru)) {
            if ($gambar_lama && $gambar_lama != 'default.jpg' && file_exists($target_dir . $gambar_lama)) {
                unlink($target_dir . $gambar_lama);
            }
            $gambar_final = $nama_gambar_baru;
        }
    } else {
        $gambar_final = $gambar_lama; 
    }

    if ($id == "") {
        // Insert dengan Satuan
        $query = "INSERT INTO harga_sampah (jenis_sampah, keterangan, harga, harga_pengepul, satuan, gambar) 
                  VALUES ('$jenis', '$keterangan', '$harga', '$harga_pengepul', '$satuan', '$gambar_final')";
    } else {
        // Update dengan Satuan
        $query = "UPDATE harga_sampah SET jenis_sampah = '$jenis', keterangan = '$keterangan', 
                  harga = '$harga', harga_pengepul = '$harga_pengepul', satuan = '$satuan', gambar = '$gambar_final' WHERE id = '$id'";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: admin_kelolasampah.php?status=sukses&pagi=$halaman");
        exit;
    }
}

// --- FITUR HAPUS (TETAP ADA) ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $target_dir = "../assets/sampah_img/";
    $res = mysqli_query($conn, "SELECT gambar FROM harga_sampah WHERE id = '$id_hapus'");
    $data = mysqli_fetch_assoc($res);
    if ($data['gambar'] && $data['gambar'] != 'default.jpg' && file_exists($target_dir . $data['gambar'])) {
        unlink($target_dir . $data['gambar']);
    }
    mysqli_query($conn, "DELETE FROM harga_sampah WHERE id = '$id_hapus'");
    header("Location: admin_kelolasampah.php?status=terhapus");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Sampah | EL HA KA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --hijau-tua: #1A8F3A; --hijau-muda: #9ACD32; --hijau-bg: #f4f9f5; }
        body { background-color: var(--hijau-bg); font-family: 'Segoe UI', sans-serif; }
        #sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background: var(--hijau-tua); color: #fff; transition: all 0.3s; z-index: 1050; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul li a { padding: 15px 25px; display: block; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); padding-left: 30px; }
        #sidebar ul li.active > a { background: var(--hijau-muda); color: #fff; border-radius: 0 30px 30px 0; margin-right: 20px; box-shadow: 0 4px 15px rgba(154, 205, 50, 0.4); }
        #content { width: 100%; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .img-preview { width: 65px; height: 65px; object-fit: cover; border-radius: 10px; }
        .pagination .page-link { color: var(--hijau-tua); border: none; font-weight: 600; margin: 0 2px; border-radius: 5px; }
        .pagination .page-item.active .page-link { background: var(--hijau-tua); color: #fff; }
        @media (max-width: 768px) { #sidebar { margin-left: -260px; position: fixed; z-index: 1000; } #sidebar.active { margin-left: 0; } }
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
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-3"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-3"></i> Data Nasabah</a></li>
            <li><a href="data_setoran.php"><i class="fas fa-balance-scale me-3"></i> Data Setoran</a></li>
            <li><a href="data_penarikan.php"><i class="fas fa-hand-holding-usd me-3"></i> Data Penarikan</a></li>
            <li class="active"><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-3"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-3"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-3"></i> Laporan</a></li>
            <li><a href="admin_profil.php"><i class="fas fa-user me-3"></i> Profil</a></li>
            <li><a href="data_penjualan.php"><i class="fas fa-shopping-cart me-3"></i> Data Penjualan</a></li>
            <li><a href="../auth/logout.php" class="text-warning"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar bg-white shadow-sm p-3 mb-4">
            <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none"><i class="fas fa-bars"></i></button>
            <h5 class="fw-bold m-0 text-success ms-2">Manajemen Harga Sampah</h5>
        </nav>

        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card glass-card p-4 border-top border-4 border-success">
                        <h5 class="fw-bold mb-4" id="formTitle"><i class="fas fa-plus-circle me-2"></i> Tambah Data</h5>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_sampah" id="id_sampah">
                            <input type="hidden" name="gambar_lama" id="gambar_lama">
                            
                            <div class="mb-3">
                                <label class="small fw-bold">Jenis Sampah</label>
                                <input type="text" name="jenis_sampah" id="jenis_sampah" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold">Satuan Takaran</label>
                                <select name="satuan" id="satuan" class="form-select" required>
                                    <option value="Kg">Kg (Kilogram)</option>
                                    <option value="Liter">Liter</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-success">Harga Beli (Nasabah)</label>
                                <input type="number" name="harga_sampah" id="harga_sampah" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-danger">Harga Jual (Pengepul)</label>
                                <input type="number" name="harga_pengepul" id="harga_pengepul" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold">Foto Produk</label>
                                <input type="file" name="gambar" class="form-control">
                            </div>
                            <button type="submit" name="simpan_sampah" class="btn btn-success w-100 rounded-pill fw-bold">Simpan Data</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card glass-card p-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>FOTO</th>
                                        <th>JENIS & SATUAN</th>
                                        <th>HARGA BELI</th>
                                        <th>HARGA JUAL</th>
                                        <th class="text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = mysqli_query($conn, "SELECT * FROM harga_sampah ORDER BY id DESC LIMIT $halaman_awal, $limit");
                                    while ($row = mysqli_fetch_assoc($query)):
                                    ?>
                                    <tr>
                                        <td><img src="../assets/sampah_img/<?= $row['gambar'] ?: 'default.jpg'; ?>" class="img-preview shadow-sm"></td>
                                        <td>
                                            <div class="fw-bold"><?= $row['jenis_sampah']; ?></div>
                                            <div class="badge bg-light text-success border small">Per <?= $row['satuan']; ?></div>
                                        </td>
                                        <td><span class="badge bg-success-subtle text-success px-3 py-2">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></span></td>
                                        <td><span class="badge bg-danger-subtle text-danger px-3 py-2">Rp <?= number_format($row['harga_pengepul'], 0, ',', '.'); ?></span></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success border-0" 
                                                onclick="editData('<?= $row['id']; ?>','<?= addslashes($row['jenis_sampah']); ?>','<?= $row['harga']; ?>','<?= $row['gambar']; ?>',`<?= addslashes($row['keterangan']); ?>`,'<?= $row['harga_pengepul']; ?>', '<?= $row['satuan']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus data?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 px-2">
                            <div class="small fw-bold text-muted">Halaman: <?= $halaman; ?> / <?= $total_halaman; ?></div>
                            <nav>
                                <ul class="pagination pagination-sm m-0">
                                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?pagi=<?= $halaman - 1; ?>"><i class="fas fa-angle-left"></i></a>
                                    </li>
                                    <?php for($i=1; $i<=$total_halaman; $i++): ?>
                                        <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?pagi=<?= $i; ?>"><?= $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?pagi=<?= $halaman + 1; ?>"><i class="fas fa-angle-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi Edit (Ditambah parameter satuan)
    function editData(id, jenis, harga, gambar, keterangan, harga_pengepul, satuan) {
        document.getElementById('formTitle').innerHTML = "<i class='fas fa-edit me-2'></i> Edit Data";
        document.getElementById('id_sampah').value = id;
        document.getElementById('jenis_sampah').value = jenis;
        document.getElementById('harga_sampah').value = harga;
        document.getElementById('keterangan').value = keterangan;
        document.getElementById('harga_pengepul').value = harga_pengepul;
        document.getElementById('satuan').value = satuan; // Set nilai select satuan
        document.getElementById('gambar_lama').value = gambar;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if(sidebarCollapse) {
        sidebarCollapse.onclick = () => sidebar.classList.toggle('active');
    }
</script>
</body>
</html>