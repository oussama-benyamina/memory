<?php
require_once('includes/db.php'); // Ensure the path to db.php is correct

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // Sanitize input to avoid SQL injection

    // Fetch user details from the database
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .user-details {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
        }
        .user-details p {
            font-size: 18px;
            margin: 10px 0;
        }
        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007BFF;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<div class='user-details'>";
    echo "<h1>User Details</h1>";
    echo "<p><strong>ID:</strong> {$user['id']}</p>";
    echo "<p><strong>Username:</strong> {$user['username']}</p>";
    echo "<p><strong>Email:</strong> {$user['email']}</p>";
    echo "<p><strong>Role:</strong> {$user['role']}</p>";
    echo "<a href='admin.php'>Back to Admin Page</a>";
    echo "</div>";
} else {
    echo "<p>User not found.</p>";
}

$conn->close();
?>

</body>
</html>
