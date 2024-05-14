<?php
session_start();

// Periksa apakah pengguna belum login, jika belum, arahkan ke halaman login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
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
    header("Location: login.php");
    exit();
}

// Ambil data produk dari database
$stmtProducts = $pdo->query("SELECT * FROM products ORDER BY id_produk DESC LIMIT 8");
$products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

// Check if the search parameter is set in the URL
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];

    // Use a prepared statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :searchTerm");
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();

    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MS STORE - Search</title>
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
<body class="bg-[#FDEFEF] " style="background-image: url('assets/unicorn.png');">

    <?php include "navbar.php"; ?>
    <div class="container mx-auto font-serif p-6 mt-[90px]">
        <h2 class="text-2xl font-bold mb-4 ">Search Results for "<?php echo htmlspecialchars($searchTerm); ?>"</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php
            // Check if $searchResults is not null before looping through it
            if ($searchResults !== null) {
                // Loop through search results and display them
                foreach ($searchResults as $result) {
                    // Wrap the card content in an anchor tag
                    echo '<a href="detail_produk.php?id=' . $result['id_produk'] . '" class="bg-white shadow-lg block  mb-4 transition duration-300 transform hover:scale-105">';
                    echo '<img src="../barang/' . $result['photo'] . '" alt="Product Image" class="w-full h-40 object-fit mb-4">';
                    echo '<h3 class="text-xl font-bold mb-4 px-4">' . $result['name'] . '</h3>';
                    echo '<p class="text-gray-800 mb-2 px-4">' . formatRupiah($result['price']) . '</p>';
                    echo '<h3 class="text-sm text-gray-400 mb-1 px-4">' . $result['store_name'] . '</h3>';
                    // Add any additional information or buttons here
                    echo '</a>';
                }
            } else {
                echo '<p>No results found.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>
