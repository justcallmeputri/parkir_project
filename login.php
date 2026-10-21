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
$persen_total_terisi = $total_kapasitas > 0 ? round(($total_terisi / $total_kapasitas) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Ketersediaan Parkir | ParkirPro Luxury</title>
    <!-- Script pencegah FOUC (Flash of Unstyled Theme) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('parkirpro_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --primary-glow: rgba(99, 102, 241, 0.25);
            --card-glass-bg: rgba(255, 255, 255, 0.03);
            --card-glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #060913;
            color: #f8fafc;
            overflow-x: hidden;
            transition: background 0.4s ease, color 0.4s ease;
        }

        [data-bs-theme="light"] body {
            background: #f1f5f9;
            color: #0f172a;
        }

        .font-number {
            font-family: 'Space Grotesk', sans-serif;
        }

        /* BAGIAN KIRI: RASIO 5 (col-lg-5) */
        .hero-side-5 {
            background: radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 45%),
                        radial-gradient(circle at 100% 100%, rgba(217, 70, 239, 0.1) 0%, transparent 45%),
                        #0b0f1d;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            max-height: 100vh;
            overflow-y: auto;
        }

        [data-bs-theme="light"] .hero-side-5 {
            background: radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.08) 0%, transparent 45%),
                        #ffffff;
            border-right: 1px solid rgba(0, 0, 0, 0.06);
        }

        /* BAGIAN KANAN: RASIO 7 (col-lg-7) */
        .form-side-7 {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            background: rgba(11, 15, 29, 0.55);
            backdrop-filter: blur(25px);
        }

        [data-bs-theme="light"] .form-side-7 {
            background: rgba(255, 255, 255, 0.7);
        }

        .login-card-box {
            width: 100%;
            max-width: 480px; /* Diperbesar agar seimbang dengan kolom 7 */
        }

        .text-gradient-primary {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* STAT CARDS LUXURY */
        .stat-card-luxury {
            position: relative;
            background: var(--card-glass-bg);
            border: 1px solid var(--card-glass-border);
            border-radius: 20px;
            padding: 20px 18px;
            backdrop-filter: blur(16px);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        [data-bs-theme="light"] .stat-card-luxury {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        }

        .stat-card-luxury:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 14px 30px var(--primary-glow);
        }

        .stat-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        /* KREASI KUSTOM DAFTAR AREA & DAYA TAMPUNG */
        .area-grid-card {
            position: relative;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
            border: 1px solid var(--card-glass-border);
            border-radius: 22px;
            padding: 22px;
            backdrop-filter: blur(16px);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        [data-bs-theme="light"] .area-grid-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
        }

        .area-grid-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-gradient);
            opacity: 0.8;
        }

        .area-grid-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 16px 36px var(--primary-glow);
        }

        .area-badge-status {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding: 6px 14px;
            border-radius: 30px;
        }

        /* PERBESARAN INPUT & BUTTON LOGIN */
        .form-label {
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 16px;
            padding: 16px 20px;
            font-size: 1.05rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.09);
            color: inherit;
        }

        [data-bs-theme="light"] .form-control {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .form-control:focus {
            background: transparent;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.09);
            font-size: 1.2rem;
            padding-left: 20px;
            padding-right: 15px;
        }

        [data-bs-theme="light"] .input-group-text {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .btn-luxury-primary {
            background: var(--accent-gradient);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 16px;
            padding: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.3);
        }

        .btn-luxury-primary:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(99, 102, 241, 0.4);
            color: #fff;
        }

        /* LIVE BADGE PULSE */
        .live-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* SLEEK SCROLLBAR */
        .hero-side-5::-webkit-scrollbar {
            width: 5px;
        }
        .hero-side-5::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <!-- Floating Theme Toggler -->
    <div class="position-fixed top-0 end-0 p-4 z-3">
        <button id="themeToggle" class="btn btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-lg" style="width: 48px; height: 48px; backdrop-filter: blur(12px);" title="Ganti Tema">
            <i id="themeIcon" class="bi bi-sun-fill fs-5"></i>
        </button>
    </div>

    <!-- Split Screen Layout (Rasio 5:7) -->
    <div class="container-fluid p-0">
        <div class="row g-0 min-vh-100">
            
            <!-- KOLOM KIRI (RASIO 5): Monitoring Area Parkir & Stats -->
            <div class="col-12 col-lg-5 hero-side-5 d-none d-lg-flex" data-aos="fade-right" data-aos-duration="800">
                <div>
                    <!-- Brand Header -->
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm border border-primary border-opacity-10">
                                <i class="bi bi-shield-shaded fs-2"></i>
                            </div>
                            <div>
                                <h4 class="fw-extrabold mb-0 fs-3">Parkir<span class="text-gradient-primary">Pro</span></h4>
                                <span class="text-muted small fw-medium">Real-Time Parking Intelligent System</span>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2 small fw-bold">
                            <span class="live-dot"></span> LIVE
                        </span>
                    </div>

                    <!-- KREASI HIGHLIGHT STATS (DIPERBESAR) -->
                    <div class="row g-3 mb-4">
                        <!-- Total Kapasitas -->
                        <div class="col-4">
                            <div class="stat-card-luxury">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold" style="font-size: 11px;">Total Capacity</span>
                                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-grid-3x3-gap-fill"></i>
                                    </div>
                                </div>
                                <h2 class="fw-extrabold font-number mb-1 text-inherit" style="font-size: 2.2rem;"><?= number_format($total_kapasitas) ?></h2>
                                <span class="text-muted" style="font-size: 11px;"><i class="bi bi-layers me-1"></i>Slot Total</span>
                            </div>
                        </div>

                        <!-- Terpakai -->
                        <div class="col-4">
                            <div class="stat-card-luxury">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold" style="font-size: 11px;">Terpakai</span>
                                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-car-front-fill"></i>
                                    </div>
                                </div>
                                <h2 class="fw-extrabold font-number mb-1 text-warning" style="font-size: 2.2rem;"><?= number_format($total_terisi) ?></h2>
                                <span class="text-warning small fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-pie-chart-fill me-1"></i><?= $persen_total_terisi ?>% Terisi
                                </span>
                            </div>
                        </div>

                        <!-- Sisa Slot -->
                        <div class="col-4">
                            <div class="stat-card-luxury">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold" style="font-size: 11px;">Sisa Slot</span>
                                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                </div>
                                <h2 class="fw-extrabold font-number mb-1 text-success" style="font-size: 2.2rem;"><?= number_format($sisa_total_slot) ?></h2>
                                <span class="text-success small fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-shield-check me-1"></i>Tersedia
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Header Daftar Area (Diperbesar) -->
                    <div class="d-flex align-items-center justify-content-between mb-3 pt-2">
                        <div>
                            <h4 class="fw-extrabold mb-0 fs-4">Daftar Area & Daya Tampung</h4>
                            <p class="text-muted small mb-0 fs-6">Pemantauan ketersediaan slot lokasi parkir terkini</p>
                        </div>
                        <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-10 px-3 py-2 rounded-pill small fw-bold"><?= count($area_publik) ?> Area</span>
                    </div>

                    <!-- KREASI KUSTOM DAFTAR AREA (Vertical List untuk Rasio 5) -->
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
                            <div class="col-12">
                                <div class="area-grid-card">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                                                <i class="bi bi-geo-alt-fill fs-4"></i>
                                            </div>
                                            <div>
                                                <span class="text-muted font-number" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">ID AREA #<?= $ap['id_area'] ?></span>
                                                <h5 class="fw-bold mb-0 text-inherit fs-5"><?= htmlspecialchars($ap['nama_area']) ?></h5>
                                            </div>
                                        </div>
                                        <span class="area-badge-status <?= $is_tersedia ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' ?>">
                                            <?= $is_tersedia ? 'TERSEDIA' : 'PENUH' ?>
                                        </span>
                                    </div>

                                    <div class="pt-2 border-top border-secondary border-opacity-10">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small fw-semibold">Keterisian Slot</span>
                                            <span class="fw-bold font-number fs-6"><b><?= $ap['terisi'] ?></b> / <?= $ap['kapasitas'] ?> <span class="text-muted fw-normal fs-7">(<b><?= $sisa_slot ?></b> Sisa)</span></span>
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

                <!-- Footer Informasi Sisi Kiri -->
                <div class="pt-4 mt-auto">
                    <div class="p-3 rounded-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-10 d-flex align-items-center justify-content-between">
                        <span class="small text-muted"><i class="bi bi-info-circle-fill text-primary me-2"></i>Status slot terbarui otomatis</span>
                        <small class="text-muted fw-bold" style="font-size: 11px;">ParkirPro Enterprise v2.4</small>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN (RASIO 7): Form Login Eksklusif (Diperbesar) -->
            <div class="col-12 col-lg-7 form-side-7" data-aos="fade-left" data-aos-duration="800">
                <div class="login-card-box">
                    
                    <!-- Mobile Header Brand -->
                    <div class="d-lg-none text-center mb-4">
                        <div class="d-inline-flex p-3 bg-primary bg-opacity-10 text-primary rounded-4 mb-2 border border-primary border-opacity-10">
                            <i class="bi bi-shield-shaded fs-1"></i>
                        </div>
                        <h2 class="fw-extrabold">Parkir<span class="text-gradient-primary">Pro</span></h2>
                        <p class="text-muted small">Sistem Manajemen Parkir Enterprise</p>
                    </div>

                    <div class="mb-5 text-center text-lg-start">
                        <h1 class="display-6 fw-extrabold mb-2">Selamat Datang</h1>
                        <p class="text-muted fs-6">Silakan masukkan kredensial akun Anda untuk masuk ke sistem.</p>
                    </div>

                    <!-- Alert Error -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-4 py-3 small mb-4 shadow-sm border border-danger border-opacity-25" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i> <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form Login (Teks & Input Diperbesar) -->
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Username Akun</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 rounded-start-4"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" name="username" class="form-control border-start-0 rounded-end-4" placeholder="Masukkan username Anda..." required autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold">Password Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 rounded-start-4"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control border-start-0 rounded-end-4" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" name="login" class="btn btn-luxury-primary w-100 mb-3 shadow-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Sistem
                        </button>
                    </form>

                    <!-- Ringkasan Ketersediaan Slot Khusus Mobile -->
                    <div class="d-lg-none mt-4 pt-3 border-top border-secondary border-opacity-10">
                        <h6 class="fw-bold mb-3"><i class="bi bi-p-square-fill text-primary me-2"></i>Ketersediaan Area Parkir</h6>
                        <div class="row g-2">
                            <?php foreach ($area_publik as $ap): 
                                $sisa_slot = $ap['kapasitas'] - $ap['terisi'];
                            ?>
                                <div class="col-12 p-2 rounded-3 bg-secondary bg-opacity-10 d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold"><?= htmlspecialchars($ap['nama_area']) ?></span>
                                    <span class="badge <?= $sisa_slot > 0 ? 'bg-success' : 'bg-danger' ?> rounded-pill">Sisa: <?= $sisa_slot ?> Slot</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-center mt-5 pt-3">
                        <small class="text-muted fw-semibold" style="font-size: 12px; letter-spacing: 0.5px;">&copy; 2026 PARKIRPRO SYSTEM. CREATED BY PUTRI.</small>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        AOS.init({
            once: true,
            offset: 40
        });

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