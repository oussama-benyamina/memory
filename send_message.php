<?php
session_start();
require_once 'includes/db.php';  // Assuming there's a db.php file for database connection

if (isset($_POST['message']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $message = $_POST['message'];
    
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    $stmt->close();
    $conn->close();
}


