<?php

require_once 'includes/db.php';

// No need to call session_start() here if it's already in config.php

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // If you want to track user status, first add the column to your database:
    // ALTER TABLE users ADD COLUMN status ENUM('online', 'offline') DEFAULT 'offline';
    
    // Then uncomment these lines:
    // $sql = "UPDATE users SET status = 'offline' WHERE id = ?";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("i", $user_id);
    // $stmt->execute();
    // $stmt->close();

    // Close the database connection
    $conn->close();

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();
}

// Redirect to the login page
header("Location: index.php");
exit();
