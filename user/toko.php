<?php

require_once "config.php";
require_once "../admin/function_log.php";

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


function addProduct($pdo, $id_toko, $store_name, $name, $price, $description, $photo, $stock)
{
    // Ambil profile_store dari tabel store_info
    $username = $_SESSION['username'];

    $queryProfileStore = "SELECT profile_store FROM store_info WHERE id_toko = ?";
    $statementProfileStore = $pdo->prepare($queryProfileStore);
    $statementProfileStore->execute([$id_toko]);
    $profileStoreRow = $statementProfileStore->fetch(PDO::FETCH_ASSOC);
    $profileStore = $profileStoreRow['profile_store'];

    // Insert data produk beserta profile_store ke dalam tabel products
    $query = "INSERT INTO products (id_toko, store_name, name, price, description, photo, stock, profile_store) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $statement = $pdo->prepare($query);
    $statement->execute([$id_toko, $store_name, $name, $price, $description, $photo, $stock, $profileStore]);

            // Log perubahan status pesanan
    $logMessage = "Menambahkan Barang  $name .";
    logActivity($pdo, $logMessage,$username);

}

function addStoreInfo($pdo, $username, $storeName, $storeField, $storeAddress, $photoDestination)
{
    $queryUserId = "SELECT id FROM tb_user WHERE username = ?";
    $statementUserId = $pdo->prepare($queryUserId);
    $statementUserId->execute([$username]);
    $user = $statementUserId->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['id'];

        $queryStoreInfo = "INSERT INTO store_info (id, username, store_name, store_field, store_address, profile_store) VALUES (?, ?, ?, ?, ?, ?)";
        $statementStoreInfo = $pdo->prepare($queryStoreInfo);
        $statementStoreInfo->execute([$userId, $username, $storeName, $storeField, $storeAddress, $photoDestination]);
    } else {
        echo '<script>alert("User not found.");</script>';
    }

    $logMessage = "Menambahkan Info Toko  $storeName .";
    logActivity($pdo, $logMessage,$username);

}




// Function modified to include photo parameter



// Check if the store information exists
$queryCheckStore = "SELECT * FROM store_info";
$statementCheckStore = $pdo->prepare($queryCheckStore);
$statementCheckStore->execute();
$storeInfo = $statementCheckStore->fetch(PDO::FETCH_ASSOC);


// Check if the store information exists
$queryCheckStore = "SELECT * FROM store_info";
$statementCheckStore = $pdo->prepare($queryCheckStore);
$statementCheckStore->execute();
$storeInfo = $statementCheckStore->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['tambah_info_toko'])) {
    // Retrieve other form data
    $storeName = $_POST['store_name'];
    $storeField = $_POST['store_field'];
    $storeAddress = $_POST['street'] . ', ' . $_POST['city'] . ', ' . $_POST['province']; // Menggabungkan alamat dari field terpisah
    $profileStore = $_FILES['profile_store']['name'];
    // Handle uploaded photo
    $photoName = $_FILES['profile_store']['name'];
    $photoTmpName = $_FILES['profile_store']['tmp_name'];
    $photoSize = $_FILES['profile_store']['size'];
    $photoError = $_FILES['profile_store']['error'];

    // Check if photo was uploaded successfully
    if ($photoError === UPLOAD_ERR_OK) {
        // Move uploaded file to desired location
        $photoDestination = '../profile_store/' . $photoName;
        move_uploaded_file($photoTmpName, $photoDestination);

        // Proceed with adding store information to the database
        $username = $_SESSION['username'];
        addStoreInfo($pdo, $username, $storeName, $storeField, $storeAddress, $photoDestination);

        echo '<script>alert("Informasi toko berhasil ditambahkan.");</script>';
        header("Location: toko.php");
        exit();
    } else {
        // Handle upload error
        echo '<script>alert("Error uploading photo.");</script>';
    }
}




// Proses tambah produk jika form disubmit
if (isset($_POST['tambah_produk'])) {
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_description = $_POST['product_description'];
    $product_stock = $_POST['product_stock']; // Assuming you have an input field for stock


    // Assuming you have $username available
    $username = $_SESSION['username'];

    // Fetch id_toko and store_name from store_info based on the username
    $queryStoreInfo = "SELECT id_toko, store_name,profile_store FROM store_info WHERE username = ?";
    $statementStoreInfo = $pdo->prepare($queryStoreInfo);
    $statementStoreInfo->execute([$username]);
    $storeInfoRow = $statementStoreInfo->fetch(PDO::FETCH_ASSOC);

    // Check if store_info exists for the given username
    if ($storeInfoRow) {
        $id_toko = $storeInfoRow['id_toko'];
        $store_name = $storeInfoRow['store_name'];

        // Initialize $uploadOk
        $uploadOk = 1;
        // Handle file upload only if a file is selected
        if (!empty($_FILES["product_photo"]["name"])) {
            $targetDirectory = "../barang/";
            $targetFile = $targetDirectory . basename($_FILES["product_photo"]["name"]);

            // ... (rest of the file upload code)

            if ($uploadOk == 0) {
                echo '<script>alert("Sorry, your file was not uploaded.");</script>';
            } else {
                // If everything is ok, try to upload file
                if (move_uploaded_file($_FILES["product_photo"]["tmp_name"], $targetFile)) {

                    // Add product to the database with the file name
                    // Memanggil fungsi addProduct dengan 8 argumen
                    addProduct($pdo, $id_toko, $store_name, $product_name, $product_price, $product_description, basename($_FILES["product_photo"]["name"]), $product_stock);


                } else {
                    echo '<script>alert("Sorry, there was an error uploading your file.");</script>';
                }
            }
        } else {
            // If no file is selected, add the product without a photo
            addProduct($pdo, $id_toko, $store_name, $product_name, $product_price, $product_description,$stock, null);

            // Show a JavaScript alert
            echo '<script>alert("Produk berhasil ditambahkan.");</script>';
        }
    } else {
        // Handle the case where store_info doesn't exist for the given username
        echo '<script>alert("User not found.");</script>';
    }
}



// Proses edit produk jika form disubmit
if (isset($_POST['edit_produk'])) {
    $edited_product_id = $_POST['edited_product_id'];
    $edited_product_name = $_POST['edited_product_name'];
    $edited_product_price = $_POST['edited_product_price'];
    $edited_product_description = $_POST['edited_product_description'];
    $edited_product_stock = $_POST['edited_product_stock'];

    $queryUpdateProduct = "UPDATE products SET name = ?, price = ?, description = ?, stock = ? WHERE id_produk = ?";
    $statementUpdateProduct = $pdo->prepare($queryUpdateProduct);
    $statementUpdateProduct->execute([$edited_product_name, $edited_product_price, $edited_product_description, $edited_product_stock, $edited_product_id]);
    
    $logMessage = "Mengedit Produk $edited_product_name .";
    logActivity($pdo, $logMessage,$username);

    echo '<script>alert("Product updated successfully.");</script>';
}



function formatRupiah($price)
{
    return 'Rp' . number_format($price, 0, ',', '.');
}

// Sisipkan file koneksi ke database
include "config.php";

$username = $_SESSION['username'];
$role ='';


// Ambil email dan lokasi foto profil berdasarkan username
$stmt = $pdo->prepare("SELECT role FROM tb_user WHERE username = ?");
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $role = $row['role'];

}

// Proses logout ketika tombol logout ditekan
if (isset($_POST['logout'])) {
    // Hapus semua data sesi
    session_unset();
    // Hancurkan sesi
    session_destroy();
    // Redirect ke halaman login.php
    header("Location: ../login.php");
    exit();
}

// Tangani penghapusan produk jika form disubmit
if (isset($_POST['delete_produk'])) {
    $deleted_product_id = $_POST['deleted_product_id'];

    // Query untuk menghapus produk berdasarkan ID
    $queryDeleteProduct = "DELETE FROM products WHERE id_produk = ?";
    $statementDeleteProduct = $pdo->prepare($queryDeleteProduct);
    $statementDeleteProduct->execute([$deleted_product_id]);

    // Redirect atau tampilkan pesan sukses jika dihapus
    echo '<script>alert("Produk berhasil dihapus.");</script>';
    header("Location: toko.php"); // Redirect ke halaman toko setelah menghapus produk
    exit();
}


$query = "SELECT * FROM products";
$statement = $pdo->prepare($query);
$statement->execute();
$products = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MS STORE</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <link rel="icon" href="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" type="image/png">

</head>

<body class="bg-[#FDEFEF] " style="background-image: url('assets/unicorn.png');">

    <?php include "navbar.php"; ?>


<?php
$username = $_SESSION['username'];
$queryCheckStore = "SELECT * FROM store_info WHERE username = ?";
$statementCheckStore = $pdo->prepare($queryCheckStore);
$statementCheckStore->execute([$username]);
$storeInfo = $statementCheckStore->fetch(PDO::FETCH_ASSOC);

if (!$storeInfo) {
    

    ?>
    <div class="bg-white border-4 mt-[80px]">
    <h1 class="text-xl font-bold mb-4 uppercase font-serif p-4  ">Informasi Toko</h1>
    
    <form method="post" action="" class="mb-8" enctype="multipart/form-data">
        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="store_name" class="block text-md  font-medium text-gray-700 w-2/12 mt-2" >Nama Toko</label>
            <input type="text" name="store_name" required class="border rounded px-3 py-2 w-11/12">
        </div>

        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="store_field" class="block text-sm font-medium text-gray-700 w-2/12">Bidang Toko:</label>
            <input type="text" name="store_field" required class="border rounded px-3 py-2 w-11/12">
        </div>

        <div class="mb-4 flex flex-warp mt-4 px-6">
    <label for="store_address" class="block text-sm font-medium text-gray-700 w-2/12">Alamat Toko:</label>
    <div class="w-11/12">
        <input type="text" name="street" required class="border rounded px-3 py-2 mb-2" placeholder="Jalan">
        <input type="text" name="city" required class="border rounded px-3 py-2 mb-2" placeholder="Kota">
        <input type="text" name="province" required class="border rounded px-3 py-2 mb-2" placeholder="Provinsi">
    </div>
</div>


        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="profile_store" class="block text-sm font-medium text-gray-700 w-2/12">Foto Toko:</label>
            <input type="file" name="profile_store" accept="image/*" class="border rounded px-3 py-2 w-11/12">
        </div>
        <div class="w-full ml-auto flex justify-end px-4">
            <button type="submit" name="tambah_info_toko" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Tambah Informasi Toko</button>
        </div>
    </form>
    </div>
    <?php
} else {
    $storePhoto = $storeInfo['profile_store'];
    ?>
    <div class="w-full mx-auto bg-white border-4 p-4 mt-[80px] mb-4">
    <h1 class="text-xl font-bold mb-4 uppercase font-serif ">Informasi Toko</h1>

    <div class="flex ">
        
        <img src="<?= $storePhoto ?>" alt="Store Profile" class="w-20 h-20 object-fit mr-4 my-[10px]">
        <div>
            <p class="mb-2">Toko: <?= $storeInfo['store_name'] ?></p>
            <p class="mb-2">Bidang: <?= $storeInfo['store_field'] ?></p>
            <p class="mb-2">Alamat: <?= $storeInfo['store_address'] ?></p>
        </div>
    </div>
    </div>
<?php
}
?>



<div class="bg-white border-4 mt-[30px]">
    <h2 class="text-2xl mb-4 p-4 uppercase font-serif font-semibold">Tambah Produk</h2>
    <form method="post" action="" enctype="multipart/form-data" class="mb-8">
        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="product_name" class="block text-sm font-medium text-gray-700 w-2/12">Nama Produk</label>
            <input type="text" name="product_name" required class="border rounded px-3  py-2 ml-auto  w-9/12">
        </div>

        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="product_price" class="block text-sm font-medium text-gray-700 w-2/12">Harga</label>
            <input type="number" name="product_price" required class="border rounded px-3 py-2 ml-auto  w-9/12">
        </div>

        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="product_description" class="block text-sm font-medium text-gray-700 w-2/12">Deskripsi</label>
            <textarea name="product_description" required class="border rounded  py-2 px-3  ml-auto w-9/12"></textarea>
        </div>

        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="product_stock" class="block text-sm font-medium text-gray-700 w-2/12">Stok</label>
            <input type="number" name="product_stock" required class="border rounded  py-2 px-3 ml-auto  w-9/12">
        </div>

        <div class="mb-4 flex flex-warp mt-4 px-6">
            <label for="product_photo" class="block text-sm font-medium text-gray-700 w-2/12">Foto Produk</label>
            <input type="file" name="product_photo" accept="image/*" required class="border rounded  ml-auto py-2 px-3  w-9/12">
        </div>



        <div class="w-full ml-auto flex justify-end px-4">
        <button type="submit" name="tambah_produk" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Tambah Produk</button>
        </div>
    </form>
    </div>

    <div class="p-4 bg-white border-4 mt-[30px]">
    <h2 class="text-2xl mb-4 uppercase font-serif font-semibold ">Daftar Produk</h2>
    <?php
        // Fetch products based on the logged-in user's store information
        $username = $_SESSION['username'];
        $queryProducts = "SELECT products.* FROM products JOIN store_info ON products.id_toko = store_info.id_toko WHERE store_info.username = ?";
        $statementProducts = $pdo->prepare($queryProducts);
        $statementProducts->execute([$username]);
        $products = $statementProducts->fetchAll(PDO::FETCH_ASSOC);
        ?>

<div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($products as $product) : ?>
            <div class="bg-white  shadow-md">
                <?php if ($product['photo']) : ?>
                    <img src="../barang/<?= $product['photo']; ?>" alt="<?= $product['name']; ?>" class="w-full h-[200px] fluid object-fit">
                <?php endif; ?>
                <div class="p-6">

                    <!-- Edit Product Form -->
                    <form method="post" action="">
                        <input type="hidden" name="edited_product_id" value="<?= $product['id_produk']; ?>">
                        <label for="edited_product_name" class="block text-sm font-medium text-gray-700">Edit Nama Produk:</label>
                        <input type="text" name="edited_product_name" value="<?= $product['name']; ?>" required class="border rounded px-3 py-2 w-full">

                        <label for="edited_product_price" class="block text-sm font-medium text-gray-700">Edit Harga:</label>
                        <input type="number" name="edited_product_price" value="<?= $product['price']; ?>" required class="border rounded px-3 py-2 w-full">

                        <label for="edited_product_description" class="block text-sm font-medium text-gray-700">Edit Deskripsi:</label>
                        <textarea name="edited_product_description" required class="border rounded px-3 py-2 w-full"><?= $product['description']; ?></textarea>

                        <label for="edited_product_stock" class="block text-sm font-medium text-gray-700">Edit Stok:</label>
                        <input type="number" name="edited_product_stock" value="<?= $product['stock']; ?>" required class="border rounded px-3 py-2 w-full">

                        <div class="w-full flex gap-2">
                        <button type="submit" name="edit_produk" class="bg-blue-500 hover:bg-blue-600 text-white font-bold text-[14px] py-2 px-4 rounded mt-2 w-1/2">Update Produk</button>
                        <input type="hidden" name="deleted_product_id" value="<?= $product['id_produk']; ?>">
                        <button type="submit" name="delete_produk" class="bg-red-500 hover:bg-red-600 text-white font-bold text-[14px] py-2 px-4 rounded mt-2 w-1/2">Hapus</button>
                        </div>
                    </form>


                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </div>

    </div>

</body>

</html>
