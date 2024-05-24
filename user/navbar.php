<?php

// Sisipkan file koneksi ke database
include "config.php";

$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$role = '';
$profile_image = '';

// Jika pengguna sudah login, ambil informasi pengguna
if ($username !== '') {
    // Ambil informasi pengguna dari database
    $stmt = $pdo->prepare("SELECT role, profile_image, saldo FROM tb_user WHERE username = ?");
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $role = $row['role'];
        $profile_image = $row['profile_image']; // Simpan lokasi file foto profil ke dalam variabel
        $saldo = $row['saldo']; // Simpan saldo ke dalam variabel
    }
}

// Proses logout ketika tombol logout ditekan
if (isset($_POST['logout'])) {
    // Hapus semua data sesi
    session_unset();
    // Hancurkan sesi
    session_destroy();
    // Redirect ke halaman login.php
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MS STORE</title>
    <link rel="icon" href="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-serif">
    <!-- Navbar -->
    <nav class="navbar shadow-lg bg-white w-full ">
        <div class="container mx-auto flex items-center justify-between py-4 px-6 ">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="berhasil_login.php" id="logo" class="hidden sm:flex items-center mr-4">
                    <img src="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" alt="MS STORE Logo" class="w-16 h-16 ">
                </a>
            </div>
            <!-- Search bar -->
            <div class="flex-grow md:w-1/3 lg:w-2/5 xl:w-1/3 mx-4">
    <!-- Search form -->
    <form action="<?php echo isset($_SESSION['username']) ? 'search.php' : '../login.php'; ?>" method="GET" class="w-full">
        <input name="search" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500"
            type="search" placeholder="Temukan Barang Anda" aria-label="Search">
        <?php if (!isset($_SESSION['username'])): ?>
            <!-- Tambahkan pesan atau tindakan tambahan untuk pengguna yang belum login -->
        <?php endif; ?>
    </form>
</div>

            <!-- Icons -->
            <div class="flex items-center">
                <?php if ($username !== ''): ?>
                    <!-- Cart icon -->
                    <a href="cart.php" class="mr-4">
                        <img src="../assets/simple-line-icons_basket.png" alt="Shopping Cart" class="w-6 h-6">
                    </a>
                    <!-- User info with dropdown -->
                    <div class="relative">
                        <button id="user-avatar" class="flex items-center focus:outline-none">
                            <?php if ($profile_image && isset($profile_image)): ?>
                                <img src="<?= $profile_image ?>" alt="User Avatar" class="w-8 h-8 rounded-full">
                            <?php else: ?>
                                <img src="../assets/gg_profile.png" alt="Default Avatar" class="w-8 h-8 rounded-full">
                            <?php endif; ?>
                            <span class="ml-2 hidden md:inline"><?= $username ?></span>
                        </button>
                        <div id="dropdown-menu" class="absolute w-[200px] right-0 mt-2 bg-white border border-gray-200 rounded-md shadow-lg hidden">
                            <!-- Dropdown menu items -->
                            <!-- Your dropdown menu items go here -->
                            <?php if ($role === "admin"): ?>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="profile.php">Profile</a>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="admin.php">Admin</a>
                        <?php elseif ($role === "pengguna"): ?>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="profile.php">Profile</a>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                            href="toko.php">Toko Saya</a>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white" href="orderan_masuk.php">Orderan Masuk</a>
                            <!-- Menampilkan saldo di dropdown -->
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="status.php">Status Pesanan</a>
                                <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white" href="https://wa.me/6283849542876" target="_blank">Hubungi Admin</a>
                            <p class="block px-4 py-2 text-sm text-gray-700">Saldo: Rp <?php echo number_format($saldo, 2, ',', '.'); ?></p>
                            <!-- Menambahkan menu "Status Pesanan" -->

                        <?php elseif ($role === "kurir"): ?>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="profile.php">Profil Kurir</a>
                        <?php endif; ?>
                        <form action="" method="post">
                            <button type="submit" name="logout"
                                class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-blue-500 hover:text-white focus:outline-none">Logout</button>
                        </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login button -->
                    <div class=" w-auto border border-[#FF3D00] text-black p-2 rounded-md hover:bg-[#FF3D00] hover:text-white ">
                    <a href="../login.php" class="  ">Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <!-- Your remaining HTML content goes here -->
    <script>
        // JavaScript to toggle the visibility of the user dropdown menu
        document.getElementById('user-avatar').addEventListener('click', function() {
            var dropdownMenu = document.getElementById('dropdown-menu');
            dropdownMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>


