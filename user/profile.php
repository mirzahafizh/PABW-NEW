<?php
session_start();

// Periksa jika pengguna belum login, maka arahkan ke halaman login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Sisipkan file koneksi ke database
include "config.php";

// Ambil informasi pengguna dari sesi
$username = $_SESSION['username'];
$phone = '';
$email = '';
$fullname = '';
$profile_image = '';
$address = ''; // Tambahkan variabel untuk alamat

// Ambil email, lokasi foto profil, dan alamat berdasarkan username
$stmt = $pdo->prepare("SELECT email, profile_image, phone, fullname, address FROM tb_user WHERE username = ?");
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $email = $row['email'];
    $profile_image = $row['profile_image'];
    $phone = $row['phone'];
    $fullname = $row['fullname'];
    $address = $row['address']; // Simpan alamat ke dalam variabel
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

// Handle file upload logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/'; // Directory to store uploaded files
    $uploadFile = $uploadDir . basename($_FILES['profile_image']['name']);
    $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES['profile_image']['tmp_name']);
    if ($check !== false) {
        // Check if file already exists
        if (file_exists($uploadFile)) {
            echo '<script>alert("Sorry, file already exists.");</script>';
        } elseif ($_FILES['profile_image']['size'] > 500000) { // Check file size (500KB)
            echo '<script>alert("Sorry, your file is too large.");</script>';
        } elseif ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") { // Allow certain file formats
            echo '<script>alert("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");</script>';
        } else {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                // Update the profile_image field in the database with the new file location
                $updateImageStmt = $pdo->prepare("UPDATE tb_user SET profile_image = ? WHERE username = ?");
                $updateImageStmt->execute([$uploadFile, $username]);

                // Redirect to avoid re-uploading on page refresh
                echo '<script>alert("Success uploading file.");</script>';
                header("Location: profile.php");
                exit();
            } else {
                echo '<script>alert("Error uploading file.");</script>';
            }
        }
    } else {
        echo '<script>alert("File is not an image.");</script>';
    }
}

// Handle address update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $newAddress = $_POST['address'];

    // Update the address field in the database
    $updateAddressStmt = $pdo->prepare("UPDATE tb_user SET address = ? WHERE username = ?");
    $updateAddressStmt->execute([$newAddress, $username]);

    // Refresh the page
    header("Location: profile.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <link rel="icon" href="../assets/DALL_E-2024-05-15-00.26.01-Design-a-logo-for-_MS-Store_-removebg-preview.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-[#FDEFEF] font-serif" style="background-image: url('assets/unicorn.png');">

    <?php include "navbar.php"; ?>

    <div class="container mx-auto py-8 mt-20">
        <div class="max-w-md mx-auto bg-white p-8 rounded-md shadow-md">
            <!-- Foto profil -->
            <div class="text-center">
                <form action="" method="post" enctype="multipart/form-data">
                    <?php if ($profile_image): ?>
                        <img src="../uploads/<?php echo basename($profile_image); ?>" alt="User Avatar" class="w-24 h-24 mx-auto rounded-full mb-4">
                    <?php else: ?>
                        <img src="../assets/gg_profile.png" alt="User Avatar" class="w-24 h-24 mx-auto rounded-full mb-4">
                    <?php endif; ?>
                    <div class="flex items-center mb-2 border-2 p-2">
                        <input type="file" name="profile_image" accept="image/*" class="block mr-2 ">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none">
                            Upload
                        </button>
                    </div>
                </form>
                <h2 class="text-xl font-semibold mb-2"><?php echo $fullname; ?></h2>
                <p class="text-gray-600 mb-4"><?php echo $email; ?> <a href="ubah_email.php" class="text-blue-400">ubah</a></p>
                <p class="text-gray-600 mb-4"><?php echo $phone; ?> <a href="ubah_telepon.php" class="text-blue-400">ubah</a></p>

                <!-- Display the address and form to update it -->
                <form action="" method="post">
                    <p class="text-gray-600 mb-4">
                        <?php if (!empty($address)): ?>
                            <?php echo $address; ?> <a href="ubah_alamat.php" class="text-blue-400">ubah</a>
                        <?php else: ?>
                            <input type="text" name="address" placeholder="Tambahkan alamat" class="border rounded px-3 py-2">
                            <button type="submit" name="update_address" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none">
                                Tambahkan
                            </button>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
