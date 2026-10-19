<?php
// admin/index.php - Dashboard Admin Luxury ParkirPro
require_once '../config.php';
check_role(['admin']);

// Mengambil beberapa data statistik ringkas dari database untuk ditampilkan di dashboard
try {
    $total_user = $pdo->query("SELECT COUNT(*) FROM tb_user")->fetchColumn();
    $total_kendaraan = $pdo->query("SELECT COUNT(*) FROM tb_kendaraan")->fetchColumn();
    $total_area = $pdo->query("SELECT COUNT(*) FROM tb_area_parkir")->fetchColumn();
    $total_tarif = $pdo->query("SELECT COUNT(*) FROM tb_tarif")->fetchColumn();
} catch (Exception $e) {
    $total_user = $total_kendaraan = $total_area = $total_tarif = 0;
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | ParkirPro Luxury</title>
    <!-- Script pencegah FOUC (Flash of Unstyled Theme) saat refresh -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('parkirpro_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.4s ease, color 0.4s ease;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: rgba(25, 29, 36, 0.85);
            backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .sidebar {
            background: rgba(255, 255, 255, 0.9);
            border-right: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sidebar-brand {
            padding: 24px;
            font-size: 1.25rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-nav {
            padding: 0 16px;
            list-style: none;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: inherit;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 6px;
            transition: all 0.3s ease;
            opacity: 0.75;
        }

        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            opacity: 1;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 8px 20px -4px rgba(99, 102, 241, 0.4);
        }

        /* Main Content Wrapper */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        /* Luxury Glass Cards */
        .luxury-card {
            background: rgba(33, 37, 41, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .luxury-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .luxury-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.2);
        }

        .text-gradient {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Top Navbar Header */
        .top-navbar {
            background: rgba(33, 37, 41, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 12px 24px;
            margin-bottom: 30px;
        }

        [data-bs-theme="light"] .top-navbar {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <i class="bi bi-shield-shaded text-primary fs-4"></i>
            <span>Parkir<span class="text-gradient">Pro</span></span>
        </div>
        <ul class="sidebar-nav mt-3">
            <li>
                <a href="index.php" class="nav-link active">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="user.php" class="nav-link">
                    <i class="bi bi-people-fill"></i> CRUD User
                </a>
            </li>
            <li>
                <a href="tarif.php" class="nav-link">
                    <i class="bi bi-cash-stack"></i> Tarif Parkir
                </a>
            </li>
            <li>
                <a href="area.php" class="nav-link">
                    <i class="bi bi-p-square-fill"></i> Area Parkir
                </a>
            </li>
            <li>
                <a href="kendaraan.php" class="nav-link">
                    <i class="bi bi-car-front"></i> Data Kendaraan
                </a>
            </li>
            <li>
                <a href="masuk.php" class="nav-link">
                    <i class="bi bi-car-front-fill"></i> Parkir Masuk
                </a>
            </li>
            <li>
                <a href="keluar.php" class="nav-link">
                    <i class="bi bi-car-front-fill"></i> Parkir Keluar
                </a>
            </li>
            <li>
                <a href="log.php" class="nav-link">
                    <i class="bi bi-journal-text"></i> Log Aktivitas
                </a>
            </li>
            <hr class="border-secondary opacity-25 my-3">
            <li>
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content Wrapper -->
    <main class="main-content">
        
        <!-- Top Header Navbar -->
        <div class="top-navbar d-flex justify-content-between align-items-center" data-aos="fade-down" data-aos-duration="600">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0 fw-bold">Dashboard Admin</h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Ganti Tema">
                    <i id="themeIcon" class="bi bi-sun-fill"></i>
                </button>

                <!-- User Profile Badge -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-inherit" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                            <?= substr($_SESSION['user']['nama_lengkap'], 0, 1) ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <h6 class="mb-0 fw-bold small"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?></h6>
                            <span class="text-muted" style="font-size: 11px;">Administrator</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item text-danger py-2" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Welcome Banner Section -->
        <div class="luxury-card p-4 p-md-5 mb-4 position-relative overflow-hidden" data-aos="fade-up" data-aos-duration="800">
            <div class="row align-items-center position-relative z-1">
                <div class="col-lg-8">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3 fw-semibold">
                        <i class="bi bi-shield-check me-1"></i> Admin Control Center
                    </span>
                    <h2 class="fw-extrabold mb-2">Halo, <span class="text-gradient"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?></span>! 👋</h2>
                    <p class="text-muted mb-4">
                        Kelola data master sistem parkir, konfigurasi tarif, pemantauan area, serta akses log aktivitas pengguna dengan mudah dan aman di sini.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="user.php" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                            <i class="bi bi-people me-1"></i> Kelola User
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards Grid -->
        <div class="row g-4 mb-4">
            <!-- Card 1: Total Users -->
            <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="100">
                <div class="luxury-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 fs-4">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold">Aktif</span>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_user ?></h3>
                    <p class="text-muted small mb-0">Total Pengguna Sistem</p>
                </div>
            </div>

            <!-- Card 2: Total Kendaraan -->
            <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="200">
                <div class="luxury-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-4 fs-4">
                            <i class="bi bi-car-front-fill"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">Database</span>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_kendaraan ?></h3>
                    <p class="text-muted small mb-0">Kendaraan Terdaftar</p>
                </div>
            </div>

            <!-- Card 3: Area Parkir -->
            <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="300">
                <div class="luxury-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4 fs-4">
                            <i class="bi bi-p-square-fill"></i>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold">Slot</span>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_area ?></h3>
                    <p class="text-muted small mb-0">Area Parkir Aktif</p>
                </div>
            </div>

            <!-- Card 4: Tarif Parkir -->
            <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="400">
                <div class="luxury-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-4 fs-4">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info fw-semibold">Kategori</span>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_tarif ?></h3>
                    <p class="text-muted small mb-0">Skema Tarif Parkir</p>
                </div>
            </div>
        </div>

    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Inisialisasi AOS Animation
        AOS.init({
            once: true,
            offset: 40
        });

        // Toggle Sidebar Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarMenu = document.getElementById('sidebarMenu');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebarMenu.classList.toggle('show');
            });
        }

        // Dark / Light Mode Toggler Logic dengan LocalStorage Persistence
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const htmlElement = document.documentElement;

        // Fungsi sinkronisasi ikon tombol sesuai tema aktif
        function syncThemeIcon(theme) {
            if (theme === 'light') {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
            } else {
                themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
            }
        }

        // Jalankan penyesuaian ikon saat pertama kali halaman dimuat
        const currentSavedTheme = localStorage.getItem('parkirpro_theme') || 'dark';
        syncThemeIcon(currentSavedTheme);

        // Event listener saat tombol diganti
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Terapkan ke HTML
            htmlElement.setAttribute('data-bs-theme', newTheme);
            
            // Simpan ke localStorage
            localStorage.setItem('parkirpro_theme', newTheme);
            
            // Update ikon
            syncThemeIcon(newTheme);
        });
    </script>
</body>
</html>