<?php
session_start();
include '../config/db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
if (isset($_POST['simpan_berita'])) {
    $id = $_POST['id_berita'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $gambar_lama = $_POST['gambar_lama'];
    
    $target_dir = "../assets/berita_img/";
    $nama_gambar = $_FILES['gambar']['name'];

    if ($nama_gambar != "") {
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $ekstensi_boleh = array('png', 'jpg', 'jpeg'); 
        $x = explode('.', $nama_gambar);
        $ekstensi = strtolower(end($x)); 

        if (in_array($ekstensi, $ekstensi_boleh)) {
            $nama_gambar_baru = rand(1, 999) . '-' . str_replace(' ', '-', $nama_gambar);
            
            if (move_uploaded_file($file_tmp, $target_dir . $nama_gambar_baru)) {
                if ($id != "" && $gambar_lama && $gambar_lama != 'default_news.jpg' && file_exists($target_dir . $gambar_lama)) {
                    unlink($target_dir . $gambar_lama);
                }
                $gambar_final = $nama_gambar_baru;
            }
        } else {
            echo "<script>alert('Format salah! Gunakan PNG, JPG, atau JPEG.'); window.location='admin_kelolaberita.php';</script>";
            exit;
        }
    } else {
        $gambar_final = ($id == "") ? 'default_news.jpg' : $gambar_lama; 
    }

    if ($id == "") {
        $query = "INSERT INTO berita (judul, isi, gambar) VALUES ('$judul', '$isi', '$gambar_final')";
        $status = "tambah";
    } else {
        $query = "UPDATE berita SET judul = '$judul', isi = '$isi', gambar = '$gambar_final' WHERE id = '$id'";
        $status = "update";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: admin_kelolaberita.php?status=$status");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $target_dir = "../assets/berita_img/";
    
    $res = mysqli_query($conn, "SELECT gambar FROM berita WHERE id = '$id_hapus'");
    $data = mysqli_fetch_assoc($res);
    
    if ($data) {
        if ($data['gambar'] != 'default_news.jpg' && file_exists($target_dir . $data['gambar'])) {
            unlink($target_dir . $data['gambar']);
        }
        mysqli_query($conn, "DELETE FROM berita WHERE id = '$id_hapus'");
    }
    header("Location: admin_kelolaberita.php?status=hapus");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Berita | Admin Panel</title>
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
        .img-berita { width: 100px; height: 60px; object-fit: cover; border-radius: 5px; }
        .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header"><h4 class="fw-bold m-0">Admin Panel</h4></div>
        <ul class="list-unstyled">
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
            <li><a href="data_nasabah.php"><i class="fas fa-users me-2"></i> Data Nasabah</a></li>
            <li><a href="admin_kelolasampah.php"><i class="fas fa-recycle me-2"></i> Kelola Sampah</a></li>
            <li class="active"><a href="admin_kelolaberita.php"><i class="fas fa-newspaper me-2"></i> Kelola Berita</a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice me-2"></i> Laporan</a></li>
            <li><a href="../auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h2 class="fw-bold mb-4">Kelola Berita & Edukasi</h2>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card glass-card p-4">
                    <h5 class="fw-bold mb-3" id="formTitle">Tulis Berita Baru</h5>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_berita" id="id_berita">
                        <input type="hidden" name="gambar_lama" id="gambar_lama">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Judul</label>
                            <input type="text" name="judul" id="judul" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Isi Berita</label>
                            <textarea name="isi" id="isi" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Foto (PNG/JPG)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            <div id="prevBox" class="mt-2" style="display:none;">
                                <img id="imgPreview" src="" class="img-thumbnail" style="max-height: 80px;">
                            </div>
                        </div>
                        <button type="submit" name="simpan_berita" id="btnSubmit" class="btn btn-primary w-100 rounded-pill">Publikasikan</button>
                        <button type="button" onclick="resetForm()" class="btn btn-light w-100 rounded-pill mt-2">Batal</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card glass-card p-3">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Info</th>
                                <th>Konten</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM berita ORDER BY tanggal DESC");
                            while ($row = mysqli_fetch_assoc($res)):
                            ?>
                            <tr>
                                <td>
                                    <img src="../assets/berita_img/<?= $row['gambar']; ?>" class="img-berita shadow-sm">
                                </td>
                                <td>
                                    <div class="fw-bold text-truncate-2"><?= $row['judul']; ?></div>
                                    <small class="text-muted"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm text-white" onclick="editData('<?= $row['id']; ?>', '<?= addslashes($row['judul']); ?>', `<?= addslashes($row['isi']); ?>`, '<?= $row['gambar']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus berita?')"><i class="fas fa-trash"></i></a>
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
    function editData(id, judul, isi, gambar) {
        document.getElementById('formTitle').innerText = "Edit Berita";
        document.getElementById('btnSubmit').innerText = "Simpan Perubahan";
        document.getElementById('btnSubmit').className = "btn btn-success w-100 rounded-pill";
        
        document.getElementById('id_berita').value = id;
        document.getElementById('judul').value = judul;
        document.getElementById('isi').value = isi;
        document.getElementById('gambar_lama').value = gambar;
        
        document.getElementById('prevBox').style.display = "block";
        document.getElementById('imgPreview').src = "../assets/berita_img/" + gambar;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('formTitle').innerText = "Tulis Berita Baru";
        document.getElementById('btnSubmit').innerText = "Publikasikan";
        document.getElementById('btnSubmit').className = "btn btn-primary w-100 rounded-pill";
        document.getElementById('id_berita').value = "";
        document.getElementById('prevBox').style.display = "none";
    }
</script>
</body>
</html>