<!-- Daftar Barang -->
<?php

?>
<div>
    <h3 class="text-xl font-semibold mb-2">Daftar Barang</h3>
    
    <div class="w-full  mx-auto flex justify-center">

    <!-- Input pencarian -->
    <input type="text" id="searchInput" onkeyup="searchProducts()" placeholder="Cari nama barang..." class="border-2 w-10/12 rounded-lg p-2 mb-4">
    </div>
    <?php $items = getItems($pdo); ?>
    <?php foreach ($items as $item): ?>
        <div class="border-2 p-4 mb-4 product-item bg-white rounded-lg">
            <!-- Product photo -->
            <?php
            $image_path = '../barang/' . $item['photo']; // Path to the product photo folder
            if (file_exists($image_path)) {
                echo '<img src="' . $image_path . '" alt="Product Photo" class="w-24 h-24">';
            } else {
                echo '<p>Gambar tidak ditemukan</p>';
            }
            ?>

            <!-- Product details -->
            <p><strong>Nama:</strong> <?php echo $item['name']; ?></p>
            <p style="display:none;"><strong>ID Produk:</strong> <?php echo $item['id_produk']; ?></p>
            <p><strong>Harga:</strong> <?php echo $item['price']; ?></p>
            <p><strong>Nama Toko:</strong> <?php echo $item['store_name']; ?></p>
            <!-- Add more product details as needed -->

            <!-- Tombol Edit dan Delete -->
            <div class="mt-4">
                <form method="post" action="admin.php" class="inline-block">
                    <input type="hidden" name="id_produk" value="<?php echo $item['id_produk']; ?>">
                    <button type="submit" name="delete_item" class="bg-red-500 text-white rounded-lg py-2 px-4 mt-2 mr-2">Delete User</button>
                </form>
                <a href="admin.php?action=edit_barang&name=<?php echo $item['name']; ?>" class="bg-blue-500 rounded-lg text-white py-2 px-4 mt-2 inline-block">Edit Barang</a>
            
            </div>

        </div>
    <?php endforeach; ?>
</div>

<script>
    function searchProducts() {
        // Get the search input value
        var input, filter, products, productName;
        input = document.getElementById('searchInput');
        filter = input.value.toUpperCase();
        products = document.getElementsByClassName('product-item');

        // Iterate over each product and hide those that do not match the search criteria
        for (var i = 0; i < products.length; i++) {
            productName = products[i].getElementsByTagName('p')[0].innerText.toUpperCase(); // Get the product name
            if (productName.indexOf(filter) > -1) {
                products[i].style.display = "";
            } else {
                products[i].style.display = "none";
            }
        }
    }
</script>
