<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch pending invitations
    $sql = "SELECT i.*, u.username FROM invitations i JOIN users u ON i.sender_id = u.id WHERE i.receiver_id = ? AND i.status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_invitations = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch active games
    $sql = "SELECT * FROM games WHERE (player1_id = ? OR player2_id = ?) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $active_games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'pending_invitations' => $pending_invitations,
        'active_games' => $active_games
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred']);
}
