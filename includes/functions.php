<?php
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function generate_game_board() {
    $cards = range(1, 8);
    $cards = array_merge($cards, $cards); // Duplicate cards to create pairs
    shuffle($cards);
    return $cards;
}

function check_win_condition($game_id) {
    global $conn;
    
    // Implement win condition checking logic here
    // This is a placeholder implementation
    $sql = "SELECT * FROM game_moves WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $moves = $result->fetch_all(MYSQLI_ASSOC);
    
    $matched_pairs = 0;
    foreach ($moves as $move) {
        if ($move['is_match']) {
            $matched_pairs++;
        }
    }
    
    return $matched_pairs == 8; // All pairs matched
}
