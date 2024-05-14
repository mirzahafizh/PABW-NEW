<?php
include 'config.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['id'])) {
    $user_id = $_POST['id'];

    // Retrieve user data based on user_id
    $sql = "SELECT * FROM tb_user WHERE id = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User not found.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}

// Handle form submission to update user data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the necessary keys are set in $_POST
    if (isset($_POST['id'], $_POST['fullname'], $_POST['email'], $_POST['phone'], $_POST['username'], $_POST['role'])) {
        $user_id = $_POST['id'];
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $username = $_POST['username'];
        $role = $_POST['role'];

        // Update user data in the database
        $updateSql = "UPDATE tb_user SET fullname='$fullname', email='$email', phone='$phone', username='$username', role='$role' WHERE id=$user_id";

        if ($conn->query($updateSql) === TRUE) {
            echo '<script>alert("User data updated successfully");</script>';
        } else {
            echo '<script>alert("Error updating user data: ' . $conn->error . '");</script>';
        }
        
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Edit User</title>
</head>

<body class="bg-gray-200">

    <!-- Content -->
    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold mb-4">Edit User</h1>

        <!-- Edit User Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

            <label for="fullname" class="block mb-2">Full Name:</label>
            <input type="text" name="fullname" id="fullname" class="border p-2 mb-4" value="<?php echo $user['fullname']; ?>" required>

            <label for="email" class="block mb-2">Email:</label>
            <input type="email" name="email" id="email" class="border p-2 mb-4" value="<?php echo $user['email']; ?>" required>

            <label for="phone" class="block mb-2">Phone:</label>
            <input type="text" name="phone" id="phone" class="border p-2 mb-4" value="<?php echo $user['phone']; ?>" required>

            <label for="username" class="block mb-2">Username:</label>
            <input type="text" name="username" id="username" class="border p-2 mb-4" value="<?php echo $user['username']; ?>" required>

            <label for="username" class="block mb-2">Passowrd:</label>
            <input type="password" name="password" id="password" class="border p-2 mb-4" value="<?php echo $user['password']; ?>" required>

            <label for="role" class="block mb-2">Role:</label>
            <select name="role" id="role" class="border p-2 mb-4" required>
                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
            </select>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Update User</button>
        </form>
        <!-- End Edit User Form -->

    </div>
</body>

</html>


