<?php
include '../user/config.php';

// Fungsi untuk mencatat aktivitas ke dalam log
include 'function_log.php';


// Proses logout ketika tombol logout ditekan
if (isset($_POST['logout'])) {
    // Hapus semua data sesi
    session_unset();
    // Hancurkan sesi
    session_destroy();
    // Catat aktivitas logout ke dalam log

    // Redirect ke halaman login.php
    header("Location: ../login.php");
    exit();
}

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    // Mendapatkan peran pengguna dari sesi
    $username = $_SESSION['username'];
    $role_sql = "SELECT role FROM tb_user WHERE username = '$username'";
    $role_result = $conn->query($role_sql);

    if ($role_result->num_rows == 1) {
        $role_row = $role_result->fetch_assoc();
        $role = $role_row["role"];

        // Redirect user based on role
        if ($role === 'kurir') {
            header("Location: /kurir.php");
            exit();
        } elseif ($role === 'pengguna') {
            header("Location: ../berhasil_login.php");
            exit();
        }
    }
}

// Fungsi untuk menghapus barang
function deleteItem($pdo, $itemId,$username) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id_produk = ?");
    $stmt->execute([$itemId]);
    // Catat aktivitas penghapusan barang ke dalam log
    logActivity($pdo,"Deleted item with ID: $itemId",$username);
}

// Proses form delete barang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item'])) {
    $itemId = $_POST['id_produk'];
    deleteItem($pdo, $itemId,$username);
    header("Location:  admin.php?action=daftar_barang");
    exit();
}



// Ambil data semua user
$users = getUsers($pdo);

// Fungsi untuk mengambil data semua user
function getUsers($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM tb_user");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk menghapus user
function deleteUser($pdo, $username) {
    $stmt = $pdo->prepare("DELETE FROM tb_user WHERE username = ?");
    $stmt->execute([$username]);
    // Catat aktivitas penghapusan pengguna ke dalam log
    logActivity($pdo,"Deleted user: $username",$username);
}

// Proses form delete user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $username = $_POST['username'];
    deleteUser($pdo, $username);
    header("Location:  admin.php?action=daftar_user");
    exit();
}

// Fungsi untuk menambahkan saldo
function addBalance($pdo, $username, $amount) {
    // Ambil saldo user
    $stmt = $pdo->prepare("SELECT saldo FROM tb_user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentBalance = $user['saldo'];

    // Tambahkan saldo baru
    $newBalance = $currentBalance + $amount;

    // Update saldo user
    $stmt = $pdo->prepare("UPDATE tb_user SET saldo = ? WHERE username = ?");
    $stmt->execute([$newBalance, $username]);
    // Catat aktivitas penambahan saldo ke dalam log
    logActivity($pdo,"Added balance ($amount) to user: $username",$username);
}

// Proses form tambah saldo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_balance'])) {
    $username = $_POST['username'];
    $amount = $_POST['amount'];
    addBalance($pdo, $username, $amount);
    header("Location: admin.php");
    exit();
}

// Proses form tambah user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['new_fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['new_phone']);
    $email = mysqli_real_escape_string($conn, $_POST['new_email']);
    $username = mysqli_real_escape_string($conn, $_POST['new_username']);
    $password = $_POST['new_password'];
    $role = $_POST['new_role'];

    // Query untuk memeriksa apakah email, nomor telepon, dan username sudah terdaftar sebelumnya
    $check_email_query = "SELECT * FROM tb_user WHERE email = '$email'";
    $check_phone_query = "SELECT * FROM tb_user WHERE phone = '$phone'";
    $check_username_query = "SELECT * FROM tb_user WHERE username = '$username'";

    $email_result = mysqli_query($conn, $check_email_query);
    $phone_result = mysqli_query($conn, $check_phone_query);
    $username_result = mysqli_query($conn, $check_username_query);

    if (mysqli_num_rows($email_result) > 0 || mysqli_num_rows($phone_result) > 0 || mysqli_num_rows($username_result) > 0) {
        echo "<script>alert('Email, nomor telepon, atau username sudah terdaftar. Silakan gunakan yang lain!')</script>";
    } else {
        // Query untuk menambahkan data pengguna baru ke database
        $sql = "INSERT INTO tb_user (fullname, email, phone, username, password, role) VALUES ('$fullname', '$email', '$phone', '$username', '$password', '$role')";

        // Jalankan query
        if (mysqli_query($conn, $sql)) {
            // Jika pendaftaran berhasil, arahkan pengguna ke halaman berhasil_login.php
            header("Location: admin.php?action=daftar_user");
            // Catat aktivitas penambahan pengguna ke dalam log
            logActivity($pdo,"Added new user: $username",$username);
            exit();
        } else {
            // Jika terjadi kesalahan, tampilkan pesan error
            echo "<script>alert('Registrasi gagal. Silakan coba lagi!')</script>";
        }
    }
}


// Fungsi untuk mengambil data semua barang
function getItems($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM products");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="font-sans bg-[#FFF6F6]">

<div class="flex">
<!-- Sidebar -->
<div class="flex flex-col justify-between bg-white border-2 text-black w-1/5 min-h-screen max-h-full py-6 font-serif">
        <div>
            <h1 class="text-[34px] font-serif text-bold px-2">MS STORE</h1>
            <a href="?action=daftar_user" class="block py-2 px-4 text-black hover:bg-gray-300">Daftar User</a>
            <a href="?action=daftar_barang" class="block py-2 px-4 text-black hover:bg-gray-300">Daftar Barang</a>
            <a href="?action=tambah_user" class="block py-2 px-4 text-black hover:bg-gray-300">Tambah User</a>
            <a href="?action=daftar_status" class="block py-2 px-4 text-black hover:bg-gray-300">Daftar Status</a>
            <a href="?action=logs" class="block py-2 px-4 text-black hover:bg-gray-300">Logs</a>
            <form method="post" action="" class="mt-[50px]">
                <button type="submit" name="logout" class="block py-2 px-4 w-10/12 ml-2 rounded-lg text-black bg-[#FF8787] hover:bg-red-700">Logout</button>
            </form>
        </div>
        <!-- Logout Button -->

    </div>


    <!-- Content -->
    <div class="w-4/5 p-8">
        <!-- Content goes here -->
        <h2 class="text-2xl font-bold mb-4">Welcome to Admin Panel</h2>
        <?php
        // Handle different actions based on the selected menu item
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            switch ($action) {
                case 'daftar_user':
                    include 'daftar_user.php'; // You can create daftar_user.php for listing and managing users
                    break;
                case 'daftar_barang':
                    include 'daftar_barang.php'; // You can create daftar_barang.php for listing products
                    break;
                case 'tambah_user':
                    include 'tambah_user.php'; // You can create tambah_user.php for adding new users
                    break;
                case 'tambah_saldo':
                    include 'tambah_saldo.php'; // You can create tambah_saldo.php for adding balance to users
                    break;
                case 'edit_user':
                    include 'edit_user.php'; // You can create tambah_saldo.php for adding balance to users
                    break;
                case 'edit_barang':
                    include 'edit_barang.php'; // You can create tambah_saldo.php for adding balance to users
                    break;
                case 'daftar_status':
                    include 'daftar_status.php'; // Buat daftar_status.php untuk menampilkan daftar status pesanan
                    break;
                case 'logs':
                    include 'logs.php'; // Buat logs.php untuk menampilkan log aktivitas
                    break;
                    
                default:
                    // Default content if no action is specified
                    echo "Select a menu item from the sidebar.";
            }
        } else {
            // Default content if no action is specified
            echo "Select a menu item from the sidebar.";
        }
        ?>
    </div>
</div>

</body>
</html>
