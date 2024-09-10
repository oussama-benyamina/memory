<?php
require_once('includes/db.php'); // Ensure the path to db.php is correct

// Set the filename and headers for CSV
$filename = "users_" . date("Ymd") . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open the output stream for writing CSV
$output = fopen('php://output', 'w');

// Write the header row to the CSV
fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Last Login']);

// Fetch all users from the database
$query = "SELECT id, username, email, role, last_login FROM users";
$result = $conn->query($query);

// Check if there are results
if ($result->num_rows > 0) {
    // Output each row of the data
    while ($row = $result->fetch_assoc()) {
        // Format the last_login column (if necessary)
        $last_login = $row['last_login'] ? $row['last_login'] : 'Never';
        fputcsv($output, [$row['id'], $row['username'], $row['email'], $row['role'], $last_login]);
    }
}

// Close the output stream
fclose($output);

// Close the database connection
$conn->close();
exit;
