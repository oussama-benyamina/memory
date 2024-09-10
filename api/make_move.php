<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$game_id = $data['game_id'];
$player_id = $data['player_id'];
$action = $data['action'];

try {
    $conn->begin_transaction();

    // Fetch game details
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();

    if (!$game) {
        throw new Exception('Game not found');
    }

    if ($game['current_turn_id'] != $player_id) {
        throw new Exception('Not your turn');
    }

    $is_player1 = ($game['player1_id'] == $player_id);
    $match_column = $is_player1 ? 'player1_matches' : 'player2_matches';
    $opponent_id = $is_player1 ? $game['player2_id'] : $game['player1_id'];

    if ($action === 'flip') {
        $card_index = $data['card_index'];
        $stmt = $conn->prepare("INSERT INTO game_moves (game_id, player_id, card_index, action) VALUES (?, ?, ?, 'flip')");
        $stmt->bind_param("iii", $game_id, $player_id, $card_index);
        $stmt->execute();
    } elseif ($action === 'check_match') {
        $index1 = $data['index1'];
        $index2 = $data['index2'];
        $is_match = $data['is_match'];

        if ($is_match) {
            // Record the match
            $stmt = $conn->prepare("INSERT INTO game_moves (game_id, player_id, card_index, action) VALUES (?, ?, ?, 'match'), (?, ?, ?, 'match')");
            $stmt->bind_param("iiiiii", $game_id, $player_id, $index1, $game_id, $player_id, $index2);
            $stmt->execute();

            // Increment match count
            $stmt = $conn->prepare("UPDATE games SET $match_column = $match_column + 1 WHERE id = ?");
            $stmt->bind_param("i", $game_id);
            $stmt->execute();

            // Player keeps their turn, so we don't update current_turn_id
        } else {
            // Record the unflip
            $stmt = $conn->prepare("INSERT INTO game_moves (game_id, player_id, card_index, action) VALUES (?, ?, ?, 'unflip'), (?, ?, ?, 'unflip')");
            $stmt->bind_param("iiiiii", $game_id, $player_id, $index1, $game_id, $player_id, $index2);
            $stmt->execute();

            // Switch turns only if it's not a match
            $stmt = $conn->prepare("UPDATE games SET current_turn_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $opponent_id, $game_id);
            $stmt->execute();
        }

        // Check if the game is over
        $stmt = $conn->prepare("SELECT player1_matches, player2_matches FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $total_matches = $result['player1_matches'] + $result['player2_matches'];

        if ($total_matches == 25) { // 50 cards = 25 pairs
            $winner_id = ($result['player1_matches'] > $result['player2_matches']) ? $game['player1_id'] : $game['player2_id'];
            $stmt = $conn->prepare("UPDATE games SET status = 'finished', winner_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $winner_id, $game_id);
            $stmt->execute();
        }
    }

    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
