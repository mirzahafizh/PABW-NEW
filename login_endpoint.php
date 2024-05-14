<?php
include "config.php";

// Check if the user has submitted the login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['username']) && isset($data['password'])) {
        $username = $data["username"];
        $password = $data["password"];

        // Query database untuk memeriksa informasi login
        $sql = "SELECT * FROM tb_user WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // Login berhasil
            $row = $result->fetch_assoc();
            $role = $row["role"];
            echo json_encode(["success" => true, "role" => $role]);
            exit();
        } else {
            // Login gagal
            echo json_encode(["success" => false, "message" => "Invalid username or password."]);
            exit();
        }
    } else {
        echo json_encode(["success" => false, "message" => "Username and password are required."]);
        exit();
    }
} else {
    http_response_code(404);
    exit();
}
?>
