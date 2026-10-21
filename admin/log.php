<?php
// admin/log.php - Audit Log Aktivitas Sistem Luxury ParkirPro
require_once '../config.php';
check_role(['admin']);

$msg = '';
$msg_type = '';

// Handling Clear/Kosongkan Log Aktivitas (Opsional untuk Admin)
if (isset($_POST['clear_log'])) {
    try {
        $pdo->exec("DELETE FROM tb_log_aktivitas");
        
        // Catat ulang log pembersihan oleh Admin yang bertugas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$_SESSION['user']['id_user'], "Membersihkan/mereset seluruh riwayat log aktivitas sistem"]);

        $msg = "Seluruh riwayat log aktivitas berhasil dibersihkan!";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Gagal membersihkan log: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Konfigurasi Paginasi (35 data per halaman)
$limit = 35;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Hitung Total Log untuk Paginasi
$total_stmt = $pdo->query("SELECT COUNT(*) FROM tb_log_aktivitas");
$total_log = $total_stmt->fetchColumn();
$total_pages = ceil($total_log / $limit);

// Fetch Log Aktivitas JOIN tb_user dengan LIMIT dan OFFSET
$query_log = "SELECT l.*, u.nama_lengkap, u.username, u.role 
              FROM tb_log_aktivitas l 
              LEFT JOIN tb_user u ON l.id_user = u.id_user 
              ORDER BY l.id_log DESC 
              LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query_log);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Sistem | ParkirPro Luxury</title>
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
            backdrop-filter: blur(12px);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .luxury-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
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

        .search-box {
            position: relative;
            max-width: 300px;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 50px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.5;
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
                <a href="index.php" class="nav-link">
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
                <a href="log.php" class="nav-link active">
                    <i class="bi bi-journal-text"></i> Log Aktivitas
                </a>
            </li>
            <!-- Divider & Kategori Sistem/Pengaturan -->
            <hr class="border-secondary opacity-25 my-3">
            <div class="px-3 mb-2 text-muted uppercase small fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Sistem & Pengaturan</div>

            <li>
                <a href="database.php" class="nav-link">
                    <i class="bi bi-database-gear text-warning"></i> Maintenance Database
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
                <h5 class="mb-0 fw-bold">Audit Log Aktivitas</h5>
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

        <!-- Alert Notification -->
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show rounded-4 mb-4" role="alert" data-aos="fade-down">
                <i class="bi bi-info-circle-fill me-2"></i> <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Content Card -->
        <div class="luxury-card p-4 p-md-4" data-aos="fade-up" data-aos-duration="800">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-journal-text text-primary me-2"></i>Riwayat Aktivitas Pengguna</h5>
                    <p class="text-muted small mb-0">Audit log riwayat transaksi, penambahan data, dan aktivitas sistem</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-md-auto">
                    <!-- Live Search Box -->
                    <div class="search-box flex-grow-1">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari log / nama user...">
                    </div>
                    
                    <!-- Clear Log Form -->
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh log aktivitas sistem? Action ini tidak bisa dibatalkan!');">
                        <button type="submit" name="clear_log" class="btn btn-outline-danger rounded-pill px-3">
                            <i class="bi bi-trash me-1"></i> Bersihkan Log
                        </button>
                    </form>
                </div>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="logTable">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID Log</th>
                            <th class="py-3">Pengguna (User)</th>
                            <th class="py-3">Role</th>
                            <th class="py-3">Rincian Aktivitas</th>
                            <th class="py-3">Waktu Kejadian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada catatan log aktivitas dalam sistem.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $l['id_log'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-secondary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center fw-semibold small" style="width: 32px; height: 32px;">
                                            <?= substr($l['nama_lengkap'] ?: 'S', 0, 1) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small"><?= htmlspecialchars($l['nama_lengkap'] ?: 'System/Guest') ?></div>
                                            <small class="text-muted" style="font-size: 11px;">@<?= htmlspecialchars($l['username'] ?: 'system') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($l['role'] === 'admin'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill">Admin</span>
                                    <?php elseif ($l['role'] === 'petugas'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill">Petugas</span>
                                    <?php elseif ($l['role'] === 'owner'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill">Owner</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-medium"><?= htmlspecialchars($l['aktivitas']) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i:s', strtotime($l['waktu_aktivitas'])) ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginasi Navigasi -->
            <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary border-opacity-10 flex-wrap gap-2">
                <div class="text-muted small">
                    Menampilkan halaman <b><?= $page ?></b> dari <b><?= $total_pages ?></b> (Total <b><?= $total_log ?></b> log)
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <!-- Tombol Previous -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link rounded-start-pill px-3" href="?page=<?= $page - 1 ?>">Sebelumnya</a>
                    </li>

                    <?php 
                    // Tampilkan nomor halaman dengan batasan agar rapi
                    $start_p = max(1, $page - 2);
                    $end_p = min($total_pages, $page + 2);
                    
                    if ($start_p > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                        if ($start_p > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }

                    for ($i = $start_p; $i <= $end_p; $i++): 
                    ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php 
                    endfor;

                    if ($end_p < $total_pages) {
                        if ($end_p < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                    }
                    ?>

                    <!-- Tombol Next -->
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link rounded-end-pill px-3" href="?page=<?= $page + 1 ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

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
            offset: 35
        });

        // Toggle Sidebar Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarMenu = document.getElementById('sidebarMenu');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebarMenu.classList.toggle('show');
            });
        }

        // Live Search Filter untuk Tabel Log (Client-side pencarian di halaman aktif)
        const searchInput = document.getElementById('searchInput');
        const logTable = document.getElementById('logTable').getElementsByTagName('tbody')[0];

        if (searchInput && logTable) {
            searchInput.addEventListener('keyup', function() {
                const filter = searchInput.value.toLowerCase();
                const rows = logTable.getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    const textContent = rows[i].textContent.toLowerCase();
                    if (textContent.includes(filter)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
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