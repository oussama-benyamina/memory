<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['invitation_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$invitation_id = $data['invitation_id'];
$user_id = $_SESSION['user_id'];

try {
    $conn->begin_transaction();

    // Get invitation details
    $stmt = $conn->prepare("SELECT * FROM invitations WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $invitation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invitation = $result->fetch_assoc();

    if (!$invitation) {
        throw new Exception('Invalid invitation');
    }

    // Create new game
    $stmt = $conn->prepare("INSERT INTO games (player1_id, player2_id, status, current_turn_id, game_mode) VALUES (?, ?, 'active', ?, ?)");
    $stmt->bind_param("iiis", $invitation['sender_id'], $user_id, $invitation['sender_id'], $invitation['game_mode']);
    $stmt->execute();
    $game_id = $conn->insert_id;

    // Update invitation status
    $stmt = $conn->prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $invitation_id);
    $stmt->execute();

    // Generate and shuffle cards
    $total_pairs = ($invitation['game_mode'] === 'visible_memory') ? 25 : 9;
    $cards = range(1, $total_pairs);
    $cards = array_merge($cards, $cards);
    shuffle($cards);

    // Store card positions in the database
    $stmt = $conn->prepare("UPDATE games SET card_positions = ? WHERE id = ?");
    $card_positions_json = json_encode($cards);
    $stmt->bind_param("si", $card_positions_json, $game_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'game_id' => $game_id]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
