<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username']) && isset($_POST['game_mode'])) {
        // Send invitation
        $username = $_POST['username'];
        $game_mode = $_POST['game_mode'];
        
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($receiver = $result->fetch_assoc()) {
            $receiver_id = $receiver['id'];
            $sql = "INSERT INTO invitations (sender_id, receiver_id, game_mode, status) VALUES (?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $user_id, $receiver_id, $game_mode);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => "Invitation sent successfully!"]);
        } else {
            echo json_encode(['success' => false, 'error' => "User not found."]);
        }
        exit();
    } elseif (isset($_POST['invitation_id']) && isset($_POST['action'])) {
        // Handle invitation response
        $invitation_id = $_POST['invitation_id'];
        $action = $_POST['action'];
        
        if ($action == 'accept') {
            $sql = "UPDATE invitations SET status = 'accepted' WHERE id = ? AND receiver_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $invitation_id, $user_id);
            $stmt->execute();
            
            // Create a new game
            $sql = "SELECT sender_id, game_mode FROM invitations WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $invitation_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $invitation = $result->fetch_assoc();
            
            $sql = "INSERT INTO games (player1_id, player2_id, status, game_mode) VALUES (?, ?, 'active', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $invitation['sender_id'], $user_id, $invitation['game_mode']);
            $stmt->execute();
            
            $game_id = $conn->insert_id;
            echo json_encode(['success' => true, 'message' => "Invitation accepted. Game started!", 'game_id' => $game_id]);
        } elseif ($action == 'decline') {
            $sql = "UPDATE invitations SET status = 'declined' WHERE id = ? AND receiver_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $invitation_id, $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => "Invitation declined."]);
        }
        exit();
    }
}

// If not a POST request or invalid data, redirect to dashboard
header("Location: dashboard.php");
exit();
