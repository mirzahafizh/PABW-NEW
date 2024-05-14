<?php
session_start();
include "user/config.php";


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
            header("Location: kurir/kurir.php");
            exit();
        } elseif ($role === 'admin') {
            header("Location: admin/admin.php?action=daftar_user");
            exit();
        }
        header("Location: user/berhasil_login.php");
        exit();

    }
}

// Mengecek apakah pengguna telah mengirimkan formulir login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil nilai yang dikirimkan oleh formulir
    $username = $_POST["username"];
    $password = $_POST["password"];
    $remember_me = isset($_POST["remember_me"]) ? true : false;

    // Query untuk memeriksa apakah informasi login yang diberikan cocok dengan data di database
    $sql = "SELECT username, password FROM tb_user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $hashed_password = $row["password"];

        // Verifikasi password
        if (password_verify($password, $hashed_password)) {
            // Login berhasil
            $_SESSION["username"] = $username;

            // Set cookie "Remember Me" jika dicentang
            if ($remember_me) {
                $token = bin2hex(random_bytes(16)); // Hasilkan token acak
                
                // Simpan token di database untuk identifikasi pengguna
                $token_sql = "UPDATE tb_user SET remember_token = ? WHERE username = ?";
                $token_stmt = $conn->prepare($token_sql);
                $token_stmt->bind_param("ss", $token, $username);
                $token_stmt->execute();

                // Set cookie pada sisi klien
                setcookie("remember_token", $token, time() + (30 * 24 * 60 * 60), "/"); // 30 hari kedaluwarsa
            }

            // Redirect ke halaman utama setelah login berhasil
            header("Location: user/berhasil_login.php");
            exit();
        } else {
            // Login gagal
            $error_message = "Username atau password salah. Silakan coba lagi.";
        }
    } else {
        // Login gagal
        $error_message = "Username atau password salah. Silakan coba lagi.";
    }
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM tb_user WHERE username='$username'");
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $hashed_password = $row['password'];
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $username;
            header('Location: user/berhasil_login.php');
            exit();
        } else {
        }
    } else {
    }
}

// Tutup koneksi ke database
$conn->close();
?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Add your custom styles here */
    </style>
    <script>
        // Function untuk menampilkan pesan kesalahan dalam bentuk popup
        function showError(errorMessage) {
            alert(errorMessage);
        }
    </script>
</head>

<body class="bg-gradient-to-b from-purple-700 to-purple-300 min-h-screen flex items-center justify-center font-serif">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-semibold text-center mb-6">Login</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm"
            class="space-y-4">
            <div class="flex flex-col space-y-1">
                <input type="text" name="username" id="username" placeholder="Username"
                    class="border border-gray-300 px-4 py-2 rounded-lg focus:outline-none focus:border-purple-500"
                    required>
            </div>
            <div class="flex flex-col space-y-1">
                <input type="password" name="password" id="password" placeholder="Password"
                    class="border border-gray-300 px-4 py-2 rounded-lg focus:outline-none focus:border-purple-500"
                    required>
            </div>
            <div class="flex items-center justify-between">
                <label for="remember_me" class="flex items-center">
                    <input type="checkbox" name="remember_me" id="remember_me" class="mr-2">
                    <span class="text-sm">Remember Me</span>
                </label>
                <a href="lupa_sandi.php" class="text-sm text-purple-600">Lupa Sandi?</a>
            </div>
            <button type="submit"
                name="login" class="bg-purple-600  text-white py-2 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-50 w-full">
                Login
            </button>
            <div class="text-center">
                <a href="registrasi.php" class="text-sm text-purple-600">Belum punya akun? Daftar disini</a>
            </div>
        </form>
        <?php
        // Menampilkan pesan kesalahan jika login gagal
        if (isset($error_message)) {
            // Memanggil JavaScript untuk menampilkan pesan kesalahan dalam bentuk popup
            echo '<script>showError("' . $error_message . '");</script>';
        }
        ?>
    </div>

    
</body>

<!-- ... (remaining code) ... -->

</html>
