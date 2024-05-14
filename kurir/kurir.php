<?php
session_start();
include "../user/config.php";

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    // Get the user's role from the session
    $username = $_SESSION['username'];

    // Fungsi untuk mencatat aktivitas ke dalam log
    function logActivity($pdo, $activity, $username) {
        try {
            $logTime = date("Y-m-d H:i:s");
            $stmt = $pdo->prepare("INSERT INTO activity_log (timestamp, username, activity) VALUES (?, ?, ?)");
            $stmt->execute([$logTime, $username, $activity]);
        } catch (PDOException $e) {
            // Tangani kesalahan jika gagal menyimpan log ke dalam database
            die("Error logging activity: " . $e->getMessage());
        }
    }

    // Call the logActivity function here or wherever needed
    // logActivity($pdo, "Some activity", $username);
} else {
    // If the user is not logged in, you may want to handle this case
    // For example, redirect them to the login page
    header("Location: login.php");
    exit(); // Make sure to exit after redirection
}

// Pastikan pengguna telah login sebagai kurir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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
        if ($role === 'pengguna') {
            header("Location: berhasil_login.php");
            exit();
        } elseif ($role === 'admin') {
            header("Location: admin/admin.php?action=daftar_user");
            exit();
        }
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
function orderHasBeenTaken($orderId) {
    global $pdo;
    
    $checkQuery = $pdo->prepare("SELECT COUNT(*) AS count FROM order_status WHERE id_order = ? AND status = 'Sedang Diambil'");
    $checkQuery->execute([$orderId]);
    $result = $checkQuery->fetch(PDO::FETCH_ASSOC);

    return $result['count'] > 0;
}

// Jika tombol "Ambil Orderan" ditekan
if(isset($_POST['ambil_orderan'])) {
    $order_id = $_POST['order_id'];
    $username = $_SESSION['username'];

    // Pemeriksaan apakah ID pesanan yang akan dimasukkan sudah ada di tabel "orders"
    $checkOrderQuery = $pdo->prepare("SELECT COUNT(*) AS count FROM orders WHERE id_order = ?");
    $checkOrderQuery->execute([$order_id]);
    $orderExists = $checkOrderQuery->fetch(PDO::FETCH_ASSOC)['count'];

    if ($orderExists > 0) {
        // Tambahkan data ke tabel order_status
        $insertQuery = $pdo->prepare("INSERT INTO order_status (id_order, nama_kurir, status, status_date) VALUES (?, ?, 'Sedang Diambil', NOW())");
        $insertQuery->execute([$order_id, $username]);

        // Update status pesanan menjadi "Sedang Dikirim" pada tabel orders
        $updateQuery = $pdo->prepare("UPDATE orders SET status_pesanan = 'Sedang Dikirim', nama_kurir = ? WHERE id_order = ?");
        $updateQuery->execute([$username, $order_id]);

                // Log pesanan yang diambil oleh kurir
                $logMessage = "Pesanan dengan ID $order_id telah diambil oleh kurir $username.";
                logActivity($pdo, $logMessage, $username);

        
    }
    
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan ada data yang dikirimkan melalui metode POST
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status']; // Menggunakan 'status' bukan 'new_status'

        // Update status pesanan dalam tabel orders
        $updateOrdersQuery = $pdo->prepare("UPDATE orders SET status_pesanan = ? WHERE id_order = ?");
        $updateOrdersQuery->execute([$new_status, $order_id]);
        
        // Update status pesanan dalam tabel order_status
        $updateOrderStatusQuery = $pdo->prepare("UPDATE order_status SET status = ? WHERE id_order = ?");
        $updateOrderStatusQuery->execute([$new_status, $order_id]);

                // Log perubahan status pesanan
                $logMessage = "Status pesanan dengan ID $order_id diubah menjadi $new_status.";
                logActivity($pdo, $logMessage, $username);

        // Redirect kembali ke halaman ini setelah memperbarui status
        header("Location: ?action=orderan_diambil" );
        exit();
    } elseif (isset($_POST['logout'])) {
        // Logout pengguna
        session_unset();
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}


// Mendapatkan data orderan yang telah diambil dari tabel order_status
$orderanQuery = $pdo->prepare("SELECT o.id_order, o.username, o.phone, o.total_price, o.order_date, o.status_pesanan, o.address, o.store_address
                                FROM orders o
                                INNER JOIN order_status os ON o.id_order = os.id_order
                                WHERE os.nama_kurir = ?");
$orderanQuery->execute([$_SESSION['username']]);
$orderan_diambil = $orderanQuery->fetchAll(PDO::FETCH_ASSOC);

// Mendapatkan data orderan dari tabel orders
$ordersQuery = $pdo->query("SELECT * FROM orders");
$orders = $ordersQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Kurir</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-serif bg-[#FFF6F6]">

    <div class="flex">
    <div class="flex flex-col justify-between bg-white border-2 text-black w-1/5 min-h-screen max-h-full py-6 font-serif">
        <div>
            <h1 class="text-[34px] font-serif text-bold px-2">MS STORE</h1>
            <a href="?action=semua_orderan" class="block py-2 px-4 text-black hover:bg-gray-300">Semua Orderan</a>
            <a href="?action=orderan_diambil" class="block py-2 px-4 text-black hover:bg-gray-300">Orderan Diambil</a>

                    <!-- Logout Button -->
        <form method="post" action="" class="mt-[50px]">
            <button type="submit" name="logout" class="block py-2 px-4 w-10/12 ml-2 rounded-lg text-black bg-[#FF8787] hover:bg-red-700">Logout</button>
        </form>
        </div>

    </div>

    <div class="w-4/5 p-8">
        <!-- Content goes here -->
        <h2 class="text-2xl font-bold mb-4">Welcome to Kurir Panel</h2>
        <?php
        // Handle different actions based on the selected menu item
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            switch ($action) {
                case 'semua_orderan':
                    include 'semua_orderan.php'; // You can create daftar_user.php for listing and managing users
                    break;
                case 'orderan_diambil':
                    include 'orderan_diambil.php'; // You can create daftar_barang.php for listing products
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

 
</body>
</html>
