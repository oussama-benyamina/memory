<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate password
    if (strlen($password) < 7 || !preg_match('/[A-Z]/', $password)) {
        $_SESSION['message'] = "Password must be at least 7 characters long and contain at least one uppercase letter.";
        header("Location: index.php");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Registration successful!";
    } else {
        $_SESSION['message'] = "Registration failed. Please try again.";
    }

    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit();
}
?>

