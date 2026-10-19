<?php
// petugas/cetak_struk.php - Halaman Cetak Struk Parkir Pro Luxury
require_once '../config.php';
check_role(['petugas', 'admin']);

$id_parkir = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Query detail transaksi parkir berdasarkan ID Parkir JOIN ke tb_kendaraan, tb_tarif, tb_area_parkir, dan tb_user
$query = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, 
                 a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS nama_petugas 
          FROM tb_transaksi t 
          LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
          LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
          LEFT JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif 
          LEFT JOIN tb_user u ON t.id_user = u.id_user 
          WHERE t.id_parkir = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_parkir]);
$struk = $stmt->fetch();

if (!$struk) {
    echo "<script>alert('Data transaksi parkir tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Tiket Parkir #<?= $struk['id_parkir'] ?> | ParkirPro Luxury</title>
    <!-- Script pencegah FOUC (Flash of Unstyled Theme) saat refresh -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('parkirpro_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans & Courier Prime untuk cetakan nota -->
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.4s ease, color 0.4s ease;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Luxury Glass Card Wrapper */
        .luxury-card {
            background: rgba(33, 37, 41, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            max-width: 480px;
            width: 100%;
            padding: 28px;
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .luxury-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        }

        /* Tampilan Struk Kertas Kasir (Thermal Paper Layout) */
        .thermal-ticket {
            background: #ffffff !important;
            color: #111111 !important;
            font-family: 'Courier Prime', monospace;
            padding: 24px;
            border-radius: 12px;
            border: 1px dashed #aaa;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
        }

        .thermal-ticket::before, .thermal-ticket::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            height: 6px;
            background-size: 12px 6px;
        }

        .thermal-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .thermal-footer {
            text-align: center;
            border-top: 2px dashed #333;
            padding-top: 12px;
            margin-top: 14px;
            font-size: 0.8rem;
        }

        .plat-box {
            display: inline-block;
            background: #000;
            color: #fff;
            font-family: monospace;
            font-weight: bold;
            font-size: 1.25rem;
            letter-spacing: 2px;
            padding: 4px 14px;
            border-radius: 6px;
            margin: 6px 0;
        }

        .text-gradient {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Standard CSS Media Print untuk printer nota */
        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .luxury-card {
                background: none !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .thermal-ticket {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <div class="luxury-card" data-aos="zoom-in" data-aos-duration="600">
        
        <!-- Header Controls (No Print) -->
        <div class="no-print d-flex justify-content-between align-items-center mb-4">
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Theme Toggle -->
                <button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Ganti Tema">
                    <i id="themeIcon" class="bi bi-sun-fill"></i>
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Struk
                </button>
            </div>
        </div>

        <!-- Tampilan Kertas Struk Thermal -->
        <div class="thermal-ticket">
            
            <!-- Header Struk -->
            <div class="thermal-header">
                <h5 class="fw-bold mb-0 text-uppercase">PARKIRPRO LUXURY</h5>
                <small class="d-block text-muted" style="font-size: 0.75rem;">Sistem Manajemen Parkir Terpadu</small>
                <small class="d-block fw-bold mt-1">NO. TIKET: #<?= sprintf('%06d', $struk['id_parkir']) ?></small>
            </div>

            <!-- Detail Kendaraan & Parkir -->
            <div class="text-center mb-3">
                <div class="plat-box"><?= htmlspecialchars($struk['plat_nomor']) ?></div>
                <div class="fw-bold text-uppercase" style="font-size: 0.9rem;"><?= htmlspecialchars($struk['jenis_kendaraan']) ?> - <?= htmlspecialchars($struk['warna'] ?: 'Standard') ?></div>
            </div>

            <div class="small">
                <div class="d-flex justify-content-between mb-1">
                    <span>Area Parkir:</span>
                    <span class="fw-bold"><?= htmlspecialchars($struk['nama_area'] ?: 'Umum') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Petugas:</span>
                    <span class="fw-bold"><?= htmlspecialchars($struk['nama_petugas'] ?: 'System') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Waktu Masuk:</span>
                    <span class="fw-bold"><?= date('d/m/Y H:i:s', strtotime($struk['waktu_masuk'])) ?></span>
                </div>

                <?php if ($struk['status'] === 'keluar'): ?>
                <div class="d-flex justify-content-between mb-1">
                    <span>Waktu Keluar:</span>
                    <span class="fw-bold"><?= date('d/m/Y H:i:s', strtotime($struk['waktu_keluar'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Durasi Parkir:</span>
                    <span class="fw-bold"><?= $struk['durasi_jam'] ?> Jam</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Tarif per Jam:</span>
                    <span>Rp <?= number_format($struk['tarif_per_jam'], 0, ',', '.') ?></span>
                </div>
                <hr style="border-top: 1px dashed #333;" class="my-2">
                <div class="d-flex justify-content-between align-items-center fs-6 fw-bold">
                    <span>TOTAL BIAYA:</span>
                    <span>Rp <?= number_format($struk['biaya_total'], 0, ',', '.') ?></span>
                </div>
                <?php else: ?>
                <hr style="border-top: 1px dashed #333;" class="my-2">
                <div class="text-center fw-bold">
                    <span>STATUS: SEDANG PARKIR</span>
                    <small class="d-block text-muted fw-normal">Tarif: Rp <?= number_format($struk['tarif_per_jam'], 0, ',', '.') ?> / Jam</small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer Struk -->
            <div class="thermal-footer">
                <p class="mb-1 fw-bold">TERIMA KASIH ATAS KUNJUNGAN ANDA</p>
                <small class="d-block text-muted">Simpan struk ini sebagai bukti pembayaran resmi.</small>
            </div>
        </div>

        <!-- Tombol Aksi Tambahan (No Print) -->
        <div class="no-print mt-4 text-center">
            <button onclick="window.print()" class="btn btn-outline-light rounded-pill px-4 me-2">
                <i class="bi bi-printer me-1"></i> Print / Cetak
            </button>
            <a href="index.php" class="btn btn-secondary rounded-pill px-4">
                Selesai
            </a>
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

        // Dark / Light Mode Toggler Logic
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