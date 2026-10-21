<?php
// admin/masuk.php - Form Input Kendaraan Masuk Parkir (Admin/Petugas Link)
require_once '../config.php';
check_role(['admin', 'petugas']);

$msg = '';
$msg_type = '';
$id_user = $_SESSION['user']['id_user'];

// Handling Form Submit Parkir Masuk
if (isset($_POST['proses_masuk'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $warna           = trim($_POST['warna']);
    $pemilik         = trim($_POST['pemilik']);
    $id_area         = intval($_POST['id_area']);
    $waktu_masuk     = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // 1. Ambil Data Tarif Berdasarkan Jenis Kendaraan
        $stmt_tarif = $pdo->prepare("SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = ? LIMIT 1");
        $stmt_tarif->execute([$jenis_kendaraan]);
        $id_tarif = $stmt_tarif->fetchColumn();

        if (!$id_tarif) {
            throw new Exception("Tarif untuk jenis kendaraan '$jenis_kendaraan' belum dikonfigurasi di master tarif!");
        }

        // 2. Cek apakah Kendaraan Sudah Terdaftar di tb_kendaraan
        $stmt_cek_k = $pdo->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ?");
        $stmt_cek_k->execute([$plat_nomor]);
        $id_kendaraan = $stmt_cek_k->fetchColumn();

        if (!$id_kendaraan) {
            $stmt_ins_k = $pdo->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins_k->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_user]);
            $id_kendaraan = $pdo->lastInsertId();
        } else {
            // Update info terkini jika kendaraan sudah pernah terdaftar
            $stmt_upd_k = $pdo->prepare("UPDATE tb_kendaraan SET jenis_kendaraan = ?, warna = ?, pemilik = ? WHERE id_kendaraan = ?");
            $stmt_upd_k->execute([$jenis_kendaraan, $warna, $pemilik, $id_kendaraan]);
        }

        // 3. Cek Kapasitas Area Parkir
        $stmt_area = $pdo->prepare("SELECT kapasitas, terisi, nama_area FROM tb_area_parkir WHERE id_area = ? FOR UPDATE");
        $stmt_area->execute([$id_area]);
        $area = $stmt_area->fetch();

        if (!$area) {
            throw new Exception("Area parkir tidak ditemukan!");
        }

        if ($area['terisi'] >= $area['kapasitas']) {
            throw new Exception("Area Parkir <b>" . htmlspecialchars($area['nama_area']) . "</b> sudah penuh!");
        }

        // 4. Input Transaksi ke tb_transaksi (Status: 'masuk')
        $stmt_tx = $pdo->prepare("INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) VALUES (?, ?, ?, 'masuk', ?, ?)");
        $stmt_tx->execute([$id_kendaraan, $waktu_masuk, $id_tarif, $id_user, $id_area]);
        $id_parkir_baru = $pdo->lastInsertId();

        // 5. Update Keterisian Slot Area Parkir (+1)
        $stmt_upd_area = $pdo->prepare("UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = ?");
        $stmt_upd_area->execute([$id_area]);

        // 6. Log Aktivitas Sistem
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user, "Input parkir MASUK kendaraan: $plat_nomor ($jenis_kendaraan) di " . $area['nama_area']]);

        $pdo->commit();

        $msg = "Kendaraan <b>$plat_nomor</b> berhasil masuk parkir! Tiket ID: <b>#$id_parkir_baru</b>";
        $msg_type = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Gagal memproses parkir masuk: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch Dropdown Options Area & Tarif Parkir
$area_options  = $pdo->query("SELECT * FROM tb_area_parkir WHERE terisi < kapasitas ORDER BY nama_area ASC")->fetchAll();
$tarif_options = $pdo->query("SELECT * FROM tb_tarif ORDER BY jenis_kendaraan ASC")->fetchAll();

// Fetch 10 Transaksi Parkir Masuk Terbaru
$query_terbaru = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, a.nama_area 
                  FROM tb_transaksi t 
                  LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                  LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
                  WHERE t.status = 'masuk' 
                  ORDER BY t.id_parkir DESC LIMIT 10";
$parkir_masuk_terbaru = $pdo->query($query_terbaru)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kendaraan Masuk | ParkirPro Luxury</title>
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

        /* Form styling */
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .plat-input {
            font-family: monospace;
            letter-spacing: 2px;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
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

        .custom-table-header {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--bs-body-color) !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="light"] .custom-table-header {
            background-color: rgba(0, 0, 0, 0.03) !important;
            border-bottom: 2px solid rgba(0, 0, 0, 0.08);
        }

        /* Styling Tombol Aksi Tabel Luxury */
        .btn-luxury-action {
            background: rgba(99, 102, 241, 0.1) !important;
            border: 1px solid rgba(99, 102, 241, 0.3) !important;
            color: #818cf8 !important;
            transition: all 0.3s ease;
        }

        .btn-luxury-action:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.35) !important;
        }

        [data-bs-theme="light"] .btn-luxury-action {
            background: rgba(99, 102, 241, 0.1) !important;
            border: 1px solid rgba(99, 102, 241, 0.25) !important;
            color: #4f46e5 !important;
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
                <a href="masuk.php" class="nav-link active">
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
                <h5 class="mb-0 fw-bold">Input Kendaraan Masuk</h5>
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

        <div class="row g-4">
            
            <!-- Form Card: Input Masuk -->
            <div class="col-12 col-lg-5" data-aos="fade-right" data-aos-duration="800">
                <div class="luxury-card p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                            <i class="bi bi-box-arrow-in-right fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Form Parkir Masuk</h5>
                            <small class="text-muted">Tambahkan kendaraan (Mobil/Motor) yang baru tiba</small>
                        </div>
                    </div>

                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Plat Nomor Kendaraan</label>
                            <input type="text" name="plat_nomor" class="form-control plat-input text-uppercase" placeholder="B 1234 ABC" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Kendaraan</label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="mobil" selected>Mobil</option>
                                <option value="motor">Motor</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Area Parkir</label>
                            <select name="id_area" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Area Parkir Tersedia --</option>
                                <?php foreach ($area_options as $ao): ?>
                                    <option value="<?= $ao['id_area'] ?>">
                                        <?= htmlspecialchars($ao['nama_area']) ?> (Sisa <?= $ao['kapasitas'] - $ao['terisi'] ?> Slot)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Warna Kendaraan <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam / Hitam Metalik">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Nama Pemilik / Pengendara <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="text" name="pemilik" class="form-control" placeholder="Contoh: Budi Santoso">
                        </div>

                        <button type="submit" name="proses_masuk" class="btn btn-primary rounded-pill w-100 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Simpan & Proses Parkir Masuk
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Card: Kendaraan Baru Masuk Hari Ini -->
            <div class="col-12 col-lg-7" data-aos="fade-left" data-aos-duration="800">
                <div class="luxury-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-warning me-2"></i>Antrean Parkir Aktif Terbaru</h5>
                        <a href="../petugas/index.php" class="btn btn-sm btn-outline-info rounded-pill px-3">Lihat Semua di Petugas</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="custom-table-header">
                                    <th class="py-3 px-3">#ID</th>
                                    <th class="py-3">Plat Nomor</th>
                                    <th class="py-3">Jenis</th>
                                    <th class="py-3">Area</th>
                                    <th class="py-3">Masuk</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($parkir_masuk_terbaru)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada kendaraan parkir aktif baru.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($parkir_masuk_terbaru as $pm): ?>
                                    <tr>
                                        <td class="fw-bold px-3">#<?= $pm['id_parkir'] ?></td>
                                        <td><span class="plat-badge"><?= htmlspecialchars($pm['plat_nomor']) ?></span></td>
                                        <td>
                                            <?php if ($pm['jenis_kendaraan'] === 'mobil'): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-car-front"></i> Mobil</span>
                                            <?php else: ?>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-bicycle"></i> Motor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="fw-semibold"><i class="bi bi-geo-alt text-primary me-1"></i><?= htmlspecialchars($pm['nama_area'] ?: '-') ?></small></td>
                                        <td><small class="text-muted"><?= date('H:i:s', strtotime($pm['waktu_masuk'])) ?></small></td>
                                        <td class="text-center">
                                            <a href="../petugas/cetak_struk.php?id=<?= $pm['id_parkir'] ?>" class="btn btn-sm btn-luxury-action rounded-circle p-2 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;" title="Cetak Struk/Tiket" target="_blank">
                                                <i class="bi bi-printer-fill fs-6"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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