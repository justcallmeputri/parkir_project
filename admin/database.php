<?php
// admin/database.php - Halaman Maintenance Database ParkirPro Luxury
require_once '../config.php';

// Pastikan hanya role admin yang bisa mengakses halaman ini
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$message = '';$error = '';

// 1. PROSES BACKUP DATABASE (.SQL)
if (isset($_POST['backup'])) {$host = "localhost";
    $username_db = "root";
    $password_db = "";
    $database_name = "db_parkir"; // Sesuaikan dengan nama database Anda

    $filename = "backup_" . $database_name . "_" . date("Y-m-d_H-i-s") . ".sql";
    
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-Disposition: attachment; filename=$filename");

    $conn_backup = new mysqli($host, $username_db,$password_db, $database_name);$tables = array();
    $result =$conn_backup->query("SHOW TABLES");
    while ($row =$result->fetch_row()) {
        $tables[] =$row[0];
    }

    $return = "-- ParkirPro Enterprise Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as$table) {
        $result =$conn_backup->query("SELECT * FROM `$table`");
        $num_fields =$result->field_count;
        $return .= "DROP TABLE IF EXISTS `$table`;";
        $row2 =$conn_backup->query("SHOW CREATE TABLE `$table`")->fetch_row();
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = $result->fetch_row()) {$return .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {$return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {$return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }

    // Catat Log Aktivitas Backup
    $log =$pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
    $log->execute([$_SESSION['user']['id_user'], "Admin melakukan backup database sistem"]);

    echo $return;
    exit;
}

// 2. PROSES RESTORE DATABASE (.SQL)
if (isset($_POST['restore'])) {
    if (isset($_FILES['file_sql']) && $_FILES['file_sql']['error'] == 0) {
        $file_name = $_FILES['file_sql']['name'];
        $file_tmp  = $_FILES['file_sql']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext === 'sql') {
            try {
                $sql_contents = file_get_contents($file_tmp);
                
                // Eksekusi langsung tanpa transaksi ketat agar tidak error jika ada command DDL/View
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Pisahkan kueri jika diperlukan atau eksekusi langsung
                $pdo->exec($sql_contents);
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

                // Catat Log Aktivitas
                $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
                $log->execute([$_SESSION['user']['id_user'], "Admin melakukan restore database dari file: $file_name"]);

                $message = "Database berhasil dipulihkan (restore) dari file SQL.";
            } catch (Exception $e) {
                // Pastikan foreign key checks menyala kembali jika terjadi error
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $error = "Gagal memulihkan database: " . $e->getMessage();
            }
        } else {
            $error = "Format file tidak valid! Harap unggah file cadangan berformat .sql";
        }
    } else {
        $error = "Silakan pilih file .sql terlebih dahulu!";
    }
}

// 3. PROSES RESET / KOSONGKAN DATABASE
if (isset($_POST['reset_db'])) {
    $password_konfirmasi =$_POST['password_konfirmasi'];

    // Verifikasi password admin yang sedang login
    $stmt =$pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
    $stmt->execute([$_SESSION['user']['id_user']]);
    $current_user =$stmt->fetch();

    if ($current_user && hash('sha256', $password_konfirmasi) === $current_user['password']) {
        try {
            $pdo->beginTransaction();

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            // Disesuaikan dengan nama tabel yang benar di database Anda
            $pdo->exec("DELETE FROM tb_log_aktivitas");
            $pdo->exec("DELETE FROM tb_transaksi");
            $pdo->exec("DELETE FROM tb_kendaraan");
            $pdo->exec("DELETE FROM tb_tarif");
            $pdo->exec("DELETE FROM tb_area_parkir");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // Catat log aktivitas reset
            $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
            $log->execute([$_SESSION['user']['id_user'], "Admin mereset/mengosongkan database sistem"]);

            $pdo->commit();
            $message = "Database berhasil direset dan dikosongkan secara keseluruhan.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Gagal mereset database: " . $e->getMessage();
        }
    } else {
        $error = "Password konfirmasi salah! Reset database dibatalkan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Database | ParkirPro Luxury</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('parkirpro_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --primary-glow: rgba(99, 102, 241, 0.2);
            --card-glass-bg: rgba(255, 255, 255, 0.03);
            --card-glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #060913;
            color: #f8fafc;
        }

        [data-bs-theme="light"] body {
            background: #f1f5f9;
            color: #0f172a;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #0b0f1d;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 24px;
            overflow-y: auto;
            z-index: 100;
        }

        [data-bs-theme="light"] .sidebar {
            background: #ffffff;
            border-right: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 30px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: inherit;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            transition: all 0.3s ease;
        }

        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background: rgba(99, 102, 241, 0.12);
            color: #818cf8;
        }

        .main-content {
            margin-left: 280px;
            padding: 40px;
        }

        .text-gradient {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .luxury-card {
            background: var(--card-glass-bg);
            border: 1px solid var(--card-glass-border);
            border-radius: 24px;
            padding: 28px;
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .luxury-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .luxury-card:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 15px 40px var(--primary-glow);
        }

        .btn-luxury-primary {
            background: var(--accent-gradient);
            border: none;
            color: #fff;
            font-weight: 700;
            border-radius: 14px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);
        }

        .btn-luxury-primary:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-luxury-warning {
            background: var(--warning-gradient);
            border: none;
            color: #fff;
            font-weight: 700;
            border-radius: 14px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.25);
        }

        .btn-luxury-warning:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-luxury-danger {
            background: var(--danger-gradient);
            border: none;
            color: #fff;
            font-weight: 700;
            border-radius: 14px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
        }

        .btn-luxury-danger:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            color: #fff;
        }

        .form-control {
            border-radius: 14px;
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.09);
            color: inherit;
        }

        [data-bs-theme="light"] .form-control {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.08);
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
                <a href="log.php" class="nav-link">
                    <i class="bi bi-journal-text"></i> Log Aktivitas
                </a>
            </li>

            <hr class="border-secondary opacity-25 my-3">
            <div class="px-3 mb-2 text-muted uppercase small fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Sistem & Pengaturan</div>

            <li>
                <a href="database.php" class="nav-link active">
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

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
            <div>
                <h2 class="fw-extrabold mb-1">Maintenance Database</h2>
                <p class="text-muted small mb-0">Kelola cadangan data (*backup*), pemulihan (*restore*), dan pembersihan database sistem.</p>
            </div>
            <button id="themeToggle" class="btn btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow" style="width: 44px; height: 44px;" title="Ganti Tema">
                <i id="themeIcon" class="bi bi-sun-fill fs-5"></i>
            </button>
        </div>

        <!-- Alert Notification -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 py-3 mb-4 shadow-sm border border-success border-opacity-25" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 py-3 mb-4 shadow-sm border border-danger border-opacity-25" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 3-Column Layout Grid (Backup, Restore, Reset) -->
        <div class="row g-4">
            
            <!-- CARD 1: BACKUP DATABASE -->
            <div class="col-12 col-xl-4">
                <div class="luxury-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4">
                                <i class="bi bi-cloud-arrow-down-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Backup Database</h5>
                                <span class="text-muted small">Unduh salinan data (*.sql*)</span>
                            </div>
                        </div>
                        <p class="text-muted small mb-4">
                            Lakukan pencadangan database secara berkala untuk mengamankan data transaksi, tarif, area, dan riwayat parkir.
                        </p>
                    </div>
                    <form method="POST">
                        <button type="submit" name="backup" class="btn btn-luxury-primary w-100">
                            <i class="bi bi-download me-2"></i> Unduh Backup (.SQL)
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD 2: RESTORE DATABASE (TUKAR KEMBALI DATA) -->
            <div class="col-12 col-xl-4">
                <div class="luxury-card h-100 d-flex flex-column justify-content-between border-warning border-opacity-25">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4">
                                <i class="bi bi-cloud-arrow-up-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-warning">Restore Database</h5>
                                <span class="text-muted small">Pulihkan dari file cadangan</span>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Unggah file `.sql` cadangan yang sebelumnya diunduh untuk memulihkan seluruh struktur dan data database sistem.
                        </p>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="file_sql" class="form-control form-control-sm" accept=".sql" required>
                        </div>
                        <button type="submit" name="restore" class="btn btn-luxury-warning w-100">
                            <i class="bi bi-upload me-2"></i> Pulihkan Database
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD 3: RESET / KOSONGKAN DATABASE -->
            <div class="col-12 col-xl-4">
                <div class="luxury-card h-100 d-flex flex-column justify-content-between border-danger border-opacity-25">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-4">
                                <i class="bi bi-exclamation-octagon-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-danger">Reset / Kosongkan</h5>
                                <span class="text-muted small">Bersihkan data master & transaksi</span>
                            </div>
                        </div>
                        <p class="text-muted small mb-4">
                            Tindakan ini akan mengosongkan seluruh tabel transaksi dan master data. Akun Administrator aman dan tidak akan terhapus.
                        </p>
                    </div>
                    <button type="button" class="btn btn-luxury-danger w-100" data-bs-toggle="modal" data-bs-target="#modalResetConfirm">
                        <i class="bi bi-trash3-fill me-2"></i> Reset Database
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL KONFIRMASI RESET DATABASE -->
    <div class="modal fade" id="modalResetConfirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content luxury-card shadow-lg border-danger border-opacity-50">
                <form method="POST">
                    <div class="modal-header border-bottom border-secondary border-opacity-10 pb-3">
                        <h5 class="modal-title fw-extrabold text-danger">
                            <i class="bi bi-shield-exclamation me-2"></i> Konfirmasi Reset Database
                        </h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <div class="alert alert-danger py-2 small mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <b>Peringatan Keras:</b> Tindakan ini bersifat permanen dan tidak dapat dibatalkan!
                        </div>
                        <p class="small text-muted mb-3">
                            Masukkan password akun Administrator Anda saat ini untuk mengonfirmasi pengosongan database:
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password Konfirmasi</label>
                            <input type="password" name="password_konfirmasi" class="form-control" placeholder="Masukkan password Anda..." required>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary border-opacity-10 pt-3">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="reset_db" class="btn btn-luxury-danger rounded-pill px-4">Ya, Kosongkan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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