<?php
// admin/area.php - Management Area Parkir (CRUD) Luxury ParkirPro
require_once '../config.php';
check_role(['admin']);

$msg = '';
$msg_type = '';

// 1. Handling Tambah Area Parkir
if (isset($_POST['tambah_area'])) {
    $nama_area = trim($_POST['nama_area']);
    $kapasitas = intval($_POST['kapasitas']);
    $terisi    = intval($_POST['terisi'] ?? 0);

    try {
        $stmt = $pdo->prepare("INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES (?, ?, ?)");
        $stmt->execute([$nama_area, $kapasitas, $terisi]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$_SESSION['user']['id_user'], "Menambah area parkir baru: $nama_area (Kapasitas: $kapasitas)"]);

        $msg = "Area parkir <b>$nama_area</b> berhasil ditambahkan!";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 2. Handling Update Data Area Parkir
if (isset($_POST['edit_area'])) {
    $id_area   = $_POST['id_area'];
    $nama_area = trim($_POST['nama_area']);
    $kapasitas = intval($_POST['kapasitas']);
    $terisi    = intval($_POST['terisi']);

    try {
        $stmt = $pdo->prepare("UPDATE tb_area_parkir SET nama_area = ?, kapasitas = ?, terisi = ? WHERE id_area = ?");
        $stmt->execute([$nama_area, $kapasitas, $terisi, $id_area]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$_SESSION['user']['id_user'], "Mengubah data area parkir ID #$id_area ($nama_area)"]);

        $msg = "Data area parkir <b>$nama_area</b> berhasil diperbarui!";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 3. Handling Hapus Area Parkir
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    try {
        $stmt_info = $pdo->prepare("SELECT nama_area FROM tb_area_parkir WHERE id_area = ?");
        $stmt_info->execute([$id_hapus]);
        $nama = $stmt_info->fetchColumn();

        if ($nama) {
            $stmt = $pdo->prepare("DELETE FROM tb_area_parkir WHERE id_area = ?");
            $stmt->execute([$id_hapus]);

            // Log Aktivitas
            $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
            $log->execute([$_SESSION['user']['id_user'], "Menghapus area parkir: $nama"]);

            $msg = "Area parkir <b>$nama</b> telah berhasil dihapus!";
            $msg_type = "warning";
        }
    } catch (PDOException $e) {
        $msg = "Gagal menghapus area parkir! Pastikan area ini tidak sedang digunakan pada transaksi parkir aktif.";
        $msg_type = "danger";
    }
}

// Fetch Semua Data Area Parkir
$area_list = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY id_area DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Area Parkir | ParkirPro Luxury</title>
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

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
        }

        .progress {
            height: 10px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="light"] .progress {
            background-color: rgba(0, 0, 0, 0.08);
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
                <a href="area.php" class="nav-link active">
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
                <h5 class="mb-0 fw-bold">Manajemen Area Parkir</h5>
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
                    <h5 class="fw-bold mb-1"><i class="bi bi-p-square-fill text-primary me-2"></i>Daftar Area & Daya Tampung</h5>
                    <p class="text-muted small mb-0">Kelola lokasi, kapasitas total, serta pemantauan jumlah terisi</p>
                </div>
                <button class="btn btn-primary px-4 py-2 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahArea">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Area Baru
                </button>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID Area</th>
                            <th class="py-3">Nama Area</th>
                            <th class="py-3">Kapasitas</th>
                            <th class="py-3">Terisi</th>
                            <th class="py-3" style="min-width: 180px;">Status Keterisian</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($area_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada area parkir yang ditambahkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($area_list as $a): 
                                $persen = $a['kapasitas'] > 0 ? round(($a['terisi'] / $a['kapasitas']) * 100) : 0;
                                $sisa   = $a['kapasitas'] - $a['terisi'];
                                
                                // Penentuan Warna Progress Bar & Badge
                                if ($persen >= 90) {
                                    $bg_bar = 'bg-danger';
                                    $badge_status = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Penuh</span>';
                                } elseif ($persen >= 70) {
                                    $bg_bar = 'bg-warning';
                                    $badge_status = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Hampir Penuh</span>';
                                } else {
                                    $bg_bar = 'bg-success';
                                    $badge_status = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Tersedia</span>';
                                }
                            ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $a['id_area'] ?></td>
                                <td class="fw-semibold">
                                    <i class="bi bi-geo-alt text-primary me-1"></i> <?= htmlspecialchars($a['nama_area']) ?>
                                </td>
                                <td><span class="fw-bold"><?= $a['kapasitas'] ?></span> Slot</td>
                                <td><span class="fw-bold"><?= $a['terisi'] ?></span> Terpakai</td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <small class="text-muted" style="font-size: 11px;"><?= $persen ?>% (Sisa <?= $sisa ?>)</small>
                                        <?= $badge_status ?>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar <?= $bg_bar ?>" role="progressbar" style="width: <?= $persen ?>%" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info rounded-pill px-3 me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditArea<?= $a['id_area'] ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <a href="area.php?hapus=<?= $a['id_area'] ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus area <?= htmlspecialchars($a['nama_area']) ?>?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal Tambah Area Baru -->
    <div class="modal fade" id="modalTambahArea" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Area Parkir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Area Parkir</label>
                            <input type="text" name="nama_area" class="form-control" placeholder="Contoh: Area A - Motor" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kapasitas Maksimal (Slot)</label>
                            <input type="number" name="kapasitas" class="form-control" placeholder="Contoh: 50" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jumlah Terisi Awal</label>
                            <input type="number" name="terisi" class="form-control" value="0" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_area" class="btn btn-primary rounded-pill px-4">Simpan Area</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals Edit Area (Ditempatkan Luar Tabel agar Rendering Modal Rapi & Pas) -->
    <?php foreach ($area_list as $a): ?>
    <div class="modal fade" id="modalEditArea<?= $a['id_area'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Area Parkir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <input type="hidden" name="id_area" value="<?= $a['id_area'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Area Parkir</label>
                            <input type="text" name="nama_area" class="form-control" value="<?= htmlspecialchars($a['nama_area']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kapasitas Maksimal (Slot)</label>
                            <input type="number" name="kapasitas" class="form-control" value="<?= $a['kapasitas'] ?>" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jumlah Terisi Saat Ini</label>
                            <input type="number" name="terisi" class="form-control" value="<?= $a['terisi'] ?>" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_area" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

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