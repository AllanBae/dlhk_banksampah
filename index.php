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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --hijau-tua: #1A8F3A;
            --hijau-muda: #9ACD32;
            --soft-bg: #f8fafc;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--soft-bg); color: #1e293b; }

        /* NAVBAR */
        .navbar-custom { background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(15px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .navbar-brand { font-weight: 800; color: var(--hijau-tua) !important; }
        .btn-daftar { background: linear-gradient(45deg, var(--hijau-tua), var(--hijau-muda)); color: white !important; border-radius: 12px; padding: 10px 25px !important; font-weight: 600; border: none; }

        /* HERO CAROUSEL (SLIDE OTOMATIS) */
        .hero-carousel-section { height: 85vh; position: relative; overflow: hidden; }
        .carousel-item-img { height: 85vh; background-size: cover; background-position: center; position: relative; }
        .carousel-item-img::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7));
        }
        .hero-static-content {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            z-index: 10; text-align: center; color: white; width: 85%;
        }

        /* CARD STYLING */
        .card-custom { border: none; border-radius: 24px; background: white; transition: all 0.4s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; }
        .card-custom:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .judul-potong { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 3em; }

        /* FOOTER & ABOUT CARD */
        .footer-top-modern { background: linear-gradient(135deg, #064e3b 0%, #1A8F3A 100%); padding: 80px 0 40px; color: white; border-radius: 50px 50px 0 0; margin-top: 100px; }
        .about-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 30px; padding: 40px; }
        .social-icon-box { width: 50px; height: 50px; background: white; color: var(--hijau-tua); border-radius: 15px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; transition: 0.3s; text-decoration: none; }
        .social-icon-box:hover { background: var(--hijau-muda); color: white; transform: rotate(10deg); }
        
        .section-title { position: relative; display: inline-block; padding-bottom: 10px; margin-bottom: 30px; }
        .section-title::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 50px; height: 4px; background: var(--hijau-muda); border-radius: 10px; }

        /* Custom Hamburger Menu */
        .navbar-toggler {
            border: none !important;
            padding: 10px;
            border-radius: 12px;
            background: rgba(26, 143, 58, 0.1) !important; /* Hijau transparan */
            transition: all 0.3s ease;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 4px rgba(26, 143, 58, 0.2);
        }

        .navbar-toggler .bi-list {
            color: var(--hijau-tua);
            font-size: 1.8rem;
        }

        /* Mobile Menu Styling */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white;
                margin-top: 15px;
                border-radius: 20px;
                padding: 20px;
                box-shadow: 0 15px 30px rgba(0,0,0,0.1);
                border: 1px solid rgba(0,0,0,0.05);
            }
            
            .nav-item {
                width: 100%;
                text-align: center;
                margin: 10px 0;
            }
            
            .nav-link {
                padding: 12px !important;
                border-radius: 10px;
            }
            
            .nav-link:hover {
                background: var(--soft-bg);
                color: var(--hijau-tua) !important;
            }

            .btn-daftar {
                margin-top: 10px;
                display: block;
                width: 100%;
            }
        }


        /* Styling Visi & Misi Modern */
        .visi-misi-box {
            background: #ffffff;
            border-radius: 30px;
            padding: 40px;
            border: 1px solid rgba(0,0,0,0.02);
            height: 100%;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }

        .visi-misi-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(26, 143, 58, 0.1) !important;
        }

        /* Dekorasi Lingkaran Halus di Belakang Kartu */
        .visi-misi-box::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 120px;
            height: 120px;
            background: rgba(154, 205, 50, 0.05);
            border-radius: 50%;
            transition: 0.4s;
        }

        .visi-misi-box:hover::before {
            background: rgba(154, 205, 50, 0.1);
            transform: scale(1.5);
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau-muda));
            color: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(26, 143, 58, 0.2);
        }

        .visi-misi-box h4 {
            color: var(--hijau-tua);
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .list-misi li {
            padding: 12px 15px;
            background: #f8fafc;
            border-radius: 15px;
            margin-bottom: 12px !important;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .list-misi li:hover {
            background: white;
            border-color: var(--hijau-muda);
            transform: translateX(5px);
        }

    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/LOGO BANK SAMPAH EL HA KA.png" alt="Logo" height="50" class="me-2">
                <div class="lh-1">
                    <span class="fs-5 d-block">BANK SAMPAH</span>
                    <small class="fw-800 text-success" style="font-size: 0.7rem; letter-spacing: 1px;">EL HA KA</small>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-grid-fill"></i> </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link fw-600 mx-2" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-600 mx-2" href="auth/login.php">Masuk</a>
                    </li>
                    <li class="nav-item ms-lg-3 w-100">
                        <a class="nav-link btn-daftar text-white px-4" href="auth/signup.php">
                            <i class="bi bi-person-plus-fill me-2"></i>Daftar Sekarang
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-carousel-section">
        <div id="heroSlider" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="3000">
            <div class="carousel-inner h-100">
                <div class="carousel-item active h-100">
                    <div class="carousel-item-img" style="background-image: url('https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1500&auto=format&fit=crop');"></div>
                </div>
                <div class="carousel-item h-100">
                    <div class="carousel-item-img" style="background-image: url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?q=80&w=1500&auto=format&fit=crop');"></div>
                </div>
            </div>
            
            </div>
        <div class="hero-static-content">
            <h1 class="fw-800 display-3 mb-3">Bersihkan Lingkungan,<br><span style="color: var(--hijau-muda);">Dapatkan Penghasilan</span></h1>
            <p class="lead mb-4 opacity-75">Ubah sampah rumah tangga menjadi saldo tabungan yang bermanfaat.</p>
            <a href="auth/signup.php" class="btn btn-daftar btn-lg px-5 shadow-lg">Mulai Menabung Sekarang</a>
        </div>
    </section>

    <section class="py-5 mt-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold section-title">Harga Tukar Sampah</h2>
                <p class="text-muted">Update harga harian per kilogram</p>
            </div>
            <div class="swiper mySwiperHarga">
                <div class="swiper-wrapper">
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM harga_sampah");
                    while($row = mysqli_fetch_assoc($res)):
                    ?>
                    <div class="swiper-slide p-2">
                        <div class="card card-custom h-100 text-center">
                            <img src="assets/sampah_img/<?= $row['gambar']; ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
                            <div class="card-body">
                                <h6 class="fw-bold text-muted small text-uppercase"><?= $row['jenis_sampah']; ?></h6>
                                <h4 class="fw-800" style="color: var(--hijau-tua);">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white shadow-sm">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold section-title">Berita Terkini</h2>
            </div>
            <div class="swiper swiperBerita pb-5">
                <div class="swiper-wrapper">
                    <?php
                    $query_berita = mysqli_query($conn, "SELECT * FROM berita ORDER BY id DESC");
                    while($b = mysqli_fetch_assoc($query_berita)):
                    ?>
                    <div class="swiper-slide p-2">
                        <div class="card card-custom h-100">
                            <img src="assets/berita_img/<?= $b['gambar'] ?: 'news-default.jpg'; ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
                            <div class="card-body p-4">
                                <small class="text-muted d-block mb-2"><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($b['tanggal'])); ?></small>
                                <h6 class="fw-bold judul-potong"><?= $b['judul']; ?></h6>
                                <button class="btn btn-link p-0 text-decoration-none fw-bold mt-2" style="color: var(--hijau-tua);" data-bs-toggle="modal" data-bs-target="#modalDetailBerita" data-judul="<?= htmlspecialchars($b['judul']); ?>" data-tanggal="<?= date('d F Y', strtotime($b['tanggal'])); ?>" data-isi="<?= htmlspecialchars($b['isi']); ?>" data-gambar="assets/berita_img/<?= $b['gambar'] ?: 'news-default.jpg'; ?>">
                                    Baca selengkapnya
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <section class="py-5" style="background: linear-gradient(to bottom, #f8fafc, #ffffff);">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title d-block">Visi & Misi</h2>
            </div>       
            <div class="row g-4 justify-content-center">
                <div class="col-lg-5 col-md-6">
                    <div class="visi-misi-box shadow-sm">
                        <div class="icon-circle">
                            <i class="bi bi-rocket-takeoff"></i>
                        </div>
                        <h4 class="fw-800 text-uppercase">Visi</h4>
                        <p class="text-secondary lh-lg mb-0" style="font-size: 1.05rem;">
                            Menjadi pelopor pengelolaan sampah mandiri berbasis teknologi untuk mewujudkan masyarakat <strong>Bangka Belitung</strong> yang peduli lingkungan dan mandiri secara ekonomi.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="visi-misi-box shadow-sm">
                        <div class="icon-circle">
                            <i class="bi bi-patch-check"></i>
                        </div>
                        <h4 class="fw-800 text-uppercase">Misi</h4>
                        <ul class="text-secondary list-unstyled list-misi">
                            <li class="d-flex align-items-start">
                                <i class="bi bi-shield-check text-success me-3 fs-5"></i>
                                <span>Memberikan edukasi masif mengenai pentingnya pemilahan sampah dari sumbernya.</span>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="bi bi-shield-check text-success me-3 fs-5"></i>
                                <span>Menyediakan platform tabungan digital yang transparan, aman, dan akurat bagi nasabah.</span>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="bi bi-shield-check text-success me-3 fs-5"></i>
                                <span>Membangun kolaborasi strategis dengan pemerintah dalam pelestarian ekosistem lokal.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5 py-5">
        <div class="text-center mb-4">
            <h3 class="section-title d-block">LOKASI KAMI</h3>
        </div>
        <div class="ratio ratio-21x9 shadow-lg rounded-5 overflow-hidden">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.123456789!2d106.1234567!3d-2.1234567!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMsKwMDcnMjQuNCJTIDEwNsKwMDcnMjQuNCJF!5e0!3m2!1sen!2sid!4v1714900000000" loading="lazy"></iframe>
        </div>
    </div>

    <section class="footer-top-modern">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="about-card shadow-lg">
                        <img src="assets/img/LOGO BANK SAMPAH EL HA KA.png" alt="Logo" height="80" class="mb-4 bg-white p-2 rounded-4 shadow-sm">
                        <h2 class="fw-bold mb-3">Tentang Kami</h2>
                        <p class="lead mb-4 opacity-75">Platform digital untuk menukar sampah menjadi tabungan sekaligus menjaga kelestarian lingkungan.</p>

                        <div class="row justify-content-center mb-4">
                            <div class="col-md-7">
                                <div class="p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-10 shadow-sm">
                                    <h6 class="text-uppercase fw-bold mb-3 small" style="color: var(--hijau-muda); letter-spacing: 2px;">
                                        <i class="bi bi-clock-history me-2"></i>Jadwal Operasional
                                    </h6>
                                    <div class="d-flex justify-content-center gap-4">
                                        <div class="text-center">
                                            <span class="d-block small opacity-50">BUKA HARI</span>
                                            <span class="fw-bold">KAMIS</span>
                                        </div>
                                        <div class="vr opacity-25"></div>
                                        <div class="text-center">
                                            <span class="d-block small opacity-50">JAM</span>
                                            <span class="fw-bold">08.00 - 09.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <a href="https://www.instagram.com/banksampah_elhaka" class="social-icon-box shadow-sm"><i class="bi bi-instagram"></i></a>
                            <a href="https://chat.whatsapp.com/GONj6HKnCiE6wW3zQ4BDHX" class="social-icon-box shadow-sm"><i class="bi bi-whatsapp"></i></a>
                        </div>
                    </div>
                    <p class="mt-5 small opacity-50">© 2026 Bank Sampah DLH Provinsi Bangka Belitung.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalDetailBerita" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-body p-0">
                    <img id="modalImg" src="" class="img-fluid w-100" style="border-radius: 25px 25px 0 0; max-height: 400px; object-fit: cover;">
                    <div class="p-4">
                        <h4 class="fw-bold" id="modalJudul"></h4>
                        <p class="text-muted small" id="modalTanggal"></p>
                        <hr>
                        <div id="modalIsi" class="text-secondary" style="line-height: 1.8;"></div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Swiper Harga
        new Swiper(".mySwiperHarga", {
            slidesPerView: 1.2,
            spaceBetween: 15,
            autoplay: { delay: 3000 },
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: { 768: { slidesPerView: 3 }, 1024: { slidesPerView: 4.5 } }
        });

        // Swiper Berita
        new Swiper(".swiperBerita", {
            slidesPerView: 1.2,
            spaceBetween: 20,
            autoplay: { delay: 4000 },
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
        });

        // Modal Berita Logic
        const modalBerita = document.getElementById('modalDetailBerita');
        modalBerita.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            modalBerita.querySelector('#modalJudul').textContent = btn.getAttribute('data-judul');
            modalBerita.querySelector('#modalTanggal').innerHTML = '<i class="bi bi-calendar-event me-2"></i>' + btn.getAttribute('data-tanggal');
            modalBerita.querySelector('#modalIsi').innerHTML = btn.getAttribute('data-isi').replace(/\n/g, '<br>');
            modalBerita.querySelector('#modalImg').src = btn.getAttribute('data-gambar');
        });
    </script>
</body>
</html>