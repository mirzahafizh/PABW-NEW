<?php
require_once '../admin/function_log.php';
include "config.php";

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
        if ($role === 'kurir') {
            header("Location: kurir.php");
            exit();
        } elseif ($role === 'admin') {
            header("Location: admin/admin.php?action=daftar_user");
            exit();
        }
    }
}

$username = $_SESSION['username'];

// Fetch order details for the current user from 'pra_order' table
$stmt = $pdo->prepare("SELECT * FROM pra_order WHERE username = ?");
$stmt->execute([$username]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format price
function formatRupiah($price)
{
    return 'Rp' . number_format($price, 0, ',', '.');
}

// Function to handle cancellation of all orders
function cancelOrders($username, $pdo) {
    $cancelQuery = $pdo->prepare("DELETE FROM pra_order WHERE username = ?");
    $cancelQuery->execute([$username]);

    $logMessage = "Cancel Order.";
    logActivity($pdo, $logMessage, $username);
    // Redirect to checkout page after cancellation
    header("Location: cart.php");
    exit();
}

// Handle cancellation of all orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    cancelOrders($username, $pdo);
}

// Function to handle payment deduction from user's balance
function deductBalance($username, $amount, $pdo) {
    // Retrieve user's current balance
    $getUserBalanceStmt = $pdo->prepare("SELECT saldo FROM tb_user WHERE username = ?");
    $getUserBalanceStmt->execute([$username]);
    $userData = $getUserBalanceStmt->fetch(PDO::FETCH_ASSOC);
    $currentBalance = $userData['saldo'];

    // Check if user has sufficient balance
    if ($currentBalance >= $amount) {
        // Deduct the amount from user's balance
        $newBalance = $currentBalance - $amount;

        // Update user's balance in the database
        $updateBalanceStmt = $pdo->prepare("UPDATE tb_user SET saldo = ? WHERE username = ?");
        $updateBalanceStmt->execute([$newBalance, $username]);

        return true; // Deduction successful
    } else {
        return false; // Insufficient balance
    }
}

// Function to handle payment when "Pay" button is clicked
function handlePayment($username, $orders, $pdo) {
    // Calculate total price of all orders
    $totalPriceToDeduct = 0;
    foreach ($orders as $order) {
        // Fetch seller_username from store_info table
        $sellerStmt = $pdo->prepare("SELECT username AS seller_username FROM store_info WHERE id_toko = ?");
        $sellerStmt->execute([$order['id_toko']]);
        $sellerInfo = $sellerStmt->fetch(PDO::FETCH_ASSOC);
        $seller_username = $sellerInfo['seller_username'];

        // Check if the user is trying to checkout from their own store
        if ($username == $seller_username) {
            $errorMessage = "Anda tidak dapat checkout dari toko Anda sendiri.";
            echo "<script>alert('$errorMessage'); window.location.href='checkout.php';</script>";
            exit();
        }

        $totalPriceToDeduct += $order['total_price'];
    }

    // Attempt to deduct balance from user's account
    if (deductBalance($username, $totalPriceToDeduct, $pdo)) {
        // If deduction is successful, continue with payment process
        
        // Clear pra_order table after payment
        $cancelQuery = $pdo->prepare("DELETE FROM pra_order WHERE username = ?");
        $cancelQuery->execute([$username]);

        // Delete items from 'carts' table
        $deleteCartStmt = $pdo->prepare("DELETE FROM carts WHERE id_cart = ?");
        foreach ($orders as $order) {
            $deleteCartStmt->execute([$order['id_cart']]);
        }

        // Insert order data into 'orders' table
        foreach ($orders as $order) {
            // Fetch seller_username from store_info table
            $sellerStmt = $pdo->prepare("SELECT username AS seller_username FROM store_info WHERE id_toko = ?");
            $sellerStmt->execute([$order['id_toko']]);
            $sellerInfo = $sellerStmt->fetch(PDO::FETCH_ASSOC);
            $seller_username = $sellerInfo['seller_username'];

            // Fetch store_address from store_info table
            $storeAddressStmt = $pdo->prepare("SELECT store_address FROM store_info WHERE id_toko = ?");
            $storeAddressStmt->execute([$order['id_toko']]);
            $storeAddressInfo = $storeAddressStmt->fetch(PDO::FETCH_ASSOC);
            $store_address = $storeAddressInfo['store_address'];

            // Fetch user's phone and address from 'tb_user' table
            $userStmt = $pdo->prepare("SELECT phone, address FROM tb_user WHERE username = ?");
            $userStmt->execute([$username]);
            $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
            $telepon = $userInfo['phone'];
            $alamat = $userInfo['address'];

            $productName = $order['name'];
            $phone = $telepon; // Ambil nomor telepon dari pesanan
            $address = $alamat; // Ambil alamat dari pesanan
            $storeAddress = $store_address; // Ambil alamat toko dari pesanan
            $storeName = $order['store_name'];
            $shippingCost = $order['shipping_cost'];
            $totalPrice = $order['total_price'];
            $price = $order['price'];
            $quantity = $order['quantity']; // Ambil jumlah barang dari pesanan
            $sellerName = $seller_username;
            $photo = $order['photo']; // Add photo to order data

            // Insert order data into 'orders' table
            $stmt = $pdo->prepare("INSERT INTO orders (username, name, status_pesanan, phone, address, store_address, store_name, shipping_cost, total_price, total_items_price, seller_username, photo, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $productName, 'menunggu penjual', $phone, $address, $storeAddress, $storeName, $shippingCost, $totalPrice, $price * $quantity, $sellerName, $photo, $quantity]);
        }

        $logMessage = "Payment Successful.";
        logActivity($pdo, $logMessage, $username);

        // Redirect to cart page after payment
        header("Location: status.php");
        exit();
    } else {
        // If deduction fails due to insufficient balance
        $errorMessage = "Saldo tidak mencukupi. Silakan hubungi admin untuk top up saldo.";
        // Redirect back with error message using JavaScript alert
        echo "<script>alert('$errorMessage'); window.location.href='checkout.php';</script>";

        $logMessage = "Payment Unsuccessful.";
        logActivity($pdo, $logMessage, $username);
        exit();
    }
}

// Handle payment when "Pay" button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    handlePayment($username, $orders, $pdo);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-[#FDEFEF]" style="background-image: url('assets/unicorn.png');">
<?php include "navbar.php"; ?>

<div class="max-w-3xl mx-auto bg-white shadow-lg p-8 rounded-md mt-[150px]">
    <?php if (empty($orders)) : ?>
        <p class="text-gray-600">No orders to checkout.</p>
    <?php else : ?>
        <div class="w-full mx-auto">
            <div class="bg-white border-[4px] rounded-md p-4">
                <?php if (empty($orders)) : ?>
                    <p class="text-gray-600">No orders to display.</p>
                <?php else : ?>
                    <?php foreach ($orders as $order) : ?>
                        <div class="border-b border-gray-300 py-4 flex flex-wrap items-center">
                            <div>
                                <img src="../barang/<?php echo $order['photo']; ?>" alt="Product Photo" class="w-20 h-20 object-fit mr-4">
                            </div>
                            <div>
                                <div class="w-full  px-4 mb-2 lg:mb-0">
                                    <h3 class="text-lg font-bold uppercase "><?php echo $order['name']; ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo $order['store_name']; ?></p>
                                </div>
                                <div class="w-full  px-4 mb-2 lg:mb-0">
                                    <p class="text-md font-semibold"><?php echo $order['quantity']; ?> Pcs</p>
                                </div>
                                <div class="w-full  px-4 mb-2 lg:mb-0">
                                    <p class="text-lg font-bold"><?php echo formatRupiah($order['price'] * $order['quantity']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($orders)) : ?>
            <?php
            $totalPrice = 0;
            $totalItemsPrice = 0;
            $shippingCostMap = [];

            foreach ($orders as $order) {
                $totalItemsPrice += ($order['price'] * $order['quantity']);
                $totalPrice += $order['total_items_price'];
                $shippingCostMap[$order['id_toko']] = $order['shipping_cost'];
            }
            $totalShippingCost = array_sum($shippingCostMap);
            ?>

            <div class="w-5/12 ml-auto grid grid-cols-2 mt-2">
                <div class="col-span-1 ">
                    <p class="text-left mt-2 font-bold font-serif">Subtotal</p>
                    <p class="text-left text-[#665C5C] font-serif mt-2">Harga Barang</p>
                    <p class="text-left text-[#665C5C] font-serif mt-2">Biaya Pengiriman</p>
                </div>
                <div class="col-span-1 ">
                    <p class="text-right mt-2 font-bold font-serif"><?php echo formatRupiah($totalItemsPrice + $totalShippingCost); ?></p>
                    <p class="text-right text-[#665C5C] font-serif mt-2"><?php echo formatRupiah($totalItemsPrice); ?></p>
                    <p class="text-right text-[#665C5C] font-serif mt-2"><?php echo formatRupiah($totalShippingCost); ?></p>
                </div>
            </div>

            <form method="post" action="" class="mt-6  flex justify-end">
                <!-- Add payment form fields -->
                <button type="submit" name="pay" class="bg-[#A69797] w-32 text-black font-reguler font-serif px-4 py-2 rounded-md uppercase">PAYMENT</button>
                <!-- Cancel button to go back to cart -->
                <button type="submit" name="cancel" class="bg-[#FFC0C0] w-32 text-black font-reguler font-serif px-4 py-2 ml-4 rounded-md uppercase">Cancel</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>

</html>
