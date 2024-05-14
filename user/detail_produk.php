<?php

require_once "../admin/function_log.php";

include "config.php";

if (isset($_GET['id'])) {
    $productId = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id_produk = ?");
    $stmt->execute([$productId]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}



function formatRupiah($price)
{
    return 'Rp' . number_format($price, 0, ',', '.');
}

// Handle Add to Cart button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Validate quantity (you can add more validation if needed)
    if ($quantity <= 0) {
        // Invalid quantity, handle accordingly
        echo "Invalid quantity";
        exit();
    }

    // Check if the user is logged in
    if (isset($_SESSION['username'])) {
        // User is logged in, proceed to add to cart
        $username = $_SESSION['username'];
        $totalPrice = $quantity * $product['price'];
        $shippingCost = 15000;

        // Fetch product details based on id_produk
        $stmtProduk = $pdo->prepare("SELECT id_toko, store_name, name, profile_store FROM products WHERE id_produk = ?");
        $stmtProduk->execute([$productId]);
        $productDetails = $stmtProduk->fetch(PDO::FETCH_ASSOC);

        // Assign fetched values to variables
        $id_toko = $productDetails['id_toko'];
        $store_name = $productDetails['store_name'];
        $name = $productDetails['name'];
        $profileStore = $productDetails['profile_store'];

        // Check if the product already exists in the cart
        $stmtCartCheck = $pdo->prepare("SELECT * FROM carts WHERE username = ? AND id_produk = ?");
        $stmtCartCheck->execute([$username, $productId]);
        $existingCartItem = $stmtCartCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingCartItem) {
            // If the product already exists in the cart, update its quantity
            $newQuantity = $existingCartItem['quantity'] + $quantity;
            $newTotalPrice = $existingCartItem['total_price'] + $totalPrice;
            // Update query to include product_photo
            $stmtUpdateQuantity = $pdo->prepare("UPDATE carts SET quantity = ?, total_price = ?, product_photo = ? WHERE username = ? AND id_produk = ?");
            $stmtUpdateQuantity->execute([$newQuantity, $newTotalPrice, $product['photo'], $username, $productId]);
        } else {
            // If the product is not in the cart, insert it as a new item
            $stmtInsertCartItem = $pdo->prepare("INSERT INTO carts (username, id_produk, id_toko, store_name, name, price, quantity, total_price, shipping_cost, product_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsertCartItem->execute([$username, $productId, $id_toko, $store_name, $name, $product['price'], $quantity, $totalPrice, $shippingCost, $product['photo']]);
        }
        
        $logMessage = "Menambahkan Barang $name Ke Keranjang .";
        logActivity($pdo, $logMessage,$username);

        // Redirect to the cart page after adding to cart
        header("Location: cart.php");
        exit();
    } else {
        // User is not logged in, redirect to login or handle accordingly
        header("Location: login.php"); // Change this to your login page
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?></title>
    <link rel="icon" href="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" type="image/png">

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.4/dist/tailwind.min.css" rel="stylesheet">
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

        /* Custom style for plus and minus buttons */
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            overflow: hidden;
        }



        .quantity-controls button {
            width: 2.5rem;
            height: 100%;
            font-size: 1.25rem;
            line-height: 1.25rem;
            background-color: #fff;
            border: 0;
            cursor: pointer;
        }

        .quantity-input {
            width: calc(100% - 5rem);
            padding: 0.5rem;
            font-size: 1rem;
            text-align: center;
        }
    </style>

<script>
        function incrementQuantity() {
            var quantityInput = document.getElementById('quantity');
            var currentQuantity = parseInt(quantityInput.value);
            var maxQuantity = parseInt(quantityInput.getAttribute('max'));
            if (currentQuantity < maxQuantity) {
                quantityInput.value = currentQuantity + 1;
            }
        }

        function decrementQuantity() {
            var quantityInput = document.getElementById('quantity');
            var currentQuantity = parseInt(quantityInput.value);
            if (currentQuantity > 1) {
                quantityInput.value = currentQuantity - 1;
            }
        }
    </script>
</head>

<body class="bg-[#FDEFEF]" style="background-image: url('../assets/unicorn.png');">

    <?php include "navbar.php"; ?>
    <div class="container-1 mx-auto font-serif p-6 mt-[90px] ">
    <div class="max-w-2xl mx-auto h-auto  bg-white shadow-lg grid grid-cols-2 gap-4 items-center" style="box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);">

            <!-- Image Section -->
            <div>
                <img src="../barang/<?php echo $product['photo']; ?>" alt="Product Image" class="w-full h-[auto] object-fit ml-3 mb-4">
            </div>
            <!-- Text Section -->
            <div>
                <h2 class="text-3xl font-bold mb-4 px-3 uppercase mt-4"><?php echo strtoupper($product['name']); ?></h2>
                <div class="bg-[#FDEFEF] w-10/12 ml-3">
                <p class="text-[#FF3D00] text-xl mb-2 px-3 "><?php echo formatRupiah($product['price']); ?></p>
                </div>
                <h3 class="text-sm text-gray-400 px-3"><?php echo $product['description']; ?></h3>

                <h3 class="text-sm text-gray-400 px-3 ">Stock :<?php echo $product['stock']; ?></h3>



                <!-- Add to Cart form -->
                <form method="post" action="" class="mt-4 px-3">
                <div>
                    <div class="flex items-center mb-4"> <!-- Flex container for Quantity label and input -->
                        <p class="mr-2 px-3">Quantity</p> <!-- Quantity label -->
                        <!-- Quantity input with plus and minus buttons -->
                        <div class="quantity-controls mt-2w w-[103px] flex items-center">
                            <button type="button" class="px-2 w-1/3" onclick="decrementQuantity(event)">-</button>
                            <input id="quantity" class="quantity w-1/3 px-2 border-white text-center" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <button type="button" class="px-2 w-1/3" onclick="incrementQuantity(event)">+</button>
                        </div>
                    </div>
                </div>
                    <button type="submit" name="add_to_cart" class="bg-white-500 rounded-[5px] text-black border px-4 py-2 flex items-center mb-4">
                        <img src="../assets/simple-line-icons_basket.png" alt="Add to Cart Icon" class="w-6 h-6 mr-2 ">
                        Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="container mx-auto font-serif p-6">
    <div class="max-w-2xl mx-auto bg-white shadow-lg p-6" style="box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);">
        <div class="flex justify-between items-center mb-4">
        </div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <img src="<?php echo $product['profile_store']; ?>" alt="" class="w-12 h-12 object-fit ml-3 mb-4 mx-3 rounded-full shadow-lg">
                <div>
                    <h3 class="text-xl text-black mb-1 flex items-center uppercase"> 
                        <?php echo $product['store_name']; ?>
                    </h3>
                </div>
            </div>
            <a href="kunjungi_toko.php?store_name=<?php echo $product['store_name']; ?>" class="bg-white border border-orange-500 text-orange-500 px-4 py-2 rounded hover:bg-orange-500 hover:text-white ml-2">Kunjungi Toko</a>
        </div>
    </div>
</div>

</div>





</body>

</html>
