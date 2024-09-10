<?php
session_start();

require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if there's an active game for the user
$stmt = $conn->prepare("SELECT id FROM games WHERE (player1_id = ? OR player2_id = ?) AND status = 'active'");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if ($game) {
    echo json_encode(['game_id' => $game['id']]);
} else {
    echo json_encode(['game_id' => null]);
}
