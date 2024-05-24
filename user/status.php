<?php
require_once "../admin/function_log.php";
include "config.php";

// Pastikan pengguna telah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    // Mendapatkan peran pengguna dari sesi
    $username = $_SESSION['username'];
    $role_sql = "SELECT role FROM tb_user WHERE username = ?";
    $stmt = $conn->prepare($role_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $role_result = $stmt->get_result();

    if ($role_result->num_rows == 1) {
        $role_row = $role_result->fetch_assoc();
        $role = $role_row["role"];

        // Redirect user based on role
        if ($role === 'kurir') {
            header("Location: kurir.php");
            exit();
        } elseif ($role === 'admin') {
            header("Location: admin/admin.php?action=daftar_user");
            exit();
        }
    }
}

// Ambil data pengguna yang sedang login
$username = $_SESSION['username'];

// Mendapatkan status pesanan berdasarkan parameter yang diberikan
if (isset($_GET['status'])) {
    $status_filter = $_GET['status'];
    if ($status_filter === 'menunggu kurir' || $status_filter === 'menunggu penjual') {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE username = ? AND status_pesanan = ?");
        $stmt->execute([$username, $status_filter]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE username = ? AND status_pesanan NOT IN ('menunggu kurir', 'menunggu penjual') AND status_pesanan = ?");
        $stmt->execute([$username, $status_filter]);
    }
} else {
    // Jika tidak ada parameter status, ambil semua status pesanan kecuali "menunggu kurir" dan "menunggu penjual"
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE username = ? AND status_pesanan NOT IN ('menunggu kurir', 'menunggu penjual')");
    $stmt->execute([$username]);
}
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fungsi untuk mendapatkan status pesanan
function getStatuses($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk memperbarui status pesanan
function updateOrderStatus($pdo, $order_id, $new_status, $username) {
    // Update status pesanan dalam tabel orders
    $updateOrdersQuery = $pdo->prepare("UPDATE orders SET status_pesanan = ? WHERE id_order = ?");
    $updateOrdersQuery->execute([$new_status, $order_id]);

    // Update status pesanan dalam tabel order_status
    $updateOrderStatusQuery = $pdo->prepare("UPDATE order_status SET status = ? WHERE id_order = ?");
    $updateOrderStatusQuery->execute([$new_status, $order_id]);

    // Jika status pesanan adalah "diterima pembeli", lakukan penyesuaian saldo
    if ($new_status === 'diterima pembeli') {
        adjustBalance($pdo, $order_id);
        adjustCourierBalance($pdo, $order_id); // Tambahkan penyesuaian saldo kurir
    }
    $logMessage = " Barang diterima pembeli.";
    logActivity($pdo, $logMessage, $username);

    // Redirect kembali ke halaman ini setelah memperbarui status
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fungsi untuk menambahkan saldo penjual
function adjustBalance($pdo, $order_id) {
    // Ambil total harga pesanan
    $username = $_SESSION['username'];
    $getTotalPriceQuery = $pdo->prepare("SELECT total_items_price, seller_username FROM orders WHERE id_order = ?");
    $getTotalPriceQuery->execute([$order_id]);
    $order = $getTotalPriceQuery->fetch(PDO::FETCH_ASSOC);
    $total_items_price = $order['total_items_price'];
    $seller_username = $order['seller_username'];

    // Tambah saldo penjual
    $getSellerBalanceQuery = $pdo->prepare("SELECT saldo FROM tb_user WHERE username = ?");
    $getSellerBalanceQuery->execute([$seller_username]);
    $seller = $getSellerBalanceQuery->fetch(PDO::FETCH_ASSOC);
    $sellerBalance = $seller['saldo'];
    $newSellerBalance = $sellerBalance + $total_items_price;

    // Update saldo penjual
    $updateSellerBalanceQuery = $pdo->prepare("UPDATE tb_user SET saldo = ? WHERE username = ?");
    $updateSellerBalanceQuery->execute([$newSellerBalance, $seller_username]);

    $logMessage = " Menambahkan Saldo Penjual sejumlah Rp" . number_format($total_items_price, 0, ',', '.') . ".";
    logActivity($pdo, $logMessage, $username);
}

// Fungsi untuk menambahkan saldo kurir
function adjustCourierBalance($pdo, $order_id) {
    // Ambil total harga pesanan
    $getTotalPriceQuery = $pdo->prepare("SELECT total_price, total_items_price, nama_kurir FROM orders WHERE id_order = ?");
    $getTotalPriceQuery->execute([$order_id]);
    $order = $getTotalPriceQuery->fetch(PDO::FETCH_ASSOC);
    $total_price = $order['total_price'];
    $total_items_price = $order['total_items_price'];
    $courier_username = $order['nama_kurir'];

    // Kurangi saldo kurir
    $getCourierBalanceQuery = $pdo->prepare("SELECT saldo FROM tb_user WHERE username = ?");
    $getCourierBalanceQuery->execute([$courier_username]);
    $courier = $getCourierBalanceQuery->fetch(PDO::FETCH_ASSOC);
    $courierBalance = $courier['saldo'];
    $newCourierBalance = $courierBalance + ($total_price - $total_items_price);

    // Update saldo kurir
    $updateCourierBalanceQuery = $pdo->prepare("UPDATE tb_user SET saldo = ? WHERE username = ?");
    $updateCourierBalanceQuery->execute([$newCourierBalance, $courier_username]);

    $logMessage = " Menyesuaikan Saldo Kurir.";
    logActivity($pdo, $logMessage, $courier_username);
}

// Proses form jika metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan ada data yang dikirimkan melalui metode POST
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status']; // Menggunakan 'status' bukan 'new_status'

        // Perbarui status pesanan
        updateOrderStatus($pdo, $order_id, $new_status, $username);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    
    <link rel="icon" href="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" type="image/png">

    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom CSS */
        /* Add your custom CSS here */
        .behind-navbar {
            position: relative;
            z-index: -1; /* Set z-index to a lower value */
        }
        .navbar {
            position: relative;
            z-index: 0; /* Set z-index to a higher value */
        }
        
    </style>
</head>

<body class="bg-[#FDEFEF] font-serif" style="background-image: url('assets/unicorn.png');">

    <!-- Navbar -->
    <?php include "navbar.php"; ?>
    <div class="flex justify-center items-center h-full ">
        <div class="w-full max-w-6xl overflow-y-auto mt-16 bg-white p-4">
            <!-- Filter buttons -->
            <div class="flex justify-center mb-4">
                <a href="?status=menunggu penjual" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-4">Menunggu Penjual</a>
                <a href="?status=menunggu kurir" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-4">Menunggu Kurir</a>
                <a href="?status=sedang dikirim" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-4">Sedang Dikirim</a>
                <a href="?status=diterima pembeli" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">Sudah Diterima</a>
            </div>

            <!-- List of orders -->
            <div class=" w-full">
                <?php if (empty($statuses)) : ?>
                    <p class="text-gray-600 text-center">Tidak ada data status pesanan.</p>
                <?php else : ?>
                    <?php foreach ($statuses as $status) : ?>
                        <div class="bg-white border rounded-md mb-4">
                            <div class="flex">
                                <div class="p-4 w-1/6">
                                    <h2 class="text-lg font-bold"><?php echo $status['store_name']; ?></h2>
                                    <img src="../barang/<?php echo $status['photo']; ?>" alt="<?php echo $status['name']; ?>" class="w-full h-auto object-fit rounded-md mt-4">
                                </div>

                                <div class="p-4 w-5/6 flex flex-col justify-between">
                                    <div>
                                        <h2 class="text-lg font-bold uppercase mb-4"><?php echo $status['name']; ?></h2>
                                        <p class="text-black font-semibold border-b-2">Rp <?php echo number_format($status['total_items_price'], 0, ',', '.'); ?></p>
                                        <p class="text-gray-600 font-bold text-sm">ID Pesanan: <?php echo $status['id_order']; ?></p>
                                        <p class="text-gray-600 font-bold text-sm">Tanggal Pesanan: <?php echo $status['order_date']; ?></p>
                                        <p class="text-gray-600 text-sm">Status Pesanan: <?php echo $status['status_pesanan']; ?></p>
                                    </div>
                                    <div class="flex justify-between mt-4">
                                        <p class="text-gray-600"><?php echo $status['quantity']; ?> pcs</p>
                                        <p class="text-gray-600">Total Pesanan : Rp<?php echo number_format($status['total_price'], 0, ',', '.'); ?></p>
                                        <?php if ($status['status_pesanan'] === 'sampai ditujuan') : ?>
                                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                                <input type="hidden" name="order_id" value="<?php echo $status['id_order']; ?>">
                                                <button type="submit" name="status" value="diterima pembeli" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                    Sudah Diterima
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <button onclick="goToTop()" id="goToTopBtn" title="Go to top" class="fixed bottom-4 right-4 bg-[#FF3D00] hover:bg-white hover:text-black text-white font-bold py-2 px-4 rounded hidden">⬆️</button>

    <script>
    // Function to scroll to the top of the page smoothly
    function goToTop() {
        // Scroll smoothly to the top of the page
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Function to show or hide the "Go to Top" button based on scroll position
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        // Show or hide the button based on the scroll position
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("goToTopBtn").style.display = "block";
        } else {
            document.getElementById("goToTopBtn").style.display = "none";
        }
    }
</script>

</body>

</html>
