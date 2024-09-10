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
    // Update the invitation status to declined
    $stmt = $conn->prepare("UPDATE invitations SET status = 'declined' WHERE id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $invitation_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not decline invitation or invitation not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
