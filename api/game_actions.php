<?php
session_start();

require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data['action'] === 'game_over') {
    $game_id = $data['game_id'];
    $player_id = $data['player_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Update the game status and set the winner
        $sql = "UPDATE games SET status = 'finished', winner_id = ? WHERE id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $player_id, $game_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // This player was the first to finish
            $winner = $player_id;
        } else {
            // The game was already finished by the other player
            $sql = "SELECT winner_id FROM games WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $game_data = $result->fetch_assoc();
            $winner = $game_data['winner_id'];
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'winner' => $winner]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
