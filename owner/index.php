<?php
// owner/index.php - Panel Owner & Laporan Rekap Transaksi ParkirPro Luxury
require_once '../config.php';
check_role(['owner']);

// Handling Filter Tanggal
$is_filtered = isset($_GET['tgl_mulai']) || isset($_GET['tgl_selesai']);
$tgl_mulai   = isset($_GET['tgl_mulai']) && $_GET['tgl_mulai'] !== '' ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) && $_GET['tgl_selesai'] !== '' ? $_GET['tgl_selesai'] : date('Y-m-d');

// Query Rekap Menggunakan Database View v_rekap_transaksi
$query_rekap = "SELECT * FROM v_rekap_transaksi 
                WHERE DATE(waktu_masuk) BETWEEN ? AND ? 
                ORDER BY waktu_masuk DESC";
$stmt = $pdo->prepare($query_rekap);
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$rekap_list = $stmt->fetchAll();

// Statistik Ringkasan Laporan
$total_pendapatan  = 0;
$transaksi_selesai = 0;
$transaksi_aktif   = 0;

foreach ($rekap_list as $r) {
    if ($r['status'] === 'keluar') {
        $total_pendapatan += $r['biaya_total'];
        $transaksi_selesai++;
    } else {
        $transaksi_aktif++;
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Owner - Rekap Transaksi | ParkirPro Luxury</title>
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

        /* Sidebar Styling Khusus Role Owner */
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
            backdrop-filter: blur(12px);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .luxury-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .stat-card {
            padding: 24px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .text-gradient {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Top Header Navbar */
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

        /* Table Adaptive Header Styling */
        .table {
            --bs-table-bg: transparent;
        }

        .custom-table-header {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--bs-body-color) !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="light"] .custom-table-header {
            background-color: rgba(0, 0, 0, 0.03) !important;
            border-bottom: 2px solid rgba(0, 0, 0, 0.08);
        }

        .plat-badge {
            font-family: monospace;
            font-size: 0.9rem;
            letter-spacing: 1px;
            background: #000;
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #fff;
            display: inline-block;
        }

        [data-bs-theme="light"] .plat-badge {
            background: #212529;
            color: #fff;
            border-color: #000;
        }

        /* Styling Khusus Cetak/Print */
        @media print {
            .sidebar, .top-navbar, .filter-section, .btn-print, #themeToggle {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .luxury-card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation (Khusus Role Owner) -->
    <nav class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <i class="bi bi-shield-shaded text-primary fs-4"></i>
            <span>Parkir<span class="text-gradient">Pro</span></span>
        </div>
        <ul class="sidebar-nav mt-3">
            <li>
                <a href="index.php" class="nav-link active">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i> Rekap Transaksi
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
                <h5 class="mb-0 fw-bold">Panel Owner - Executive Report</h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Ganti Tema">
                    <i id="themeIcon" class="bi bi-sun-fill"></i>
                </button>

                <!-- User Profile Badge -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-inherit" data-bs-toggle="dropdown">
                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                            <?= substr($_SESSION['user']['nama_lengkap'], 0, 1) ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <h6 class="mb-0 fw-bold small"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?></h6>
                            <span class="text-muted" style="font-size: 11px;">Owner / Pimpinan</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item text-danger py-2" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Summary Stat Cards -->
        <div class="row g-3 mb-4" data-aos="fade-up" data-aos-duration="700">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="luxury-card stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Total Pendapatan</span>
                            <h3 class="fw-extrabold mb-0 mt-1 text-success">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-4">
                <div class="luxury-card stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Transaksi Selesai (Keluar)</span>
                            <h3 class="fw-extrabold mb-0 mt-1 text-primary"><?= number_format($transaksi_selesai) ?></h3>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-12 col-xl-4">
                <div class="luxury-card stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Kendaraan Sedang Parkir</span>
                            <h3 class="fw-extrabold mb-0 mt-1 text-warning"><?= number_format($transaksi_aktif) ?></h3>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-car-front"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Table Card -->
        <div class="luxury-card p-4 p-md-4" data-aos="fade-up" data-aos-duration="900">
            
            <!-- Filter Tanggal Section (Luxury Modern Design) -->
<div class="filter-section mb-4 pb-3 border-bottom border-secondary border-opacity-10">
    <form method="GET" class="row g-3 align-items-end">
                    <!-- Input Dari Tanggal -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px; font-size: 11px;">
                            <i class="bi bi-calendar-event me-1 text-primary"></i> Dari Tanggal
                        </label>
                        <input type="date" name="tgl_mulai" class="form-control form-control-luxury" value="<?= $tgl_mulai ?>">
                    </div>

                    <!-- Input Sampai Tanggal -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px; font-size: 11px;">
                            <i class="bi bi-calendar-check me-1 text-primary"></i> Sampai Tanggal
                        </label>
                        <input type="date" name="tgl_selesai" class="form-control form-control-luxury" value="<?= $tgl_selesai ?>">
                    </div>

                    <!-- Group Action Buttons -->
                    <div class="col-12 col-lg-6 d-flex align-items-center justify-content-between justify-content-lg-end gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Tombol Filter Gradient -->
                            <button type="submit" class="btn btn-luxury-filter rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                                <i class="bi bi-funnel-fill"></i> Filter
                            </button>

                            <!-- Tombol Reset Filter -->
                            <a href="index.php" class="btn btn-luxury-reset rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>

                        <!-- Tombol Cetak Laporan -->
                        <button type="button" onclick="window.print();" class="btn btn-luxury-print rounded-pill px-4 py-2 fw-semibold btn-print d-inline-flex align-items-center gap-2 shadow-sm ms-auto ms-lg-0">
                            <i class="bi bi-printer-fill"></i> Cetak Laporan
                        </button>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-table text-primary me-2"></i>Data Rekapitulasi Transaksi</h5>
                <span class="badge bg-secondary bg-opacity-25 text-body px-3 py-2 rounded-pill">Periode: <?= date('d M Y', strtotime($tgl_mulai)) ?> - <?= date('d M Y', strtotime($tgl_selesai)) ?></span>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID Parkir</th>
                            <th class="py-3">Plat Nomor</th>
                            <th class="py-3">Jenis</th>
                            <th class="py-3">Area Parkir</th>
                            <th class="py-3">Waktu Masuk</th>
                            <th class="py-3">Waktu Keluar</th>
                            <th class="py-3">Durasi</th>
                            <th class="py-3">Biaya Total</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rekap_list)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">Tidak ada transaksi ditemukan pada rentang tanggal ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rekap_list as $r): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $r['id_parkir'] ?></td>
                                <td><span class="plat-badge"><?= htmlspecialchars($r['plat_nomor'] ?: '-') ?></span></td>
                                <td>
                                    <?php if ($r['jenis_kendaraan'] === 'motor'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-bicycle"></i> Motor</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-car-front"></i> <?= ucfirst(htmlspecialchars($r['jenis_kendaraan'] ?: 'Mobil')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="fw-semibold"><i class="bi bi-geo-alt text-primary me-1"></i><?= htmlspecialchars($r['nama_area'] ?: '-') ?></small></td>
                                <td><small class="text-muted"><?= date('d/m/y H:i', strtotime($r['waktu_masuk'])) ?></small></td>
                                <td><small class="text-muted"><?= $r['waktu_keluar'] ? date('d/m/y H:i', strtotime($r['waktu_keluar'])) : '-' ?></small></td>
                                <td><span class="fw-bold"><?= $r['durasi_jam'] ?: 0 ?></span> Jam</td>
                                <td class="fw-bold text-success">Rp <?= number_format($r['biaya_total'] ?: 0, 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($r['status'] === 'keluar'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">Selesai (Keluar)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill">Masuk (Parkir)</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($r['nama_petugas'] ?: 'System') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

        function syncThemeIcon(theme) {
            if (theme === 'light') {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
            } else {
                themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
            }
        }

        const currentSavedTheme = localStorage.getItem('parkirpro_theme') || 'dark';
        syncThemeIcon(currentSavedTheme);

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('parkirpro_theme', newTheme);
            syncThemeIcon(newTheme);
        });
    </script>
</body>
</html>