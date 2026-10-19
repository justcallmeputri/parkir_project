<?php
// index.php - Halaman Login Utama & Monitoring Publik ParkirPro Luxury
require_once 'config.php';

$error = '';

// Proses Login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && hash('sha256', $password) === $user['password']) {
            $_SESSION['user'] = [
                'id_user'      => $user['id_user'],
                'username'     => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role'         => $user['role']
            ];

            // Log Aktivitas Login
            $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
            $log->execute([$user['id_user'], "Pengguna berhasil login ke sistem"]);

            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
                exit;
            } elseif ($user['role'] === 'petugas') {
                header("Location: petugas/index.php");
                exit;
            } elseif ($user['role'] === 'owner') {
                header("Location: owner/index.php");
                exit;
            }
        } else {
            $error = "Username atau password salah!";
        }
    }
}

// Fetch Data Area Parkir untuk Fitur Publik Tanpa Login
$area_publik = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area ASC")->fetchAll();
$total_kapasitas = 0;
$total_terisi = 0;
foreach ($area_publik as $ap) {
    $total_kapasitas += $ap['kapasitas'];
    $total_terisi += $ap['terisi'];
}
$sisa_total_slot = $total_kapasitas - $total_terisi;
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Ketersediaan Parkir | ParkirPro Luxury</title>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #0b0f19;
            color: #f8fafc;
            overflow-x: hidden;
            transition: background 0.4s ease, color 0.4s ease;
        }

        [data-bs-theme="light"] body {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Split Layout Container */
        .split-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

        /* Hero Side (Kiri) */
        .hero-side {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.05) 100%), #111827;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        [data-bs-theme="light"] .hero-side {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.02) 100%), #ffffff;
            border-right: 1px solid rgba(0, 0, 0, 0.06);
        }

        /* Form Side (Kanan) */
        .form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: rgba(17, 24, 39, 0.5);
        }

        [data-bs-theme="light"] .form-side {
            background: rgba(255, 255, 255, 0.6);
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        .text-gradient {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-control {
            border-radius: 14px;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="light"] .form-control {
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control:focus {
            background: transparent;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
        }

        .stat-mini-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            padding: 20px;
            transition: transform 0.3s ease;
        }

        [data-bs-theme="light"] .stat-mini-card {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .stat-mini-card:hover {
            transform: translateY(-4px);
        }

        /* ULTIMATE LUXURY MODAL REDESIGN */
        .modal-content-luxury {
            background: rgba(20, 24, 33, 0.95);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 28px !important;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
        }

        [data-bs-theme="light"] .modal-content-luxury {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.1);
        }

        .area-grid-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-bs-theme="light"] .area-grid-card {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .area-grid-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.15);
        }

        .modal-header-custom {
            padding: 28px 32px 20px 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        [data-bs-theme="light"] .modal-header-custom {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .modal-body-custom {
            padding: 28px 32px;
            max-height: 65vh;
            overflow-y: auto;
        }

        .modal-footer-custom {
            padding: 20px 32px 28px 32px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        [data-bs-theme="light"] .modal-footer-custom {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

    <!-- Floating Theme Toggler -->
    <div class="position-fixed top-0 end-0 p-4 z-3">
        <button id="themeToggle" class="btn btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow" style="width: 44px; height: 44px;" title="Ganti Tema">
            <i id="themeIcon" class="bi bi-sun-fill fs-5"></i>
        </button>
    </div>

    <!-- Split Screen Layout -->
    <div class="container-fluid p-0 split-wrapper">
        
        <!-- KOLOM KIRI: Hero Branding & Cek Slot Publik -->
        <div class="col-12 col-lg-7 hero-side d-none d-lg-flex" data-aos="fade-right" data-aos-duration="800">
            <div>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm">
                        <i class="bi bi-shield-shaded fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-0">Parkir<span class="text-gradient">Pro</span> Enterprise</h4>
                </div>
                <h1 class="display-5 fw-extrabold mb-3">Sistem Parkir Modern, Cerdas & Terintegrasi</h1>
                <p class="text-muted lead mb-4" style="font-size: 1.05rem;">Kelola manajemen area, transaksi masuk/keluar, audit log, hingga laporan keuangan owner secara real-time dengan standar keamanan enterprise.</p>
            </div>

            <!-- Quick Live Slot Status Box -->
            <div>
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="stat-mini-card">
                            <span class="text-muted small fw-semibold">Total Kapasitas</span>
                            <h3 class="fw-bold mb-0 mt-1 text-primary"><?= $total_kapasitas ?></h3>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-mini-card">
                            <span class="text-muted small fw-semibold">Terpakai</span>
                            <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $total_terisi ?></h3>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-mini-card">
                            <span class="text-muted small fw-semibold">Sisa Slot</span>
                            <h3 class="fw-bold mb-0 mt-1 text-success"><?= $sisa_total_slot ?></h3>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary rounded-pill px-4 py-3 fw-bold shadow-lg w-100 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCekKetersediaan" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none;">
                    <i class="bi bi-p-square-fill fs-5"></i> Buka Monitoring Ketersediaan Area Parkir Publik
                </button>
            </div>
        </div>

        <!-- KOLOM KANAN: Form Login Eksklusif -->
        <div class="col-12 col-lg-5 form-side" data-aos="fade-left" data-aos-duration="800">
            <div class="login-box py-4">
                
                <!-- Mobile Header Brand -->
                <div class="d-lg-none text-center mb-4">
                    <div class="d-inline-flex p-3 bg-primary bg-opacity-10 text-primary rounded-4 mb-2">
                        <i class="bi bi-shield-shaded fs-1"></i>
                    </div>
                    <h3 class="fw-bold">Parkir<span class="text-gradient">Pro</span></h3>
                    <p class="text-muted small">Sistem Manajemen Parkir Luxury</p>
                </div>

                <div class="mb-4">
                    <h3 class="fw-extrabold mb-1">Selamat Datang</h3>
                    <p class="text-muted small">Silakan masukkan kredensial akun Anda untuk masuk ke sistem.</p>
                </div>

                <!-- Alert Error -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 py-2 small mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username Akun</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 rounded-start-4"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="username" class="form-control border-start-0 rounded-end-4" placeholder="Masukkan username..." required autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password Sandi</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 rounded-start-4"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 rounded-end-4" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary rounded-pill w-100 py-3 fw-bold shadow-sm mb-3" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none;">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Sistem
                    </button>
                </form>

                <!-- Tombol Cek Ketersediaan Khusus Mobile -->
                <div class="d-lg-none mt-3 pt-3 border-top border-secondary border-opacity-10">
                    <button type="button" class="btn btn-outline-primary rounded-pill w-100 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCekKetersediaan">
                        <i class="bi bi-p-square-fill me-1"></i> Cek Area & Ketersediaan Slot Parkir
                    </button>
                </div>

                <div class="text-center mt-5">
                    <small class="text-muted" style="font-size: 11px;">&copy; 2026 ParkirPro System. CREATED BY PUTRI.</small>
                </div>

            </div>
        </div>

    </div>

    <!-- MODAL CEK KETERSEDIAAN AREA PARKIR PUBLIK (REDESIGNED LUXURY UI/UX) -->
    <div class="modal fade" id="modalCekKetersediaan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-luxury shadow-lg">
                
                <!-- Header Modal -->
                <div class="modal-header modal-header-custom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary shadow-sm">
                            <i class="bi bi-p-square-fill fs-3"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-extrabold mb-1">Daftar Area & Daya Tampung</h4>
                            <p class="text-muted small mb-0">Pemantauan lokasi, kapasitas total, dan jumlah terisi secara real-time</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body Modal (Grid Cards UI/UX) -->
                <div class="modal-body modal-body-custom">
                    
                    <!-- Top Summary Banner inside Modal -->
                    <div class="p-3 rounded-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-10 d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                            <span class="small fw-semibold">Status Ketersediaan Keseluruhan Area</span>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">Total: <?= $total_kapasitas ?> Slot</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">Sedia: <?= $sisa_total_slot ?> Slot</span>
                        </div>
                    </div>

                    <!-- Grid Layout for Areas -->
                    <div class="row g-3">
                        <?php if (empty($area_publik)): ?>
                            <div class="col-12 text-center py-5">
                                <div class="text-muted">Belum ada data area parkir yang tersedia di sistem.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($area_publik as $ap): 
                                $persen = $ap['kapasitas'] > 0 ? round(($ap['terisi'] / $ap['kapasitas']) * 100) : 0;
                                $sisa_slot = $ap['kapasitas'] - $ap['terisi'];
                                $is_tersedia = $sisa_slot > 0;
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="area-grid-card h-100 d-flex flex-col justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <span class="text-muted" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">#ID AREA: <?= $ap['id_area'] ?></span>
                                                <h5 class="fw-bold mb-0 mt-1 text-inherit">
                                                    <i class="bi bi-geo-alt-fill text-primary me-1"></i><?= htmlspecialchars($ap['nama_area']) ?>
                                                </h5>
                                            </div>
                                            <span class="badge <?= $is_tersedia ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' ?> px-3 py-1 rounded-pill" style="font-size: 11px; font-weight: 600;">
                                                <?= $is_tersedia ? 'Tersedia' : 'Penuh' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-top border-secondary border-opacity-10">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small text-muted">Keterisian Slot</span>
                                            <span class="small fw-bold"><b><?= $ap['terisi'] ?></b> / <?= $ap['kapasitas'] ?> Slot (<b><?= $sisa_slot ?></b> Sisa)</span>
                                        </div>
                                        <div class="progress rounded-pill bg-secondary bg-opacity-25" style="height: 8px;">
                                            <div class="progress-bar rounded-pill <?= $persen >= 90 ? 'bg-danger' : ($persen >= 70 ? 'bg-warning' : 'bg-success') ?>" role="progressbar" style="width: <?= $persen ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Footer Modal -->
                <div class="modal-footer modal-footer-custom d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary rounded-pill px-5 py-2 fw-semibold" data-bs-dismiss="modal">Tutup</button>
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