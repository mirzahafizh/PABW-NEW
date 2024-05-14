<?php
include '../user/config.php';
require_once 'function_log.php';

// Periksa apakah ada permintaan POST untuk memperbarui informasi barang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    // Ambil data yang dikirim melalui formulir
    $product_id = $_POST['edit_product_id'];
    $new_name = $_POST['edit_product_name'];
    $new_price = $_POST['edit_product_price'];
    $new_store_name = $_POST['edit_product_store'];
    $new_desc = $_POST['edit_product_desc'];

    // Perbarui informasi barang di database
    $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, store_name = ?, description = ? WHERE name = ?");
    $stmt->execute([$new_name, $new_price, $new_store_name, $new_desc, $product_id]);

    // Log aktivitas pengeditan barang
    $log_message = "Updated product: $new_name";
    logActivity($pdo, $log_message, $_SESSION['username']);

    // Redirect kembali ke halaman admin setelah pembaruan
    header("Location: admin.php?action=daftar_barang");
    exit();
}

// Periksa apakah ada parameter GET yang menyertakan id barang yang akan diubah
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['name'])) {
    $name = $_GET['name'];

    // Ambil informasi barang dari database berdasarkan id
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ?");
    $stmt->execute([$name]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika barang ditemukan, tampilkan formulir untuk mengedit informasi barang
    if ($product) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Edit Product</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        </head>
        <body class="bg-gray-100 h-screen flex items-center justify-center">

        <form method="post" action="edit_barang.php" class="bg-white p-8 shadow-lg border border-black mt-10 rounded-md w-96 mx-auto">
            <!-- Input hidden untuk menyimpan id barang yang akan diubah -->
            <input type="hidden" name="edit_product_id" value="<?php echo $product['name']; ?>">

            <!-- Form untuk mengedit informasi barang -->
            <div class="mb-4">
                <label for="edit_product_name" class="block text-sm font-medium text-gray-600">Product Name:</label>
                <input type="text" name="edit_product_name" value="<?php echo $product['name']; ?>" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="mb-4">
                <label for="edit_product_name" class="block text-sm font-medium text-gray-600">Product Description:</label>
                <input type="text" name="edit_product_desc" value="<?php echo $product['description']; ?>" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="mb-4">
                <label for="edit_product_price" class="block text-sm font-medium text-gray-600">Price:</label>
                <input type="number" name="edit_product_price" value="<?php echo $product['price']; ?>" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="mb-4">
                <label for="edit_product_store" class="block text-sm font-medium text-gray-600">Store Name:</label>
                <input type="text" name="edit_product_store" value="<?php echo $product['store_name']; ?>" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <!-- Tombol untuk mengirimkan permintaan pembaruan -->
            <button type="submit" name="update_product" class="bg-green-500 text-white py-2 px-4 rounded-md">Update Product</button>
        </form>

        </body>
        </html>
        <?php
    } else {
        // Jika barang tidak ditemukan, tampilkan pesan error
        echo "Product not found.";
    }
}
?>
