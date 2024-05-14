<?php
include '../user/config.php';
require_once 'function_log.php';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $edit_username = $_POST['edit_username'];
    $edit_fullname = $_POST['edit_fullname'];
    $edit_email = $_POST['edit_email'];
    $edit_phone = $_POST['edit_phone'];
    $edit_role = $_POST['edit_role'];
    $edit_saldo_add = $_POST['edit_saldo']; // Jumlah saldo yang akan ditambahkan
    // Add more fields as needed

    // Retrieve existing saldo
    $stmt = $pdo->prepare("SELECT saldo FROM tb_user WHERE username = ?");
    $stmt->execute([$edit_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $existing_saldo = $user['saldo'];
        $new_saldo = $existing_saldo + $edit_saldo_add; // Tambahkan jumlah saldo baru ke saldo yang ada

        // Update saldo in the database
        $stmt = $pdo->prepare("UPDATE tb_user SET fullname = ?, email = ?, phone = ?, role = ?, saldo = ? WHERE username = ?");
        $stmt->execute([$edit_fullname, $edit_email, $edit_phone, $edit_role, $new_saldo, $edit_username]);

        // Log activity for updating user information
        $logMessage = "Updated user: $edit_username. ";

        // Check if saldo is edited and add it to the log message
        if ($edit_saldo_add !== $user['saldo']) {
            $logMessage .= "Add saldo: $edit_saldo_add. ";
        }


        // Log activity only if saldo or role is edited
        if ($edit_saldo_add !== $user['saldo']) {
            logActivity($pdo, $logMessage, $_SESSION['username']);
        }


        // Redirect back to the admin page after the update
        header("Location: admin.php?action=daftar_user");
        exit();
    } else {
        // Handle the case when the user is not found
        echo "User not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['username'])) {
    $username = $_GET['username'];

    // Retrieve user data based on the username
    $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Display the form for editing user data
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Edit User</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        </head>
        <body class="bg-gray-100 h-screen flex items-center justify-center">

        <form method="post" action="edit_user.php" class="bg-white p-8 shadow-lg border border-black mt-10 rounded-md w-96 mx-auto">
            <input type="hidden" name="edit_username" value="<?php echo $user['username']; ?>">

            <div class="mb-4">
                <label for="edit_fullname" class="block text-sm font-medium text-gray-600">Full Name:</label>
                <input type="text" name="edit_fullname" value="<?php echo $user['fullname']; ?>" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="mb-4">
                <label for="edit_email" class="block text-sm font-medium text-gray-600">Email:</label>
                <input type="email" name="edit_email" value="<?php echo $user['email']; ?>" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="mb-4">
                <label for="edit_phone" class="block text-sm font-medium text-gray-600">Phone:</label>
                <input type="text" name="edit_phone" value="<?php echo $user['phone']; ?>"
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="mb-4">
                <label for="edit_role" class="block text-sm font-medium text-gray-600">Role:</label>
                <select name="edit_role" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                    <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="pengguna" <?php if ($user['role'] === 'pengguna') echo 'selected'; ?>>Pengguna</option>
                    <option value="kurir" <?php if ($user['role'] === 'kurir') echo 'selected'; ?>>Kurir</option>
                    <!-- Tambahkan opsi lainnya sesuai kebutuhan -->
                </select>
            </div>


            <div class="mb-4">
                <label for="edit_saldo" class="block text-sm font-medium text-gray-600">Saldo (Tambah/Kurang):</label>
                <input type="number" name="edit_saldo" value="0" required
                       class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <button type="submit" name="update_user" class="bg-green-500 text-white py-2 px-4 rounded-md">Update User</button>
        </form>

        </body>
        </html>
        <?php
    } else {
        // Handle the case when the user is not found
        echo "User not found.";
    }
}
?>
