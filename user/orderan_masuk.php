<?php
require_once "config.php";
require_once "../admin/function_log.php";

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Get the user's role
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

    // Handle the form submission to update order status
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];

        // Fetch the order details
        $order_sql = "SELECT * FROM orders WHERE id_order = ? AND seller_username = ?";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("is", $order_id, $username);
        $stmt->execute();
        $order_result = $stmt->get_result();

        if ($order_result->num_rows == 1) {
            $order = $order_result->fetch_assoc();
            $buyer_username = $order['username'];
            $total_price = $order['total_price'];

            if ($new_status === 'orderan gagal') {
                // Update buyer's balance
                $balance_sql = "UPDATE tb_user SET saldo = saldo + ? WHERE username = ?";
                $stmt = $conn->prepare($balance_sql);
                $stmt->bind_param("ds", $total_price, $buyer_username);
                $stmt->execute();
            }

            // Update order status
            $update_sql = "UPDATE orders SET status_pesanan = ? WHERE id_order = ? AND seller_username = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sis", $new_status, $order_id, $username);
            $stmt->execute();
        }

        // Redirect to the same page to see the changes
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Fetch orders for the seller with status 'menunggu penjual' or 'dikirim balik'
    $orders_sql = "SELECT * FROM orders WHERE seller_username = ? AND (status_pesanan = 'menunggu penjual' OR status_pesanan = 'dikirim balik')";
    $stmt = $conn->prepare($orders_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $orders_result = $stmt->get_result();

    // Fetch orders into an array
    $orders = [];
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = $order;
    }
}

// Process logout when the logout button is pressed
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <title>Orderan Masuk</title>
    <link rel="icon" href="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" type="image/png">


</head>
<body class="bg-[#FDEFEF] p-6" style="background-image: url('../assets/unicorn.png');">
<?php include "navbar.php"; ?>

<div class="container mx-auto mt-20">
    <h1 class="text-2xl font-bold mb-4">Orderan</h1>
    <div class="bg-white shadow-md rounded p-6">
        <?php if (!empty($orders)): ?>
            <table class="min-w-full bg-white">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="py-2 px-4">Order ID</th>
                        <th class="py-2 px-4">Product</th>
                        <th class="py-2 px-4">Quantity</th>
                        <th class="py-2 px-4">Total Item Price</th>
                        <th class="py-2 px-4">Total Price</th>
                        <th class="py-2 px-4">Status</th>
                        <th class="py-2 px-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="border py-2 px-4 text-center"><?php echo htmlspecialchars($order['id_order']); ?></td>
                            <td class="border py-2 px-4 text-center"><?php echo htmlspecialchars($order['name']); ?></td>
                            <td class="border py-2 px-4 text-center"><?php echo htmlspecialchars($order['quantity']); ?></td>
                            <td class="border py-2 px-4 text-center"><?php echo htmlspecialchars($order['total_items_price']); ?></td>
                            <td class="border py-2 px-4 text-center"><?php echo htmlspecialchars($order['total_price']); ?></td>
                            <td class="border py-2 px-4 text-center"><?php echo htmlspecialchars($order['status_pesanan']); ?></td>
                            <td class="border py-2 px-4 text-center">
                                <?php if ($order['status_pesanan'] === 'menunggu penjual' || $order['status_pesanan'] === 'dikirim balik'): ?>
                                    <form method="post" class="mb-2">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id_order']); ?>">
                                        <?php if ($order['status_pesanan'] === 'menunggu penjual'): ?>
                                            <input type="hidden" name="new_status" value="menunggu kurir">
                                            <button type="submit" name="update_status" class="bg-blue-500 text-white px-4 py-2 rounded">Menunggu Kurir</button>
                                        <?php elseif ($order['status_pesanan'] === 'dikirim balik'): ?>
                                            <input type="hidden" name="new_status" value="orderan gagal">
                                            <button type="submit" name="update_status" class="bg-red-500 text-white px-4 py-2 rounded">Orderan Gagal</button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-red-500">Tidak ada orderan yang menunggu penjual atau dikirim balik.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
