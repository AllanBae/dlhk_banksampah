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
    <title>Kelola Harga Sampah | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        #sidebar { min-width: 250px; max-width: 250px; min-height: 100vh; background: #2c3e50; color: #fff; position: sticky; top: 0; }
        #sidebar .sidebar-header { padding: 20px; background: #1a252f; text-align: center; }
        #sidebar ul li a { padding: 15px 20px; display: block; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; transition: 0.3s; }
        #sidebar ul li a:hover { background: #34495e; color: #fff; padding-left: 25px; }
        #sidebar ul li.active > a { background: #3498db; color: #fff; }
        .main-content { width: 100%; padding: 25px; }
        .glass-card { background: #fff; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .img-preview { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header"><h4 class="fw-bold m-0">Admin Panel</h4></div>
        <ul class="list-unstyled components">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-2"></i> Data Nasabah</a></li>
            <li class="active"><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-2"></i> Kelola Sampah</a></li>
            <li><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-2"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-2"></i> Laporan</a></li>
            <li><a href="../auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h2 class="fw-bold mb-4">Kelola Harga Sampah</h2>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card glass-card p-4">
                    <h5 class="fw-bold mb-3" id="formTitle">Tambah Data</h5>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_sampah" id="id_sampah">
                        <input type="hidden" name="gambar_lama" id="gambar_lama">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jenis Sampah</label>
                            <input type="text" name="jenis_sampah" id="jenis_sampah" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Harga per Kg (Nasabah)</label>
                            <input type="number" name="harga_sampah" id="harga_sampah" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Harga Pengepul (Admin)</label>
                            <input type="number" name="harga_pengepul" id="harga_pengepul" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Foto</label>
                            <input type="file" name="gambar" class="form-control">
                        </div>

                        <button type="submit" name="simpan_sampah" class="btn btn-primary w-100 rounded-pill">Simpan Harga</button>
                        <button type="button" onclick="window.location.reload()" class="btn btn-light w-100 rounded-pill mt-2">Batal</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card glass-card p-3">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Jenis</th>
                                <th>Keterangan</th>
                                <th>Harga</th>
                                <th>Harga Pengepul</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM harga_sampah ORDER BY id DESC");
                            while ($row = mysqli_fetch_assoc($query)):
                            ?>
                            <tr>
                                <td><img src="../assets/sampah_img/<?= $row['gambar'] ?: 'default.jpg'; ?>" class="img-preview"></td>
                                <td class="fw-bold"><?= $row['jenis_sampah']; ?></td>
                                <td><?= $row['keterangan']; ?></td>
                                <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td class="text-danger">
                                    Rp <?= number_format($row['harga_pengepul'] ?? 0, 0, ',', '.'); ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm text-white"
                                    onclick="editData('<?= $row['id']; ?>','<?= $row['jenis_sampah']; ?>','<?= $row['harga']; ?>','<?= $row['gambar']; ?>',`<?= $row['keterangan']; ?>`,'<?= $row['harga_pengepul']; ?>')">Edit</button>
                                    <a href="?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">Hapus</a>
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

<script>
function editData(id, jenis, harga, gambar, keterangan, harga_pengepul) {
    document.getElementById('formTitle').innerText = "Edit Data";
    document.getElementById('id_sampah').value = id;
    document.getElementById('jenis_sampah').value = jenis;
    document.getElementById('harga_sampah').value = harga;
    document.getElementById('keterangan').value = keterangan;
    document.getElementById('harga_pengepul').value = harga_pengepul;
    document.getElementById('gambar_lama').value = gambar;
}
</script>

</body>
</html>