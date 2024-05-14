<?php
session_start();

include 'config.php';

// Periksa apakah parameter 'id' ada dalam URL
if(isset($_GET['store_name'])) {
    // Simpan nilai 'id' dari URL ke dalam variabel
    $id_toko = $_GET['store_name'];

    // Query untuk mendapatkan informasi toko dari tabel store_info
    $sql_store = "SELECT * FROM store_info WHERE store_name = '$id_toko'";
    $result_store = $conn->query($sql_store);
    
    // Periksa apakah toko ditemukan
    if($result_store && $result_store->num_rows > 0) {
        // Ambil data toko
        $store = $result_store->fetch_assoc();
        
        // Query untuk mendapatkan produk yang dijual oleh toko dari tabel products
        $sql_products = "SELECT * FROM products WHERE store_name = '$id_toko'";
        $result_products = $conn->query($sql_products);
        
        // Periksa apakah produk ditemukan
        if($result_products && $result_products->num_rows > 0) {
            // Produk ditemukan, lakukan sesuatu
            // Anda dapat menampilkan produk di sini
        } else {
            // Jika tidak ada produk yang ditemukan
            echo "Toko ini belum menjual produk apa pun.";
        }
    } else {
        // Jika toko tidak ditemukan
        echo "Toko tidak ditemukan.";
    }
} else {
    // Jika parameter 'id' tidak ada dalam URL
    echo "Parameter 'id' tidak ditemukan dalam URL.";
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <title>Toko</title>
</head>
<body class="bg-[#FDEFEF] mt-[-20px] bg-cover" style="background-image: url('assets/unicorn.png');">
<?php include 'navbar.php'; ?>

<div class="flex flex-col lg:flex-row items-center justify-center sm:justify-start gap-6 p-6 mt-[100px] sm:mt-[100px]">
    <div class="content lg:w-3/12 w-full  rounded-lg shadow-lg shadow-gray-400 bg-white lg:h-[410px] h-auto  mt-10 p-2">
        <div class="flex flex-col items-center justify-center mb-4">
            <img src="<?php echo $store['profile_store']; ?>" alt="" class="w-20 h-20 object-fit shadow-lg mt-4 mb-4">
            <h1 class="text-xl font-serif uppercase font-bold mb-2 "><?php echo $store['store_name']; ?></h1>
            <p class="text-sm text-center"><?php echo $store['store_address']; ?></p>
        </div>
    </div>

    <div class="w-full card bg-white h-auto rounded-lg p-6 sm:mt-[50px]">
        <h2 class="text-xl font-bold mb-4 uppercase">Produk <?php echo $store['store_name']; ?></h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php
            // Periksa apakah ada hasil dari query produk
            if ($result_products && $result_products->num_rows > 0) {
                // Tampilkan data untuk setiap produk
                while($product = $result_products->fetch_assoc()) {
            ?>
            <a href="detail_produk.php?id=<?php echo $product['id_produk']; ?>" class="block">
                <div class="bg-white border border-[#FF3D00] h-[320px] px-4 rounded-lg shadow-md hover:shadow-xl transition-transform transform hover:scale-105">
                    <img src="../barang/<?php echo $product['photo']; ?>" alt="<?php echo $product['name']; ?>" class="w-full h-48 object-fit mb-2">
                    <h3 class="text-[15px] font-bold mb-2"><?php echo $product['name']; ?></h3>
                    <p class="text-gray-800 font-semibold mt-2"><?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                </div>
            </a>
            <?php
                }
            } else {
                // Jika tidak ada produk yang ditemukan
                echo "<p class='text-gray-600'>Toko ini belum menjual produk apa pun.</p>";
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>
