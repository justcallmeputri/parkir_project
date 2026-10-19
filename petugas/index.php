<?php
// petugas/index.php - Panel Petugas Transaksi Parkir Luxury ParkirPro
require_once '../config.php';
check_role(['petugas', 'admin']);

$msg = '';
$msg_type = '';
$id_user = $_SESSION['user']['id_user'];

// 1. Handling Transaksi Parkir Masuk
if (isset($_POST['transaksi_masuk'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $warna           = trim($_POST['warna']);
    $pemilik         = trim($_POST['pemilik']);
    $id_area         = intval($_POST['id_area']);
    $waktu_masuk     = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // A. Ambil Data Tarif Berdasarkan Jenis Kendaraan
        $stmt_tarif = $pdo->prepare("SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = ? LIMIT 1");
        $stmt_tarif->execute([$jenis_kendaraan]);
        $id_tarif = $stmt_tarif->fetchColumn();

        if (!$id_tarif) {
            throw new Exception("Tarif untuk jenis kendaraan '$jenis_kendaraan' belum diatur oleh Admin!");
        }

        // B. Cek atau Tambahkan Kendaraan ke tb_kendaraan
        $stmt_cek_k = $pdo->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ?");
        $stmt_cek_k->execute([$plat_nomor]);
        $id_kendaraan = $stmt_cek_k->fetchColumn();

        if (!$id_kendaraan) {
            $stmt_ins_k = $pdo->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins_k->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_user]);
            $id_kendaraan = $pdo->lastInsertId();
        } else {
            // Update info kendaraan jika sudah pernah terdaftar
            $stmt_upd_k = $pdo->prepare("UPDATE tb_kendaraan SET jenis_kendaraan = ?, warna = ?, pemilik = ? WHERE id_kendaraan = ?");
            $stmt_upd_k->execute([$jenis_kendaraan, $warna, $pemilik, $id_kendaraan]);
        }

        // C. Cek Kapasitas Area Parkir
        $stmt_area = $pdo->prepare("SELECT kapasitas, terisi, nama_area FROM tb_area_parkir WHERE id_area = ? FOR UPDATE");
        $stmt_area->execute([$id_area]);
        $area = $stmt_area->fetch();

        if ($area['terisi'] >= $area['kapasitas']) {
            throw new Exception("Area Parkir " . $area['nama_area'] . " sudah penuh!");
        }

        // D. Insert ke tb_transaksi
        $stmt_tx = $pdo->prepare("INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) VALUES (?, ?, ?, 'masuk', ?, ?)");
        $stmt_tx->execute([$id_kendaraan, $waktu_masuk, $id_tarif, $id_user, $id_area]);
        $id_parkir_baru = $pdo->lastInsertId();

        // E. Update Keterisian Area Parkir (+1)
        $stmt_upd_area = $pdo->prepare("UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = ?");
        $stmt_upd_area->execute([$id_area]);

        // F. Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user, "Mencatat parkir MASUK: $plat_nomor di " . $area['nama_area']]);

        $pdo->commit();

        $msg = "Kendaraan <b>$plat_nomor</b> berhasil masuk parkir! Kode Tiket: <b>#$id_parkir_baru</b>";
        $msg_type = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Gagal: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 2. Handling Transaksi Parkir Keluar
if (isset($_POST['transaksi_keluar'])) {
    $id_parkir    = intval($_POST['id_parkir']);
    $waktu_keluar = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // Fetch detail transaksi parkir
        $stmt_tx = $pdo->prepare("SELECT t.*, tf.tarif_per_jam, k.plat_nomor 
                                 FROM tb_transaksi t 
                                 JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif 
                                 JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                                 WHERE t.id_parkir = ? AND t.status = 'masuk' FOR UPDATE");
        $stmt_tx->execute([$id_parkir]);
        $tx = $stmt_tx->fetch();

        if (!$tx) {
            throw new Exception("Transaksi parkir tidak ditemukan atau sudah diproses keluar!");
        }

        // Hitung Durasi (Jam) dan Biaya Total
        $t_masuk  = new DateTime($tx['waktu_masuk']);
        $t_keluar = new DateTime($waktu_keluar);
        $diff     = $t_masuk->diff($t_keluar);

        $durasi_jam = $diff->h + ($diff->days * 24);
        if ($diff->i > 0 || $durasi_jam == 0) {
            $durasi_jam++; // Pembulatan ke atas per jam
        }

        $biaya_total = $durasi_jam * $tx['tarif_per_jam'];

        // Update tb_transaksi
        $stmt_upd_tx = $pdo->prepare("UPDATE tb_transaksi SET waktu_keluar = ?, durasi_jam = ?, biaya_total = ?, status = 'keluar' WHERE id_parkir = ?");
        $stmt_upd_tx->execute([$waktu_keluar, $durasi_jam, $biaya_total, $id_parkir]);

        // Update Keterisian Area Parkir (-1)
        $stmt_upd_area = $pdo->prepare("UPDATE tb_area_parkir SET terisi = GREATEST(0, terisi - 1) WHERE id_area = ?");
        $stmt_upd_area->execute([$tx['id_area']]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user, "Mencatat parkir KELUAR: " . $tx['plat_nomor'] . " (Total: Rp " . number_format($biaya_total) . ")"]);

        $pdo->commit();

        $msg = "Transaksi KELUAR berhasil! Plat: <b>" . $tx['plat_nomor'] . "</b> | Biaya: <b>Rp " . number_format($biaya_total, 0, ',', '.') . "</b>";
        $msg_type = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Gagal: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch Data Parkir Aktif (Status 'masuk')
$query_aktif = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, a.nama_area, tf.tarif_per_jam 
                FROM tb_transaksi t 
                LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
                LEFT JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif 
                WHERE t.status = 'masuk' 
                ORDER BY t.waktu_masuk DESC";
$parkir_aktif = $pdo->query($query_aktif)->fetchAll();

// Fetch Data Transaksi Hari Ini (Selesai/Keluar)
$query_hari_ini = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
                   FROM tb_transaksi t 
                   LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                   LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
                   WHERE DATE(t.waktu_masuk) = CURDATE() 
                   ORDER BY t.id_parkir DESC";
$transaksi_hari_ini = $pdo->query($query_hari_ini)->fetchAll();

// Fetch Option Dropdown Area & Tarif
$area_options  = $pdo->query("SELECT * FROM tb_area_parkir WHERE terisi < kapasitas ORDER BY nama_area ASC")->fetchAll();
$tarif_options = $pdo->query("SELECT * FROM tb_tarif ORDER BY jenis_kendaraan ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Petugas Transaksi | ParkirPro Luxury</title>
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

        .plat-badge {
            font-family: monospace;
            font-size: 0.95rem;
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

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
        }

        /* Struk/Ticket Printable Area */
        @media print {
            body * {
                visibility: hidden;
            }
            #printableStrukArea, #printableStrukArea * {
                visibility: visible;
            }
            #printableStrukArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                font-family: monospace;
                color: #000 !important;
                background: #fff !important;
            }
            .no-print {
                display: none !important;
            }
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
                    <i class="bi bi-speedometer2"></i> Transaksi Parkir
                </a>
            </li>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <hr class="border-secondary opacity-25 my-3">
            <li>
                <a href="../admin/index.php" class="nav-link text-warning">
                    <i class="bi bi-shield-lock-fill"></i> Panel Admin
                </a>
            </li>
            <?php endif; ?>
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
                <h5 class="mb-0 fw-bold">Panel Transaksi Petugas</h5>
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
                            <span class="text-muted" style="font-size: 11px;">Petugas Parkir</span>
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

        <!-- Action Button Section -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" data-aos="fade-up" data-aos-duration="700">
            <div>
                <h4 class="fw-bold mb-1">Entry & Exit Parkir</h4>
                <p class="text-muted small mb-0">Kelola proses masuk, pemrosesan keluar, dan pencetakan struk parkir</p>
            </div>
            <button class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalParkirMasuk">
                <i class="bi bi-plus-lg me-1"></i> Input Parkir Masuk
            </button>
        </div>

        <!-- Table Parkir Aktif (Sedang Parkir) -->
        <div class="luxury-card p-4 p-md-4 mb-4" data-aos="fade-up" data-aos-duration="800">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-car-front-fill text-warning me-2"></i>Kendaraan Sedang Parkir (<?= count($parkir_aktif) ?>)</h5>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-1 rounded-pill">Status: Masuk</span>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID Parkir</th>
                            <th class="py-3">Plat Nomor</th>
                            <th class="py-3">Jenis Kendaraan</th>
                            <th class="py-3">Area Parkir</th>
                            <th class="py-3">Waktu Masuk</th>
                            <th class="py-3 text-center">Aksi / Cetak Struk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parkir_aktif)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Saat ini belum ada kendaraan yang sedang parkir.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parkir_aktif as $pa): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $pa['id_parkir'] ?></td>
                                <td><span class="plat-badge"><?= htmlspecialchars($pa['plat_nomor']) ?></span></td>
                                <td>
                                    <?php if ($pa['jenis_kendaraan'] === 'motor'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-bicycle"></i> Motor</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-car-front"></i> <?= ucfirst(htmlspecialchars($pa['jenis_kendaraan'] ?: 'Mobil')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="fw-semibold"><i class="bi bi-geo-alt text-primary me-1"></i><?= htmlspecialchars($pa['nama_area'] ?: '-') ?></small></td>
                                <td><small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i:s', strtotime($pa['waktu_masuk'])) ?></small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success rounded-pill px-3 me-1" data-bs-toggle="modal" data-bs-target="#modalParkirKeluar<?= $pa['id_parkir'] ?>">
                                        <i class="bi bi-box-arrow-right me-1"></i> Diproses Keluar
                                    </button>
                                    <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick='bukaStrukModal(<?= json_encode($pa) ?>)'>
                                        <i class="bi bi-printer me-1"></i> Struk Tiket
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Table Transaksi Hari Ini -->
        <div class="luxury-card p-4 p-md-4" data-aos="fade-up" data-aos-duration="900">
            <h5 class="fw-bold mb-3"><i class="bi bi-journal-check text-primary me-2"></i>Riwayat Transaksi Hari Ini</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID</th>
                            <th class="py-3">Plat Nomor</th>
                            <th class="py-3">Jenis</th>
                            <th class="py-3">Area</th>
                            <th class="py-3">Waktu Masuk</th>
                            <th class="py-3">Waktu Keluar</th>
                            <th class="py-3">Biaya Total</th>
                            <th class="py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksi_hari_ini)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat transaksi parkir untuk hari ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksi_hari_ini as $thi): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $thi['id_parkir'] ?></td>
                                <td><span class="plat-badge"><?= htmlspecialchars($thi['plat_nomor']) ?></span></td>
                                <td><small class="fw-medium"><?= ucfirst(htmlspecialchars($thi['jenis_kendaraan'])) ?></small></td>
                                <td><small class="text-muted"><?= htmlspecialchars($thi['nama_area'] ?: '-') ?></small></td>
                                <td><small class="text-muted"><?= date('H:i:s', strtotime($thi['waktu_masuk'])) ?></small></td>
                                <td><small class="text-muted"><?= $thi['waktu_keluar'] ? date('H:i:s', strtotime($thi['waktu_keluar'])) : '-' ?></small></td>
                                <td class="fw-bold text-success">Rp <?= number_format($thi['biaya_total'] ?: 0, 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($thi['status'] === 'keluar'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">Keluar</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill">Sedang Parkir</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal Input Parkir Masuk -->
    <div class="modal fade" id="modalParkirMasuk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-right text-primary me-2"></i>Input Parkir Masuk Baru</h5>
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
                            <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Pemilik / Pengendara <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="text" name="pemilik" class="form-control" placeholder="Contoh: Pengunjung Umumu">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="transaksi_masuk" class="btn btn-primary rounded-pill px-4">Proses Masuk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals Transaksi Parkir Keluar -->
    <?php foreach ($parkir_aktif as $pa): ?>
    <div class="modal fade" id="modalParkirKeluar<?= $pa['id_parkir'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-right text-success me-2"></i>Konfirmasi Parkir Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <input type="hidden" name="id_parkir" value="<?= $pa['id_parkir'] ?>">
                        
                        <div class="text-center mb-3">
                            <span class="plat-badge mb-2"><?= htmlspecialchars($pa['plat_nomor']) ?></span>
                            <p class="text-muted small mb-0">Waktu Masuk: <?= date('d M Y, H:i:s', strtotime($pa['waktu_masuk'])) ?></p>
                        </div>

                        <div class="alert alert-secondary border-0 rounded-3 mb-0">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Jenis Kendaraan:</span>
                                <span class="fw-bold"><?= ucfirst(htmlspecialchars($pa['jenis_kendaraan'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Tarif Per Jam:</span>
                                <span class="fw-bold text-success">Rp <?= number_format($pa['tarif_per_jam'] ?: 0, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Area Parkir:</span>
                                <span class="fw-bold"><?= htmlspecialchars($pa['nama_area'] ?: '-') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="transaksi_keluar" class="btn btn-success rounded-pill px-4">Proses Keluar & Hitung Biaya</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modal Cetak Struk Tiket Parkir -->
    <div class="modal fade" id="modalStruk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="bi bi-ticket-perforated text-primary me-2"></i>Struk Parkir Resmi</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div id="printableStrukArea" class="p-3 border rounded-3 text-center bg-white text-dark" style="font-family: monospace;">
                        <h6 class="fw-bold mb-1">PARKIRPRO SYSTEM</h6>
                        <small class="d-block text-muted mb-2">Tiket Parkir Resmi</small>
                        <hr class="my-2" style="border-top: 1px dashed #000;">
                        <div class="text-start small">
                            <div><b>ID TIKET:</b> #<span id="strukId"></span></div>
                            <div><b>PLAT:</b> <span id="strukPlat"></span></div>
                            <div><b>JENIS:</b> <span id="strukJenis"></span></div>
                            <div><b>AREA:</b> <span id="strukArea"></span></div>
                            <div><b>MASUK:</b> <span id="strukWaktu"></span></div>
                        </div>
                        <hr class="my-2" style="border-top: 1px dashed #000;">
                        <small class="d-block text-center text-muted" style="font-size: 10px;">Simpan tiket ini untuk bukti pembayaran saat keluar.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

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

        // Function Buka & Isi Dynamic Modal Struk Tiket Parkir
        function bukaStrukModal(data) {
            document.getElementById('strukId').innerText = data.id_parkir;
            document.getElementById('strukPlat').innerText = data.plat_nomor;
            document.getElementById('strukJenis').innerText = data.jenis_kendaraan.toUpperCase();
            document.getElementById('strukArea').innerText = data.nama_area || '-';
            document.getElementById('strukWaktu').innerText = data.waktu_masuk;

            const modalStruk = new bootstrap.Modal(document.getElementById('modalStruk'));
            modalStruk.show();
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