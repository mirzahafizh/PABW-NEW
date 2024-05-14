<?php

// Informasi koneksi ke database MySQL
$host = "localhost"; // Host database (biasanya "localhost")
$username = "root"; // Nama pengguna database
$password = ""; // Kata sandi database (kosongkan jika tidak ada)
$database = "db_ecommerce"; // Nama database yang ingin Anda hubungkan

// Membuat koneksi ke database menggunakan mysqli
$conn = new mysqli($host, $username, $password, $database);

// Membuat koneksi ke database menggunakan PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Gagal terhubung ke database: " . $e->getMessage());
}

// Memeriksa koneksi menggunakan mysqli
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>
