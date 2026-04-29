<?php
session_start();
include '../config/db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_POST['simpan_sampah'])) {
    $id = $_POST['id_sampah'];
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_sampah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $harga = $_POST['harga_sampah'];
    $harga_pengepul = $_POST['harga_pengepul'];
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
        $query = "INSERT INTO harga_sampah 
        (jenis_sampah, keterangan, harga, harga_pengepul, gambar) 
        VALUES 
        ('$jenis', '$keterangan', '$harga', '$harga_pengepul', '$gambar_final')";
    } else {
        $query = "UPDATE harga_sampah SET 
        jenis_sampah = '$jenis',
        keterangan = '$keterangan',
        harga = '$harga',
        harga_pengepul = '$harga_pengepul',
        gambar = '$gambar_final'
        WHERE id = '$id'";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: admin_kelolasampah.php?status=sukses");
        exit;
    }
}

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
    <title>Kelola Sampah | Admin</title>
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
                <a href="data_penarikan.php"><i class="fas fa-coins me-3"></i> Data Penarikan</a>
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
                <h4 class="fw-bold m-0 text-success">Kelola Harga Sampah</h4>
            </div>
        </nav>

        <div class="main-inner">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <?php if(isset($_GET['status'])): ?>
                    <div id="status-alert" class="alert alert-success py-2 px-3 m-0 shadow-sm border-0">
                        <?php 
                            if($_GET['status'] == 'sukses') echo "<i class='fas fa-check-circle me-1'></i> Data Berhasil Disimpan!";
                            else if($_GET['status'] == 'terhapus') echo "<i class='fas fa-check-circle me-1'></i> Data Berhasil Dihapus!";
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card glass-card p-4 border-top border-4" style="border-top-color: var(--hijau-tua) !important;">
                        <h5 class="fw-bold mb-4" id="formTitle" style="color: var(--hijau-tua);"><i class="fas fa-plus-circle me-2"></i> Tambah Data</h5>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_sampah" id="id_sampah">
                            <input type="hidden" name="gambar_lama" id="gambar_lama">

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Jenis Sampah</label>
                                <input type="text" name="jenis_sampah" id="jenis_sampah" class="form-control" required placeholder="Contoh: Botol Plastik">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" class="form-control" rows="2" placeholder="Detail jenis sampah..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Harga per Kg (Nasabah)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" name="harga_sampah" id="harga_sampah" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Harga Pengepul (Admin)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" name="harga_pengepul" id="harga_pengepul" class="form-control">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">Foto Sampah</label>
                                <input type="file" name="gambar" class="form-control form-control-sm">
                            </div>

                            <button type="submit" name="simpan_sampah" class="btn w-100 rounded-pill fw-bold shadow-sm text-white mb-2" style="background-color: var(--hijau-tua);">Simpan Harga</button>
                            <button type="button" onclick="window.location.reload()" class="btn btn-light w-100 rounded-pill border shadow-sm">Batal / Reset</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card glass-card p-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead style="border-bottom: 2px solid var(--hijau-tua);">
                                    <tr>
                                        <th class="text-muted pb-3">Foto</th>
                                        <th class="text-muted pb-3">Jenis & Keterangan</th>
                                        <th class="text-muted pb-3">Harga Beli (Nasabah)</th>
                                        <th class="text-muted pb-3">Harga Jual (Pengepul)</th>
                                        <th class="text-center text-muted pb-3" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = mysqli_query($conn, "SELECT * FROM harga_sampah ORDER BY id DESC");
                                    while ($row = mysqli_fetch_assoc($query)):
                                        // Memotong keterangan jika lebih dari 20 karakter
                                        $ket = $row['keterangan'];
                                        $ket_singkat = (strlen($ket) > 20) ? substr($ket, 0, 20) . '...' : $ket;
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/sampah_img/<?= $row['gambar'] ?: 'default.jpg'; ?>" class="img-preview shadow-sm">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= $row['jenis_sampah']; ?></div>
                                            <div class="small text-muted" title="<?= htmlspecialchars($ket); ?>" style="cursor: help;">
                                                <?= htmlspecialchars($ket_singkat); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: rgba(154, 205, 50, 0.2); color: var(--hijau-tua); font-size: 0.9rem;">
                                                Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-danger border" style="font-size: 0.9rem;">
                                                Rp <?= number_format($row['harga_pengepul'] ?? 0, 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button class="btn btn-sm shadow-sm" style="background: var(--hijau-bg); color: var(--hijau-tua); border: 1px solid var(--hijau-tua);" title="Edit Data"
                                                    onclick="editData('<?= $row['id']; ?>','<?= addslashes($row['jenis_sampah']); ?>','<?= $row['harga']; ?>','<?= $row['gambar']; ?>',`<?= addslashes($row['keterangan']); ?>`,'<?= $row['harga_pengepul']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?hapus=<?= $row['id']; ?>" class="btn btn-outline-danger btn-sm shadow-sm" title="Hapus Data" onclick="return confirm('Apakah Anda yakin ingin menghapus data sampah ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script untuk memindahkan data ke form edit
    function editData(id, jenis, harga, gambar, keterangan, harga_pengepul) {
        document.getElementById('formTitle').innerHTML = "<i class='fas fa-edit me-2'></i> Edit Data";
        document.getElementById('id_sampah').value = id;
        document.getElementById('jenis_sampah').value = jenis;
        document.getElementById('harga_sampah').value = harga;
        document.getElementById('keterangan').value = keterangan;
        document.getElementById('harga_pengepul').value = harga_pengepul;
        document.getElementById('gambar_lama').value = gambar;
        
        // Scroll otomatis ke form jika diakses dari mobile
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Script untuk Toggle Sidebar di Mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    if(sidebarCollapse) sidebarCollapse.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);

    // Animasi hilang untuk alert
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('status-alert');
        if (alert) {
            setTimeout(function() {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(function() {
                    alert.remove();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 500);
            }, 2000);
        }
    });
</script>

</body>
</html>