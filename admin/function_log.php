<?php
// Start the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    // Get the user's role from the session
    $username = $_SESSION['username'];

    // Fungsi untuk mencatat aktivitas ke dalam log
    function logActivity($pdo, $activity, $username) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_log (timestamp, username, activity) VALUES (CURRENT_TIMESTAMP, ?, ?)");
            $stmt->execute([$username, $activity]);
        } catch (PDOException $e) {
            // Tangani kesalahan jika gagal menyimpan log ke dalam database
            die("Error logging activity: " . $e->getMessage());
        }
    }
    

    // Call the logActivity function here or wherever needed
    // logActivity($pdo, "Some activity", $username);
} else {
    // If the user is not logged in, you may want to handle this case
    // For example, redirect them to the login page
    header("Location: login.php");
    exit(); // Make sure to exit after redirection
}
?>
