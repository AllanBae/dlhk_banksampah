<?php 
include 'config/db.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bank Sampah - Bersihkan Lingkungan, Dapatkan Penghasilan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        /* 1. MENGUBAH VARIABEL WARNA SESUAI LOGO */
        :root {
            --hijau-bg-muda: #f0f8f1; /* Hijau sangat muda untuk latar belakang tipis/border */
            --hijau-tua: #1A8F3A;     /* Hijau gelap dari teks "Bank Sampah" */
            --hijau-muda: #9ACD32;    /* Hijau kekuningan dari ikon daun dan rumah */
        }

        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; }

        .navbar-custom { 
            background-color: rgba(255, 255, 255, 0.95) !important; 
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--hijau-bg-muda);
        }
        .navbar-brand { color: var(--hijau-tua) !important; font-weight: 800; font-size: 22px; }
        .nav-link.nav-modern { color: #475569; font-weight: 600; padding: 10px 15px; transition: 0.3s; }
        .nav-link.nav-modern:hover { color: var(--hijau-tua); }

        @media (max-width: 991.98px) {
            .navbar-collapse { 
                background: #fff; 
                margin-top: 10px; 
                border: 1px solid #e2e8f0; 
                border-radius: 12px;
                padding: 10px;
            }
            .navbar-nav { align-items: flex-start !important; } 
            .nav-item { width: 100%; border-bottom: 1px solid #f1f5f9; } 
            .nav-item:last-child { border-bottom: none; }
            .btn-daftar { 
                margin-left: 0 !important; 
                margin-top: 10px; 
                width: 100%; 
                text-align: left !important; 
                display: block;
            }
        }

        .btn-daftar { 
            background-color: var(--hijau-tua); 
            color: white !important; 
            border-radius: 8px; 
            padding: 8px 20px !important;
            margin-left: 10px;
            font-weight: 600;
        }

        .hero-carousel-section { position: relative; height: 85vh; overflow: hidden; }
        .carousel-inner, .carousel-item, .carousel-item-img { height: 85vh; }
        .carousel-item-img { background-size: cover; background-position: center; position: relative; }
        .carousel-item-img::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7));
        }
        .hero-static-content {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            z-index: 10; width: 90%; max-width: 800px; text-align: center; color: white;
        }

        .scrolling-wrapper { 
            display: flex !important; flex-wrap: nowrap !important; overflow-x: auto !important; 
            gap: 20px; padding: 20px 5px; scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch; scrollbar-width: none;
        }
        .scrolling-wrapper::-webkit-scrollbar { display: none; }
        .scrolling-card { flex: 0 0 auto; width: 300px; }

        /* 2. MENGUBAH TOMBOL UTAMA MENJADI HIJAU */
        .btn-hijau { background-color: var(--hijau-tua); color: white; border-radius: 10px; font-weight: 600; padding: 12px 30px; border: none; transition: 0.3s; }
        .btn-hijau:hover { background-color: var(--hijau-muda); transform: scale(1.05); color: white; }
        
        .card { border: none; border-radius: 15px; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        footer { background: #f1f5f9; border-top: 1px solid #e2e8f0; }

        .text-potong { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; height: 4.5em; }
        .judul-potong { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 3em; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top navbar-custom shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/LOGO BANK SAMPAH EL HA KA.png" alt="Logo" height="80" class="me-2">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-2" style="color: var(--hijau-tua);"></i>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link nav-modern" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link nav-modern" href="auth/login.php">Login</a></li>
                    <li class="nav-item" style="border-bottom: none;"><a class="nav-link btn-daftar" href="auth/signup.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-carousel-section">
        <div id="heroSlider" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="carousel-item-img" style="background-image: url('https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1500&auto=format&fit=crop');"></div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-item-img" style="background-image: url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?q=80&w=1500&auto=format&fit=crop');"></div>
                </div>
            </div>
        </div>
        
        <div class="hero-static-content">
            <h1 class="fw-bold display-4 mb-3">Bersihkan Lingkungan,<br><span style="color: var(--hijau-muda);">Dapatkan Penghasilan</span></h1>
            <p class="lead mb-4">Ubah sampah rumah tangga menjadi saldo tabungan yang bermanfaat bagi masa depan.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="auth/signup.php" class="btn btn-hijau btn-lg shadow">Menabung Sekarang</a>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold d-flex align-items-center justify-content-center">
                    <i class="bi bi-wallet2 me-2" style="color: var(--hijau-tua);"></i> <span>Daftar Harga Sampah</span>
                </h2>
                <p class="text-muted">Harga tukar sampah terkini per kilogram</p>
            </div>
            
            <div class="scrolling-wrapper">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM harga_sampah");
                while($row = mysqli_fetch_assoc($res)):
                ?>
                <div class="scrolling-card">
                    <div class="card shadow-sm border-0 h-100">
                        <img src="assets/sampah_img/<?= $row['gambar']; ?>" class="card-img-top" alt="sampah" style="height: 180px; object-fit: cover; border-radius: 15px 15px 0 0;">
                        <div class="card-body text-center">
                            <h5 class="fw-bold"><?= $row['jenis_sampah']; ?></h5>
                            <p class="fw-bold fs-5" style="color: var(--hijau-tua);">Rp <?= number_format($row['harga'], 0, ',', '.'); ?> <small class="text-muted">/kg</small></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white shadow-sm">
        <div class="container"> 
            <div class="text-center mb-5">
                <h2 class="fw-bold m-0"><i class="bi bi-newspaper me-2" style="color: var(--hijau-tua);"></i>Berita Terkini</h2>
                <p class="text-muted">Update informasi seputar lingkungan</p>
            </div>

            <div class="scrolling-wrapper">
                <?php
                $query_berita = mysqli_query($conn, "SELECT * FROM berita ORDER BY id DESC");
                while($b = mysqli_fetch_assoc($query_berita)):
                ?>
                <div class="scrolling-card">
                    <div class="card shadow-sm h-100 border-0 overflow-hidden">
                        <img src="assets/berita_img/<?= $b['gambar'] ? $b['gambar'] : 'news-default.jpg'; ?>" 
                             class="card-img-top" style="height: 180px; object-fit: cover;"
                             onerror="this.src='https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=300&q=80'">
                        <div class="card-body p-4">
                            <small class="text-muted d-block mb-2"><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($b['tanggal'])); ?></small>
                            <h6 class="fw-bold judul-potong"><?= $b['judul']; ?></h6>
                            <button class="btn btn-link p-0 text-decoration-none fw-bold mt-2" 
                                    style="color: var(--hijau-tua);"
                                    data-bs-toggle="modal" data-bs-target="#modalDetailBerita"
                                    data-judul="<?= htmlspecialchars($b['judul']); ?>"
                                    data-tanggal="<?= date('d F Y', strtotime($b['tanggal'])); ?>"
                                    data-isi="<?= htmlspecialchars($b['isi']); ?>"
                                    data-gambar="assets/berita_img/<?= $b['gambar'] ? $b['gambar'] : 'news-default.jpg'; ?>">
                                Baca selengkapnya
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-5">Visi & Misi Kami</h2>
            <div class="row g-4 text-start">
                <div class="col-md-6">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center border">
                        <i class="bi bi-eye-fill fs-1 mb-3" style="color: var(--hijau-tua);"></i>
                        <h4 class="fw-bold">Visi</h4>
                        <p class="text-muted">Menjadi platform digital pengelolaan sampah nomor satu di Indonesia.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 border">
                        <div class="text-center mb-3">
                            <i class="bi bi-bullseye fs-1" style="color: var(--hijau-tua);"></i>
                            <h4 class="fw-bold mt-2">Misi</h4>
                        </div>
                        <ul class="text-muted">
                            <li>Edukasi pemilahan sampah dari rumah.</li>
                            <li>Akses digital mudah tukar sampah jadi saldo.</li>
                            <li>Ekosistem ekonomi sirkular.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <h3 class="text-center fw-bold">LOKASI KAMI</h3>
        <div class="ratio ratio-16x9 mt-3">
            <iframe src="https://maps.google.com/maps?q=Dinas%20Lingkungan%20Hidup%20dan%20Kehutanan%20Provinsi%20Kep.Bangka%20Belitung&t=&z=13&ie=UTF8&iwloc=&output=embed" allowfullscreen></iframe>
        </div>
    </div>

    <footer class="py-5 text-center">
        <div class="container">
            <h5 class="fw-bold mb-2" style="color: var(--hijau-tua);">Bank Sampah DLH Provinsi BANGKA BELITUNG</h5>
            <p class="small text-muted mb-0">© 2026 Hak Cipta Terlindungi.</p>
        </div>
    </footer>

    <div class="modal fade" id="modalDetailBerita" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Detail Berita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <img id="modalImg" src="" class="img-fluid mb-3 w-100" style="border-radius: 15px; max-height: 400px; object-fit: cover;">
                    <h4 class="fw-bold" id="modalJudul"></h4>
                    <small class="text-muted d-block mb-3" id="modalTanggal"></small>
                    <div id="modalIsi" class="text-secondary" style="line-height: 1.6; text-align: justify;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalBerita = document.getElementById('modalDetailBerita');
            modalBerita.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                modalBerita.querySelector('#modalJudul').textContent = button.getAttribute('data-judul');
                modalBerita.querySelector('#modalTanggal').innerHTML = '<i class="bi bi-calendar-event me-1"></i> ' + button.getAttribute('data-tanggal');
                modalBerita.querySelector('#modalIsi').innerHTML = button.getAttribute('data-isi').replace(/\n/g, '<br>');
                modalBerita.querySelector('#modalImg').src = button.getAttribute('data-gambar');
            });
        });
    </script> 
</body>
</html>