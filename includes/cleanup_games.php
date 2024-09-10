<?php
require_once 'db.php';

function cleanupFinishedGames() {
    global $conn;
    
    $sql = "DELETE FROM games WHERE status = 'finished' AND created_at < NOW() - INTERVAL 1 DAY";
    $conn->query($sql);
    
    $sql = "DELETE FROM game_moves WHERE game_id NOT IN (SELECT id FROM games)";
    $conn->query($sql);
}