<?php
session_start();

include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Function to handle the checkout process and insert selected items into 'pra_order' table
function handleCheckout($username, $selectedItems) {
    global $pdo;

// Insert selected items into 'pra_order' table
foreach ($selectedItems as $cartItemId) {
    // Query to get data from 'carts' table based on id_cart
    $stmtCart = $pdo->prepare("SELECT id_toko, store_name, name, price, quantity, shipping_cost FROM carts WHERE id_cart = ?");
    $stmtCart->execute([$cartItemId]);
    $cartData = $stmtCart->fetch(PDO::FETCH_ASSOC);

    // Prepare values to be inserted into 'pra_order' table
    $id_cart = $cartItemId;
    $id_toko = $cartData['id_toko']; // Retrieve id_toko from 'carts'
    $store_name = $cartData['store_name'];
    $name = $cartData['name'];
    $price = $cartData['price'];
    $quantity = $cartData['quantity'];
    $shipping_cost = $cartData['shipping_cost'];

    // Calculate total price
    $total_price = ($quantity * $price) + $shipping_cost;

    // Insert data into 'pra_order' table
    $stmtOrder = $pdo->prepare("INSERT INTO pra_order (username, id_cart, id_toko, total_price, shipping_cost, store_name, name, price, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtOrder->execute([$username, $id_cart, $id_toko, $total_price, $shipping_cost, $store_name, $name, $price, $quantity]);
}


    // Redirect to a confirmation page or handle as needed
    header("Location: checkout.php");
    exit();
}


// Handle Checkout button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Get selected items from the form
    $selectedItems = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];

    // Call the function to handle checkout and insert selected items into 'pra_order' table
    handleCheckout($username, $selectedItems);
}


// Function to delete a cart item
function deleteCartItem($cartItemId, $username) {
    global $pdo;

    // Assuming you have a 'carts' table to store cart items
    $stmt = $pdo->prepare("DELETE FROM carts WHERE id_cart = ? AND username = ?");
    $stmt->execute([$cartItemId, $username]);

    // Redirect back to the cart page or handle as needed
    header("Location: cart.php");
    exit();
}

// Handle Checkout button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Redirect to checkout.php with total price
    header("Location: checkout.php?total_price=" . $total);
    exit();
}

// Updated PHP code
// Handle delete button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $cartItemId = $_POST['delete'];
    // Assuming you have a function to handle item deletion
    // Replace 'deleteCartItem' with your actual function
    deleteCartItem($cartItemId, $username);
}

$stmt = $pdo->prepare("SELECT * FROM carts WHERE username = ?");
$stmt->execute([$username]);

$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatRupiah($price)
{
    return 'Rp' . number_format($price, 0, ',', '.');
}

// Calculate total price for selected item
// Fetch total price from the database
$totalQuery = $pdo->prepare("SELECT SUM(total_price) as total FROM carts WHERE username = ?");
$totalQuery->execute([$username]);
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$total = $totalResult['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart <?php echo $_SESSION['username']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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

<body class="bg-gray-100">
    <!-- Navbar -->
    <?php include "navbar.php"; ?>
    
    <div class="container mx-auto font-serif p-6 mt-[100px]">

        <div class="w-full  mx-auto ">
            <div class="w-10/12 mx-auto">
            <h2 class="text-3xl font-bold mb-4">Your Cart</h2>
            <?php if (empty($cartItems)) : ?>
                <p>Your cart is empty.</p>
            <?php else : ?>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="overflow-x-auto">
                        <table class="border-collapse w-10/12 mx-auto table-auto">
                            <!-- Table headers -->
                            <thead>
                                <tr>
                                    <th class="border border-gray-400 px-4 py-2">Select</th>
                                    <th class="border border-gray-400 px-4 py-2">Name</th>
                                    <th class="border border-gray-400 px-4 py-2">Store Name</th>
                                    <th class="border border-gray-400 px-4 py-2">Quantity</th>
                                    <th class="border border-gray-400 px-4 py-2">Shipping Cost</th>
                                    <th class="border border-gray-400 px-4 py-2">Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $cartItem) : ?>
                                    <tr>
                                        <!-- Checkbox for selecting items -->
                                        <td class="border border-gray-400 px-4 py-2">
                                            <input type="checkbox" name="selected_items[]" value="<?php echo $cartItem['id_cart']; ?>">
                                        </td>
                                        <!-- Cart item details -->
                                        <td class="border border-gray-400 px-4 py-2"><?php echo $cartItem['name']; ?></td>
                                        <td class="border border-gray-400 px-4 py-2"><?php echo $cartItem['store_name']; ?></td>
                                        <td class="border border-gray-400 px-4 py-2"><?php echo $cartItem['quantity']; ?></td>
                                        <td class="border border-gray-400 px-4 py-2"><?php echo $cartItem['shipping_cost']; ?></td>
                                        <td class="border border-gray-400 px-4 py-2"><?php echo formatRupiah($cartItem['total_price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Checkout and delete buttons -->
                    <div class="mt-4   w-10/12 mx-auto">
                        <button type="submit" name="checkout" class="bg-blue-500 text-white px-4 py-2">Checkout</button>
                        <?php if (!empty($cartItems)) : ?>
                            <button type="submit" name="delete" value="<?php echo $cartItems[0]['id_cart']; ?>" class="bg-red-500 text-white px-4 py-2 mt-4">Delete</button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>