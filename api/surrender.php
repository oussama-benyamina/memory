<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$data = json_decode(file_get_contents('php://input'), true);

$game_id = $data['game_id'];
$player_id = $data['player_id'];

// Fetch game details
$sql = "SELECT * FROM games WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $game_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

if (!$game) {
    echo json_encode(['error' => 'Game not found']);
    exit();
}

$opponent_id = ($game['player1_id'] == $player_id) ? $game['player2_id'] : $game['player1_id'];

// Update game status to finished and set the opponent as the winner
$sql = "UPDATE games SET status = 'finished', winner_id = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $opponent_id, $game_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update game status']);
}
?>