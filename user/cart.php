<?php

include "config.php";
require_once "../admin/function_log.php";

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

function handleCheckout($username, $selectedItems) {
    global $pdo;

    foreach ($selectedItems as $cartItemId) {
        // Ambil data dari tabel 'carts' berdasarkan id_cart
        $stmtCart = $pdo->prepare("SELECT * FROM carts WHERE id_cart = ?");
        $stmtCart->execute([$cartItemId]);
        $cartData = $stmtCart->fetch(PDO::FETCH_ASSOC);

        // Persiapkan nilai untuk dimasukkan ke dalam tabel 'pra_order'
        $id_cart = $cartItemId;
        $id_toko = $cartData['id_toko'];
        $store_name = $cartData['store_name'];
        $name = $cartData['name'];
        $price = $cartData['price'];
        $quantity = $cartData['quantity'];
        $shipping_cost = $cartData['shipping_cost'];
        $product_photo = $cartData['product_photo']; // Ambil foto produk dari 'carts'

        // Hitung total harga
        $total_price = ($quantity * $price) + $shipping_cost;

        // Masukkan data ke dalam tabel 'pra_order'
        $stmtOrder = $pdo->prepare("INSERT INTO pra_order (username, id_cart, id_toko, total_price, shipping_cost, store_name, name, price, quantity, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtOrder->execute([$username, $id_cart, $id_toko, $total_price, $shipping_cost, $store_name, $name, $price, $quantity, $product_photo]);
    }

    $logMessage = " Checkout Keranjang dengan ID $cartItemId.";
    logActivity($pdo, $logMessage,$username);
    // Redirect ke halaman konfirmasi atau sesuai kebutuhan
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

    
    $logMessage = " Menghapus Barang dari Keranjang dengan ID $cartItemId.";
    logActivity($pdo, $logMessage,$username);
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

// Ambil nilai quantity dan id item dari POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Jika request adalah untuk update quantity
    if (isset($_POST['cart_item_id']) && isset($_POST['new_quantity'])) {
        $cartItemId = $_POST['cart_item_id'];
        $newQuantity = $_POST['new_quantity'];

        // Call the function to update cart item quantity
        $success = updateCartQuantity($cartItemId, $newQuantity);
        if ($success) {
            echo "Quantity berhasil diperbarui!";
        } else {
            echo "Gagal memperbarui quantity.";
        }
        exit; // Hentikan eksekusi setelah proses pembaruan quantity
    }

}

// Function to update cart item quantity// Function to update cart item quantity and total price
function updateCartQuantity($cartItemId, $newQuantity) {
    global $pdo;

    $username =$_SESSION['username'];
    // Get the price of the product
    $stmtPrice = $pdo->prepare("SELECT price FROM carts WHERE id_cart = ?");
    $stmtPrice->execute([$cartItemId]);
    $price = $stmtPrice->fetchColumn();

    // Calculate new total price
    $newTotalPrice = $price * $newQuantity;

    // Update quantity and total price in the database
    $stmt = $pdo->prepare("UPDATE carts SET quantity = ?, total_price = ? WHERE id_cart = ?");
    $success = $stmt->execute([$newQuantity, $newTotalPrice, $cartItemId]);
    return $success;

}

// Fetch cart items for the current user
$stmt = $pdo->prepare("SELECT * FROM carts WHERE username = ?");
$stmt->execute([$username]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format price
function formatRupiah($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart <?php echo $_SESSION['username']; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

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

<body class="bg-[#FDEFEF]" style="background-image: url('assets/unicorn.png');">
    <!-- Navbar -->
    <?php include "navbar.php"; ?>
    
    <div class="container mx-auto font-serif p-6 mt-[100px]">
        <div class="w-full  mx-auto ">

            <form method="post" action="" enctype="multipart/form-data">


            <div class="bg-white w-10/12 mx-auto shadow-lg rounded-lg p-4">
            <div class="w-10/12 mx-auto text-center">
                <h2 class="text-3xl font-bold mb-4 ">Your Cart</h2>
                <?php if (empty($cartItems)) : ?>
                    <p>Your cart is empty.</p>
                <?php else : ?>
            </div>
                <?php foreach ($cartItems as $cartItem): ?>
                    <div class="flex items-center border-b border-gray-400 pb-4 mb-4">
                        <!-- Checkbox for selecting items -->
                        <input type="checkbox" name="selected_items[]" value="<?php echo $cartItem['id_cart']; ?>" class="mr-4">

                        <!-- Product photo -->
                        <img src="../barang/<?php echo $cartItem['product_photo']; ?>" alt="Product Photo" class="w-20 h-20 object-fit mr-4">

                        <!-- Cart item details -->
                        <div class="flex-grow">
                            <h3 class="text-lg font-semibold mb-2 uppercase "><?php echo $cartItem['name']; ?></h3>
                            <p class="text-[#FF3D00] font-bold"> <?php echo formatRupiah($cartItem['total_price']); ?></p>
                                    <p class=" px-4 py-2  " style="display: none;"><?php echo $cartItem['store_name']; ?></p>
                                    <p class=" px-4 py-2  " style="display: none;"><?php echo $cartItem['quantity']; ?></p>
                                    <p class=" px-4 py-2  " style="display: none;"><?php echo $cartItem['shipping_cost']; ?></p>
                                    <p class=" px-4 py-2  " style="display: none;"><?php echo formatRupiah($cartItem['total_price']); ?></p>
                        </div>

                        <div class="rounded-lg border-2 mt-16">
                            <!-- Quantity controls -->
                            <div class="quantity-controls flex items-center">
                                <button type="button" class="px-2" onclick="updateQuantity(<?php echo $cartItem['id_cart']; ?>, 'decrement')">-</button>
                                <form id="quantity_form_<?php echo $cartItem['id_cart']; ?>" method="post" action="">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $cartItem['id_cart']; ?>">
                                    <input id="quantity_<?php echo $cartItem['id_cart']; ?>" class="quantity px-2 text-center" name="new_quantity" value="<?php echo $cartItem['quantity']; ?>" min="1" max="<?php echo $product['stock']; ?>">
                                </form>
                                <button type="button" class="px-2" onclick="updateQuantity(<?php echo $cartItem['id_cart']; ?>, 'increment')">+</button>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
                <div class="mt-4 w-10/12 ">
                    <button type="submit" name="checkout" class="bg-[#FFC0C0] text-black rounded w-[120px] px-4 py-2 mx-3">Checkout</button>
                    <?php if (!empty($cartItems)) : ?>
                        <button type="submit" name="delete" value="<?php echo $cartItems[0]['id_cart']; ?>" class="bg-[#FFC0C0] text-black rounded w-[120px] px-4 py-2 mx-2 mt-4">Delete</button>
                    <?php endif; ?>
                </div>

            </form>
        <?php endif; ?>
        </div>
    </div>
</div>
<script>
    function incrementQuantity(cartItemId) {
        var quantityInput = document.getElementById('quantity_' + cartItemId);
        var currentQuantity = parseInt(quantityInput.value);
        var maxQuantity = parseInt(quantityInput.getAttribute('max'));
        if (currentQuantity < maxQuantity) {
            quantityInput.value = currentQuantity + 1;
            updateQuantity(cartItemId, 'increment'); // Call the function to update quantity
        }
    }

    function decrementQuantity(cartItemId) {
        var quantityInput = document.getElementById('quantity_' + cartItemId);
        var currentQuantity = parseInt(quantityInput.value);
        if (currentQuantity > 1) {
            quantityInput.value = currentQuantity - 1;
            updateQuantity(cartItemId, 'decrement'); // Call the function to update quantity
        }
    }

    function updateQuantity(cartItemId, action) {
        var quantityInput = document.getElementById('quantity_' + cartItemId);
        var currentQuantity = parseInt(quantityInput.value);
        var newQuantity;

        if (action === 'decrement') {
            newQuantity = currentQuantity - 1;
            if (newQuantity < 1) {
                return; // Do not update if quantity is already minimum
            }
        } else if (action === 'increment') {
            var maxQuantity = parseInt(quantityInput.getAttribute('max'));
            newQuantity = currentQuantity + 1;
            if (newQuantity > maxQuantity) {
                return; // Do not update if quantity is already maximum
            }
        }

        // Send an AJAX request to update the quantity
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                // Response from the server (may not need to be displayed)
                console.log(this.responseText);
                // Reload the page after successfully updating the quantity
                window.location.reload();
            }
        };
        xhttp.open("POST", "cart.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("cart_item_id=" + cartItemId + "&new_quantity=" + newQuantity);
    }
</script>

</body>
</html>

