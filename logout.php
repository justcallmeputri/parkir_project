<?php
// logout.php - File utama untuk proses Logout
require_once 'config.php';

// Cek apakah ada session user yang aktif
if (isset($_SESSION['user'])) {
    try {
        // Catat jejak aktivitas logout pengguna ke tb_log_aktivitas
        $id_user = $_SESSION['user']['id_user'];
        $log = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $log->execute([$id_user, 'Logout dari sistem']);
    } catch (Exception $e) {
        // Abaikan jika terjadi kesalahan log agar proses logout tidak terganggu
    }
}

// Unset semua variabel session
$_SESSION = array();

// Jika menggunakan cookie session, hapus cookie session browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan seluruh data session
session_destroy();

// Redirect/kembalikan pengguna ke halaman login utama (login.php)
header("Location: login.php");
exit;
?>