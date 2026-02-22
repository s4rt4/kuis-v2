<?php
// config.php
// sesuaikan user/pass/dbname jika perlu
$host = "localhost";
$dbname = "quiz_db";
$user = "root";
$pass = "";

try {
    $db = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Kalau error, tampilkan agar mudah debugging lokal
    die("Koneksi DB gagal: " . $e->getMessage());
}
