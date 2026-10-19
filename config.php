<?php
session_start();

$host = 'localhost';
$db   = 'db_parkir';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

function check_role($allowed_roles = []) {
    if (!isset($_SESSION['user'])) {
        header("Location: ../index.php");
        exit;
    }
    if (!in_array($_SESSION['user']['role'], $allowed_roles)) {
        die("Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini.");
    }
}
?>