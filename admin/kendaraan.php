<?php
// admin/kendaraan.php - Management Data Kendaraan (CRUD) Luxury ParkirPro
require_once '../config.php';
check_role(['admin']);

$msg = '';
$msg_type = '';

// 1. Handling Tambah Kendaraan Baru
if (isset($_POST['tambah_kendaraan'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $warna           = trim($_POST['warna']);
    $pemilik         = trim($_POST['pemilik']);
    $id_user         = $_SESSION['user']['id_user'];

    try {
        $stmt = $pdo->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_user]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user, "Menambah kendaraan baru: $plat_nomor ($jenis_kendaraan)"]);

        $msg = "Kendaraan dengan Plat Nomor <b>$plat_nomor</b> berhasil ditambahkan!";
        $msg_type = "success";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = "Gagal: Plat Nomor <b>$plat_nomor</b> sudah terdaftar di sistem!";
        } else {
            $msg = "Error: " . $e->getMessage();
        }
        $msg_type = "danger";
    }
}

// 2. Handling Update Data Kendaraan
if (isset($_POST['edit_kendaraan'])) {
    $id_kendaraan    = $_POST['id_kendaraan'];
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $warna           = trim($_POST['warna']);
    $pemilik         = trim($_POST['pemilik']);

    try {
        $stmt = $pdo->prepare("UPDATE tb_kendaraan SET plat_nomor = ?, jenis_kendaraan = ?, warna = ?, pemilik = ? WHERE id_kendaraan = ?");
        $stmt->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_kendaraan]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$_SESSION['user']['id_user'], "Mengubah data kendaraan: $plat_nomor"]);

        $msg = "Data kendaraan <b>$plat_nomor</b> berhasil diperbarui!";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 3. Handling Hapus Kendaraan
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    try {
        $stmt_info = $pdo->prepare("SELECT plat_nomor FROM tb_kendaraan WHERE id_kendaraan = ?");
        $stmt_info->execute([$id_hapus]);
        $plat = $stmt_info->fetchColumn();

        if ($plat) {
            $stmt = $pdo->prepare("DELETE FROM tb_kendaraan WHERE id_kendaraan = ?");
            $stmt->execute([$id_hapus]);

            // Log Aktivitas
            $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
            $log->execute([$_SESSION['user']['id_user'], "Menghapus kendaraan: $plat"]);

            $msg = "Kendaraan <b>$plat</b> telah berhasil dihapus!";
            $msg_type = "warning";
        }
    } catch (PDOException $e) {
        $msg = "Gagal menghapus data kendaraan! Pastikan kendaraan ini tidak terikat pada riwayat transaksi parkir.";
        $msg_type = "danger";
    }
}

// Fetch Semua Data Kendaraan Sesuai Struktur Database (JOIN ke tb_user untuk info pendaftar)
$query_kendaraan = "SELECT k.*, u.nama_lengkap AS pendaftar 
                    FROM tb_kendaraan k 
                    LEFT JOIN tb_user u ON k.id_user = u.id_user 
                    ORDER BY k.id_kendaraan DESC";
$kendaraan_list = $pdo->query($query_kendaraan)->fetchAll();

// Fetch Data Tarif Parkir untuk Pilihan Jenis Kendaraan
$tarif_list = $pdo->query("SELECT DISTINCT jenis_kendaraan FROM tb_tarif ORDER BY jenis_kendaraan ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Kendaraan | ParkirPro Luxury</title>
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

        .plat-badge {
            font-family: monospace;
            font-size: 0.95rem;
            letter-spacing: 1px;
            background: #000;
            color: #fff;
            padding: 4px 12px;
            border-radius: 6px;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            display: inline-block;
        }

        [data-bs-theme="light"] .plat-badge {
            background: #212529;
            color: #fff;
            border-color: #000;
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
                <a href="kendaraan.php" class="nav-link active">
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
                <h5 class="mb-0 fw-bold">Manajemen Data Kendaraan</h5>
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
                    <h5 class="fw-bold mb-1"><i class="bi bi-car-front-fill text-primary me-2"></i>Daftar Kendaraan Terdaftar</h5>
                    <p class="text-muted small mb-0">Kelola nomor plat, jenis kendaraan, warna, serta identitas pemilik</p>
                </div>
                <button class="btn btn-primary px-4 py-2 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kendaraan Baru
                </button>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID</th>
                            <th class="py-3">Plat Nomor</th>
                            <th class="py-3">Jenis Kendaraan</th>
                            <th class="py-3">Warna</th>
                            <th class="py-3">Pemilik</th>
                            <th class="py-3">Petugas Input</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kendaraan_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada data kendaraan yang terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kendaraan_list as $k): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $k['id_kendaraan'] ?></td>
                                <td>
                                    <span class="plat-badge"><?= htmlspecialchars($k['plat_nomor']) ?></span>
                                </td>
                                <td>
                                    <?php if ($k['jenis_kendaraan'] === 'motor'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-bicycle me-1"></i> Motor</span>
                                    <?php elseif ($k['jenis_kendaraan'] === 'mobil'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-car-front me-1"></i> Mobil</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-truck me-1"></i> <?= ucfirst(htmlspecialchars($k['jenis_kendaraan'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-body border border-secondary border-opacity-25 px-2 py-1">
                                        <i class="bi bi-palette me-1"></i><?= htmlspecialchars($k['warna'] ?: 'Tidak Diisi') ?>
                                    </span>
                                </td>
                                <td class="fw-medium">
                                    <i class="bi bi-person text-secondary me-1"></i> <?= htmlspecialchars($k['pemilik'] ?: '-') ?>
                                </td>
                                <td>
                                    <small class="text-muted"><i class="bi bi-user-check me-1"></i><?= htmlspecialchars($k['pendaftar'] ?: 'System') ?></small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info rounded-pill px-3 me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditKendaraan<?= $k['id_kendaraan'] ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <a href="kendaraan.php?hapus=<?= $k['id_kendaraan'] ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data kendaraan <?= htmlspecialchars($k['plat_nomor']) ?>?')">
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

    <!-- Modal Tambah Kendaraan Baru -->
    <div class="modal fade" id="modalTambahKendaraan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Kendaraan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Plat Nomor Kendaraan</label>
                            <input type="text" name="plat_nomor" class="form-control text-uppercase" placeholder="Contoh: B 1234 ABC" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Kendaraan</label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="motor" selected>Motor</option>
                                <option value="mobil">Mobil</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Warna Kendaraan</label>
                            <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam / Merah" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Pemilik / Pengendara</label>
                            <input type="text" name="pemilik" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_kendaraan" class="btn btn-primary rounded-pill px-4">Simpan Kendaraan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals Edit Kendaraan (Ditempatkan Luar Tabel agar Rendering Rapi) -->
    <?php foreach ($kendaraan_list as $k): ?>
    <div class="modal fade" id="modalEditKendaraan<?= $k['id_kendaraan'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Kendaraan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <input type="hidden" name="id_kendaraan" value="<?= $k['id_kendaraan'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Plat Nomor Kendaraan</label>
                            <input type="text" name="plat_nomor" class="form-control text-uppercase" value="<?= htmlspecialchars($k['plat_nomor']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Kendaraan</label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="motor" <?= $k['jenis_kendaraan'] === 'motor' ? 'selected' : '' ?>>Motor</option>
                                <option value="mobil" <?= $k['jenis_kendaraan'] === 'mobil' ? 'selected' : '' ?>>Mobil</option>
                                <option value="lainnya" <?= $k['jenis_kendaraan'] === 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Warna Kendaraan</label>
                            <input type="text" name="warna" class="form-control" value="<?= htmlspecialchars($k['warna']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Pemilik / Pengendara</label>
                            <input type="text" name="pemilik" class="form-control" value="<?= htmlspecialchars($k['pemilik']) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_kendaraan" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
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