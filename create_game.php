<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$game_mode = $_POST['game_mode'];
$user_id = $_SESSION['user_id'];

// Create a new game based on the selected mode
$stmt = $conn->prepare("INSERT INTO games (player1_id, mode, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("is", $user_id, $game_mode);
$stmt->execute();
$game_id = $conn->insert_id;

// Redirect to the game page
header("Location: game.php?id=$game_id");
exit();
