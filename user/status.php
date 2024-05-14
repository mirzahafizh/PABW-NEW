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

// Panggil fungsi untuk mengambil status pesanan
$statuses = getStatuses($pdo, $username);

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
    <div class="flex justify-center  items-center h-screen">
        <div class=" w-8/12 overflow-y-auto max-h-screen mt-[150px] ">
            <div class="border w-10/12">
            <?php if (empty($statuses)) : ?>
                <p class="text-gray-600 text-center">Tidak ada data status pesanan.</p>
            <?php else : ?>
                </div>
                    <?php foreach ($statuses as $status) : ?>
                        <div class="bg-white border h-[220px] border-gray-300 rounded-md mb-4 ">
                        <div class="flex ">
                        <div class="p-4 w-1/6 ">
                            <h2 class="text-lg font-bold mt-[-10px] mb-8"><?php echo $status['store_name']; ?></h2>
                            <img src="../barang/<?php echo $status['photo']; ?>" alt="<?php echo $status['name']; ?>" class="w-full h-auto object-fit rounded-md mx-auto my-auto"> <!-- Tambahkan mx-auto dan my-auto -->
                        </div>

                            <div class="p-4 flex w-full justify-between">
                                <div class="w-3/4">
                                    <h2 class="text-lg font-bold uppercase "><?php echo $status['name']; ?></h2>
                                    <p class="text-black font-semibold border-b-2">Rp <?php echo number_format($status['total_items_price'], 0, ',', '.'); ?></p>
                                    <p class="text-gray-600 font-bold text-[14px]">ID Pesanan: <?php echo $status['id_order']; ?></p>
                                    <p class="text-gray-600 font-bold text-[14px]">Tanggal Pesanan: <?php echo $status['order_date']; ?></p>
                                    <p class="text-gray-600 text-[14px]">Status Pesanan: <?php echo $status['status_pesanan']; ?></p>
                                </div>
                                <div class="w-1/4 flex flex-col items-end">
                                <p class="text-gray-600  "><?php echo $status['quantity']; ?> pcs </p>
                                </div>
                            </div>

                            </div>
                            <div class="p-4 w-full flex justify-end mt-[-90px]">
                                    <p class="text-gray-600">Total Pesanan : Rp<?php echo number_format($status['total_price'], 0, ',', '.'); ?></p>
                                </div>

                                <div class="p-4 w-full flex justify-end mt-[-20px]">
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                        <input type="hidden" name="order_id" value="<?php echo $status['id_order']; ?>">
                                        <?php if ($status['status_pesanan'] === 'sampai ditujuan') : ?>
                                            <button type="submit" name="status" value="diterima pembeli" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                Sudah Diterima
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>

                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
