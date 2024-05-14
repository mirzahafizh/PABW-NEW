<?php
session_start();

// Periksa jika pengguna belum login, maka arahkan ke halaman login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Sisipkan file koneksi ke database
include "config.php";

// Ambil username pengguna dari sesi
$username = $_SESSION['username'];

// Direktori tempat file diunggah
$target_dir = "../uploads/";
$target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Periksa apakah file gambar atau bukan
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if($check !== false) {
        echo "File adalah gambar - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File bukan gambar.";
        $uploadOk = 0;
    }
}

// Periksa apakah file sudah ada
if (file_exists($target_file)) {
    echo "Maaf, file tersebut sudah ada.";
    $uploadOk = 0;
}

// Periksa ukuran file
if ($_FILES["profile_image"]["size"] > 500000) {
    echo "Maaf, ukuran file terlalu besar.";
    $uploadOk = 0;
}

// Izinkan hanya beberapa jenis file tertentu
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
    echo "Maaf, hanya file JPG, JPEG, PNG, & GIF yang diizinkan.";
    $uploadOk = 0;
}

// Cek apakah $uploadOk bernilai 0 karena terjadi kesalahan
if ($uploadOk == 0) {
    echo "Maaf, file Anda tidak diunggah.";
// Jika semuanya baik, coba unggah file
} else {
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        // Simpan lokasi file foto profil ke dalam database
        $stmt = $pdo->prepare("UPDATE tb_user SET profile_image = ? WHERE username = ?");
        $stmt->execute([$target_file, $username]);
        echo "File ". basename( $_FILES["profile_image"]["name"]). " telah berhasil diunggah.";
    } else {
        echo "Maaf, ada kesalahan saat mengunggah file.";
    }
}

// Redirect kembali ke halaman profil setelah mengunggah foto
header("Location: profile.php");
?>
