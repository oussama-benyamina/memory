<?php
include 'includes/db.php';  // Assuming there's a db.php file for database connection

$query = "SELECT chat_messages.message, chat_messages.timestamp, users.username 
          FROM chat_messages 
          JOIN users ON chat_messages.user_id = users.id 
          ORDER BY chat_messages.timestamp ASC";
          
$result = $conn->query($query);
$messages = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'username' => $row['username'],
            'message' => $row['message'],
            'timestamp' => $row['timestamp']
        ];
    }
}

echo json_encode($messages);
$conn->close();

