<?php
// Periksa apakah pengguna belum login, jika belum, arahkan ke halaman login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Sisipkan file koneksi ke database
include "config.php";

$username = $_SESSION['username'];
$role = '';

// Ambil email dan lokasi foto profil berdasarkan username
$stmt = $pdo->prepare("SELECT role, profile_image, saldo FROM tb_user WHERE username = ?");
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $role = $row['role'];
    $profile_image = $row['profile_image']; // Simpan lokasi file foto profil ke dalam variabel
    $saldo = $row['saldo']; // Simpan saldo ke dalam variabel
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
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS */
        /* Add your custom CSS here */
        .behind-navbar {
            padding-top: 64px; /* Adjust padding to the height of your navbar */
        }
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000; /* Set z-index to a higher value */
            background-color: white; /* Adjust background color as needed */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Optional: Add box-shadow for a subtle elevation effect */
        }
        .main-content {
            margin-top: 64px; /* Adjust margin-top to the height of your navbar */
        }
    </style>
</head>

<body class="bg-gray-100 font-serif">
    <!-- Navbar -->
    <nav class="navbar shadow-lg">
        <div class="container mx-auto flex items-center justify-between py-4 px-6 ">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <!-- Logo -->
                <a href="berhasil_login.php" id="logo" class="hidden sm:flex items-center mr-4">
                    <img src="../assets/gg_profile.png" alt="MS STORE Logo" class="w-8 h-8 mr-2">
                    <span class="text-xl font-bold">MS STORE</span>
                </a>
            </div>
            <!-- Search bar -->
            <div class="flex-grow md:w-1/3 lg:w-2/5 xl:w-1/3 mx-4">
                <form action="search.php" method="GET" class="w-full">
                    <input name="search" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500"
                        type="search" placeholder="Temukan Barang Anda" aria-label="Search">
                </form>
            </div>
            <!-- Icons -->
            <div class="flex items-center">
                <!-- Cart icon -->
                <a href="cart.php" class="mr-4">
                    <img src="../assets/simple-line-icons_basket.png" alt="Shopping Cart" class="w-6 h-6">
                </a>
                <!-- User info with dropdown -->
                <div class="relative">
                    <button id="user-avatar" class="flex items-center focus:outline-none">
                        <?php
                        // Check if the user has an avatar path
                        if (isset($profile_image) && $profile_image) {
                            echo '<img src="../' . $profile_image . '" alt="User Avatar" class="w-8 h-8 rounded-full">';
                        } else {
                            // If no avatar path is available, you can use a default image
                            echo '<img src="../assets/gg_profile.png" alt="Default Avatar" class="w-8 h-8 rounded-full">';
                        }
                        ?>
                        <span class="ml-2 hidden md:inline"><?php echo $_SESSION['username']; ?></span>
                    </button>
                    <div id="dropdown-menu" class="absolute w-[200px] right-0 mt-2 bg-white border border-gray-200 rounded-md shadow-lg hidden">
                        <?php if ($role === "admin"): ?>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="profile.php">Profile</a>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="admin.php">Admin</a>
                        <?php elseif ($role === "pengguna"): ?>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="toko.php">Toko Saya</a>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="profile.php">Profile</a>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white" href="orderan_masuk.php">Orderan Masuk</a>
                            <!-- Menampilkan saldo di dropdown -->
                            <p class="block px-4 py-2 text-sm text-gray-700">Saldo: Rp <?php echo number_format($saldo, 2, ',', '.'); ?></p>
                            <!-- Menambahkan menu "Status Pesanan" -->
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white"
                                href="status.php">Status Pesanan</a>
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
            </div>
        </div>
    </nav>

    <script>
        // JavaScript to toggle the visibility of the user dropdown menu
        document.getElementById('user-avatar').addEventListener('click', function() {
            var dropdownMenu = document.getElementById('dropdown-menu');
            dropdownMenu.classList.toggle('hidden');
        });
    </script>
</body>

</html>
