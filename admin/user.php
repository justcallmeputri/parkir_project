<?php
// admin/user.php - Manajemen CRUD User Administrator ParkirPro Luxury
require_once '../config.php';
check_role(['admin']);

$msg = '';
$msg_type = '';
$id_user_login = $_SESSION['user']['id_user'];

// 1. Handling Tambah User Baru
if (isset($_POST['tambah_user'])) {
    $username     = trim($_POST['username']);
    $password     = trim($_POST['password']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role         = trim($_POST['role']);

    try {
        if (empty($username) || empty($password) || empty($nama_lengkap) || empty($role)) {
            throw new Exception("Semua kolom form wajib diisi!");
        }

        // Cek duplikasi username
        $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM tb_user WHERE username = ?");
        $stmt_cek->execute([$username]);
        if ($stmt_cek->fetchColumn() > 0) {
            throw new Exception("Username '$username' sudah digunakan oleh pengguna lain!");
        }

        $password_hash = hash('sha256', $password);

        $stmt_ins = $pdo->prepare("INSERT INTO tb_user (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$username, $password_hash, $nama_lengkap, $role]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user_login, "Menambahkan user baru: $username (Role: $role)"]);

        $msg = "User <b>$username</b> berhasil ditambahkan!";
        $msg_type = "success";
    } catch (Exception $e) {
        $msg = "Gagal menambah user: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 2. Handling Edit User
if (isset($_POST['edit_user'])) {
    $id_user      = intval($_POST['id_user']);
    $username     = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role         = trim($_POST['role']);
    $password     = trim($_POST['password']);

    try {
        if (empty($username) || empty($nama_lengkap) || empty($role)) {
            throw new Exception("Kolom utama wajib diisi!");
        }

        if (!empty($password)) {
            $password_hash = hash('sha256', $password);
            $stmt_upd = $pdo->prepare("UPDATE tb_user SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id_user = ?");
            $stmt_upd->execute([$username, $password_hash, $nama_lengkap, $role, $id_user]);
        } else {
            $stmt_upd = $pdo->prepare("UPDATE tb_user SET username = ?, nama_lengkap = ?, role = ? WHERE id_user = ?");
            $stmt_upd->execute([$username, $nama_lengkap, $role, $id_user]);
        }

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user_login, "Memperbarui data user ID #$id_user ($username)"]);

        $msg = "Data user <b>$username</b> berhasil diperbarui!";
        $msg_type = "success";
    } catch (Exception $e) {
        $msg = "Gagal memperbarui user: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 3. Handling Hapus User
if (isset($_POST['hapus_user'])) {
    $id_user = intval($_POST['id_user']);

    try {
        if ($id_user === $id_user_login) {
            throw new Exception("Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan!");
        }

        $stmt_get = $pdo->prepare("SELECT username FROM tb_user WHERE id_user = ?");
        $stmt_get->execute([$id_user]);
        $uname = $stmt_get->fetchColumn();

        $stmt_del = $pdo->prepare("DELETE FROM tb_user WHERE id_user = ?");
        $stmt_del->execute([$id_user]);

        // Log Aktivitas
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user_login, "Menghapus user: $uname"]);

        $msg = "User <b>$uname</b> berhasil dihapus dari sistem.";
        $msg_type = "success";
    } catch (Exception $e) {
        $msg = "Gagal menghapus user: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch Semua Data User
$users = $pdo->query("SELECT * FROM tb_user ORDER BY id_user DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD User | ParkirPro Luxury</title>
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
            border-radius: 12px;
            padding: 11px 15px;
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
                <a href="user.php" class="nav-link active">
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
                <h5 class="mb-0 fw-bold">Manajemen CRUD Pengguna (User)</h5>
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

        <!-- Content Card: Tabel User & Tombol Tambah -->
        <div class="luxury-card p-4 p-md-4" data-aos="fade-up" data-aos-duration="800">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Daftar Pengguna Sistem</h5>
                    <p class="text-muted small mb-0">Kelola akun Admin, Petugas, dan Owner beserta hak akses masing-masing</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah User Baru
                </button>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="custom-table-header">
                            <th class="py-3 px-3">#ID</th>
                            <th class="py-3">Nama Lengkap</th>
                            <th class="py-3">Username</th>
                            <th class="py-3">Role / Hak Akses</th>
                            <th class="py-3 text-center">Aksi Manajemen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data user terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?= $u['id_user'] ?></td>
                                <td><div class="fw-bold text-inherit"><?= htmlspecialchars($u['nama_lengkap']) ?></div></td>
                                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-shield-lock"></i> Admin</span>
                                    <?php elseif ($u['role'] === 'petugas'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-person-badge"></i> Petugas</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill"><i class="bi bi-briefcase"></i> Owner</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $u['id_user'] ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </button>
                                    <?php if ($u['id_user'] !== $id_user_login): ?>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalHapusUser<?= $u['id_user'] ?>">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
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

    <!-- Modal Tambah User -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Putri Indah" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Username Login</label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: putri_admin" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password Sandi</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Role / Hak Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="petugas" selected>Petugas</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_user" class="btn btn-primary rounded-pill px-4">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals Edit & Hapus User (Decoupled dari Loop Tabel) -->
    <?php foreach ($users as $u): ?>
    <!-- Modal Edit User -->
    <div class="modal fade" id="modalEditUser<?= $u['id_user'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Pengguna: <?= htmlspecialchars($u['username']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($u['nama_lengkap']) ?>" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Username Login</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password Baru <span class="text-muted fw-normal">(Kosongkan jika tidak diubah)</span></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Role / Hak Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="petugas" <?= $u['role'] === 'petugas' ? 'selected' : '' ?>>Petugas</option>
                                <option value="owner" <?= $u['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Hapus User -->
    <?php if ($u['id_user'] !== $id_user_login): ?>
    <div class="modal fade" id="modalHapusUser<?= $u['id_user'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-3">
                        <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                        <p class="mb-1">Apakah Anda yakin ingin menghapus pengguna berikut?</p>
                        <div class="p-3 bg-secondary bg-opacity-10 rounded-3 mt-2">
                            <div><b>Nama:</b> <?= htmlspecialchars($u['nama_lengkap']) ?></div>
                            <div><b>Username:</b> <?= htmlspecialchars($u['username']) ?></div>
                            <div><b>Role:</b> <?= ucfirst($u['role']) ?></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus_user" class="btn btn-danger rounded-pill px-4">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
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