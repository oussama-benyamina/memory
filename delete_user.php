<?php
require_once('includes/db.php'); // Ensure the path to db.php is correct

// Check if an ID is provided
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // Sanitize input to avoid SQL injection

    // Fetch user details from the database for confirmation
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo "<p>User not found.</p>";
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Begin a transaction
        $conn->begin_transaction();

        try {
            // Delete related records from the games table
            $query = "DELETE FROM games WHERE player1_id = ? OR player2_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete related records from the invitations table
            $query = "DELETE FROM invitations WHERE receiver_id = ? OR sender_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // Then delete user from the database
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Commit the transaction
            $conn->commit();

            // Redirect back to admin page after deletion
            header("Location: admin.php");
            exit;
        } catch (Exception $e) {
            // Rollback the transaction if something fails
            $conn->rollback();
            echo "Failed to delete user: " . htmlspecialchars($e->getMessage());
        }
    }
} else {
    echo "<p>No user ID provided.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }
        p {
            font-size: 1rem;
            color: #555;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            color: #fff;
            transition: background-color 0.3s ease;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .btn-back {
            background-color: #007BFF;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            transition: opacity 0.3s ease;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 100%;
            position: relative;
            transform: translateY(-50px);
            transition: transform 0.3s ease;
        }
        .modal-content.show {
            transform: translateY(0);
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #007BFF;
            transition: color 0.3s ease;
        }
        .modal-close:hover {
            color: #0056b3;
        }
        .modal-buttons {
            text-align: right;
            margin-top: 20px;
        }
        .modal-buttons button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }
        .modal-buttons button:hover {
            background-color: #0056b3;
        }
        .modal-buttons button.cancel {
            background-color: #6c757d;
        }
        .modal-buttons button.cancel:hover {
            background-color: #5a6268;
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left: 4px solid #007BFF;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Delete User</h1>
    <p>Are you sure you want to delete the following user?</p>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($user['id']); ?></p>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>

    <button class="btn btn-delete" id="deleteBtn">Delete User</button>
    <a href="admin.php" class="btn btn-back">Back to Admin Page</a>
</div>

<!-- Modal for confirmation -->
<div id="confirmationModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this user?</p>
        <div class="modal-buttons">
            <button id="confirmButton">Confirm</button>
            <button class="cancel" id="cancelButton">Cancel</button>
        </div>
    </div>
</div>

<!-- Spinner for loading indication -->
<div id="spinner" class="spinner" style="display: none;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteBtn = document.getElementById('deleteBtn');
        const modal = document.getElementById('confirmationModal');
        const confirmButton = document.getElementById('confirmButton');
        const cancelButton = document.getElementById('cancelButton');
        const modalClose = document.querySelector('.modal-close');
        const spinner = document.getElementById('spinner');

        // Show the confirmation modal
        deleteBtn.addEventListener('click', function () {
            modal.style.display = 'flex';
            setTimeout(() => modal.querySelector('.modal-content').classList.add('show'), 10);
        });

        // Hide the modal
        function hideModal() {
            modal.querySelector('.modal-content').classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        modalClose.addEventListener('click', hideModal);
        cancelButton.addEventListener('click', hideModal);

        // Confirm deletion
        confirmButton.addEventListener('click', function () {
            modal.style.display = 'none';
            spinner.style.display = 'block';
            // Submit the form to delete the user
            const form = document.createElement('form');
            form.method = 'post';
            form.action = '';
            document.body.appendChild(form);
            form.submit();
        });
    });
</script>

</body>
</html>
