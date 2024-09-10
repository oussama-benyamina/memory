<?php
require_once('includes/db.php'); // Ensure the path to db.php is correct

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch users with pagination
$query = "SELECT id, username, email, role, last_login FROM users LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total number of users for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM users";
$totalResult = $conn->query($totalQuery);
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #007BFF;
            color: #fff;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input[type="text"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
        }

        .export-btn {
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }

        .export-btn:hover {
            background-color: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            position: relative;
        }

        th, td {
            padding: 15px;
            border: 1px solid #e0e0e0;
            text-align: left;
            transition: background-color 0.3s ease;
        }

        th {
            background-color: #007BFF;
            color: #ffffff;
            font-weight: bold;
            cursor: pointer;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        td a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
            transition: color 0.3s ease;
        }

        td a:hover {
            color: #0056b3;
        }

        .no-users {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
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
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 100%;
            position: relative;
        }

        .modal-content h2 {
            margin-top: 0;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #007BFF;
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

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            color: #007BFF;
            text-decoration: none;
            padding: 8px 12px;
            border: 1px solid #ddd;
            margin: 0 5px;
            border-radius: 5px;
        }

        .pagination a.current {
            background-color: #007BFF;
            color: #fff;
        }

        .pagination a:hover {
            background-color: #0056b3;
            color: #fff;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="admin.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
    </div>

    <div class="container">
        <h1>Admin Page</h1>

        <!-- Export Button -->
        <a href="export_users.php" class="export-btn">Export to CSV</a>

        <!-- Search bar -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search users...">
        </div>

        <!-- User table -->
        <table id="userTable">
            <thead>
                <tr>
                    <th data-sort="id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort="username">Username <i class="fas fa-sort"></i></th>
                    <th data-sort="email">Email <i class="fas fa-sort"></i></th>
                    <th data-sort="role">Role <i class="fas fa-sort"></i></th>
                    <th data-sort="last_login">Last Login <i class="fas fa-sort"></i></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Check if 'last_login' is NULL or empty and format accordingly
                        $lastLogin = !empty($row['last_login']) ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : 'Never';
                        echo "<tr data-id='{$row['id']}'>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['username']}</td>";
                        echo "<td>{$row['email']}</td>";
                        echo "<td>{$row['role']}</td>";
                        echo "<td>{$lastLogin}</td>";
                        echo "<td>
                                <a href='view_user.php?id={$row['id']}'>View</a>
                                <a href='#' class='edit-btn' data-id='{$row['id']}'>Edit</a>
                                <a href='#' class='delete-btn' data-id='{$row['id']}'>Delete</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='no-users'>No users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="admin.php?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'current' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Modal for confirmation -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2 id="modalTitle">Confirm Action</h2>
            <p id="modalMessage"></p>
            <div class="modal-buttons">
                <button id="confirmButton">Confirm</button>
                <button class="cancel" id="cancelButton">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('userTable');
            const tableRows = table.querySelectorAll('tbody tr');
            const modal = document.getElementById('confirmationModal');
            const confirmButton = document.getElementById('confirmButton');
            const cancelButton = document.getElementById('cancelButton');
            let confirmCallback = () => {};

            // Search functionality
            searchInput.addEventListener('input', function () {
                const filter = searchInput.value.toLowerCase();
                tableRows.forEach(row => {
                    const cells = row.getElementsByTagName('td');
                    let visible = false;
                    for (let i = 0; i < cells.length - 1; i++) { // Exclude actions column
                        if (cells[i].textContent.toLowerCase().includes(filter)) {
                            visible = true;
                            break;
                        }
                    }
                    row.style.display = visible ? '' : 'none';
                });
            });

            // Sort table functionality
            table.querySelectorAll('th').forEach(header => {
                header.addEventListener('click', function () {
                    const sortColumn = this.getAttribute('data-sort');
                    const rowsArray = Array.from(tableRows);
                    const sortedRows = rowsArray.sort((a, b) => {
                        const cellA = a.querySelector(`td:nth-child(${this.cellIndex + 1})`).textContent;
                        const cellB = b.querySelector(`td:nth-child(${this.cellIndex + 1})`).textContent;
                        return cellA.localeCompare(cellB, undefined, { numeric: true });
                    });

                    table.querySelector('tbody').innerHTML = '';
                    sortedRows.forEach(row => table.querySelector('tbody').appendChild(row));
                });
            });

            // Open modal for delete confirmation
            function openModal(title, message, callback) {
                modal.style.display = 'flex';
                document.getElementById('modalTitle').innerText = title;
                document.getElementById('modalMessage').innerText = message;
                confirmButton.onclick = () => {
                    callback();
                    modal.style.display = 'none';
                };
                cancelButton.onclick = () => modal.style.display = 'none';
                document.querySelector('.modal-close').onclick = () => modal.style.display = 'none';
                confirmCallback = callback;
            }

            // Handle delete button click
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-id');
                    openModal(
                        'Delete User',
                        `Are you sure you want to delete user with ID ${userId}?`,
                        function () {
                            window.location.href = `delete_user.php?id=${userId}`;
                        }
                    );
                });
            });

            // Handle edit button click
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-id');
                    window.location.href = `edit_user.php?id=${userId}`;
                });
            });
        });
    </script>

</body>
</html>
