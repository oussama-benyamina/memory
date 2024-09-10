<?php
// Start the session at the beginning of the file
session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user profile picture
$sql = "SELECT profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profile_picture = $user['profile_picture'] ?: 'uploads/default_profile.png';

// Ensure that the session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    // Redirect to login or show an error message
    header('Location: login.php');
    exit();
}

// Fetch active games
$sql = "SELECT * FROM games WHERE (player1_id = ? OR player2_id = ?) AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$active_games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Clean up finished games
$sql = "DELETE FROM games WHERE status = 'finished'";
$conn->query($sql);

// Fetch pending invitations
$sql = "SELECT i.*, u.username FROM invitations i JOIN users u ON i.sender_id = u.id WHERE i.receiver_id = ? AND i.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_invitations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Define game modes
$game_modes = [
    'hidden_memory' => 'Hidden Memory Game',
    'visible_memory' => '50-Card Memory Game'
];

?>

<!DOCTYPE html>
<html lang="en">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Memory Card Game</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
    </style>
</head>
<body style="display: unset !important;">

<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        
        <h3>Active Games</h3>
        <ul id="active-games-list">
            <?php if (empty($active_games)): ?>
                <li>No active games.</li>
            <?php else: ?>
                <?php foreach ($active_games as $game): ?>
                    <li><a href="game.php?id=<?php echo htmlspecialchars($game['id']); ?>">Game #<?php echo htmlspecialchars($game['id']); ?></a></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <h3>Pending Invitations</h3>
        <ul id="pending-invitations-list">
            <?php if (empty($pending_invitations)): ?>
                <li>No pending invitations.</li>
            <?php else: ?>
                <?php foreach ($pending_invitations as $invitation): ?>
                    <li>
                        Invitation from <?php echo htmlspecialchars($invitation['username']); ?>
                        <button onclick="acceptInvitation(<?php echo $invitation['id']; ?>)">Accept</button>
                        <button onclick="declineInvitation(<?php echo $invitation['id']; ?>)">Decline</button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        
        <h3>Invite a Player</h3>
        <form id="invite-form">
            <select name="game_mode" required>
                <option value="hidden_memory">Hidden Memory Game</option>
                <option value="visible_memory">50-Card Memory Game</option>
            </select>
            <input type="text" name="username" placeholder="Enter username" required>
            <button type="submit">Send Invitation</button>
        </form>
        
        <!-- Chat Icon -->
        <div id="chat-icon">
            <i class="fas fa-comment-dots"></i>
            
        </div>

        <!-- Chat Section -->
        <div id="chat-container">
            <div id="chat-messages"></div>
            <form id="chat-form">
                <input type="text" id="chat-input" placeholder="Type your message here..." autocomplete="off" />
                <button type="submit">Send</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    
    function checkForUpdates() {
        fetch('api/check_updates.php')
            .then(response => response.text())
            .then(text => {
                console.log('Raw response:', text);
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                if (data.pending_invitations) {
                    updatePendingInvitations(data.pending_invitations);
                }
                if (data.active_games) {
                    updateActiveGames(data.active_games);
                }
                if (data.unread_messages) {
                    updateChatNotification(data.unread_messages);
                }
            })
            .catch(error => console.error('Error checking for updates:', error));
    }

    function updatePendingInvitations(invitations) {
        const invitationsList = document.getElementById('pending-invitations-list');
        if (invitations.length === 0) {
            invitationsList.innerHTML = '<li>No pending invitations.</li>';
        } else {
            invitationsList.innerHTML = '';
            invitations.forEach(invitation => {
                const listItem = document.createElement('li');
                listItem.innerHTML = `
                    Invitation from ${invitation.username}
                    <button onclick="acceptInvitation(${invitation.id})">Accept</button>
                    <button onclick="declineInvitation(${invitation.id})">Decline</button>
                `;
                invitationsList.appendChild(listItem);
            });
        }
        console.log('Updated invitations:', invitations);
    }

    function updateActiveGames(games) {
        const gamesList = document.getElementById('active-games-list');
        if (games.length === 0) {
            gamesList.innerHTML = '<li>No active games.</li>';
        } else {
            gamesList.innerHTML = '';
            games.forEach(game => {
                const listItem = document.createElement('li');
                listItem.innerHTML = `<a href="game.php?id=${game.id}">Game #${game.id}</a>`;
                gamesList.appendChild(listItem);
            });
        }
        console.log('Updated active games:', games);
    }

    function updateChatNotification(count) {
        const notification = document.getElementById('chat-notification');
        notification.textContent = count;
        notification.style.display = count > 0 ? 'flex' : 'none';
    }

    function acceptInvitation(invitationId) {
        fetch('api/accept_invitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ invitation_id: invitationId })
        })
        .then(response => response.text())
        .then(text => {
            console.log('Raw server response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Invitation accepted. Redirecting to game...');
                    window.location.href = `game.php?id=${data.game_id}`;
                } else {
                    console.error('Error accepting invitation:', data.error);
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
            }
        })
        .catch(error => console.error('Fetch error:', error));
    }

    function declineInvitation(invitationId) {
        fetch('api/decline_invitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ invitation_id: invitationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                checkForUpdates(); // Refresh the invitations list
            } else {
                console.error('Error declining invitation:', data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    document.getElementById('invite-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('invite.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Invitation sent successfully!');
            } else {
                alert('Error sending invitation: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Check for updates every 5 seconds
    setInterval(checkForUpdates, 5000);

    function checkForGameStart() {
        fetch('api/check_game_start.php')
            .then(response => response.json())
            .then(data => {
                if (data.game_id) {
                    window.location.href = `game.php?id=${data.game_id}`;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Check for game start every 5 seconds
    setInterval(checkForGameStart, 5000);

    // Also check immediately when the page loads
    checkForUpdates();

    // Chat Script
    // Function to fetch and display messages
    function fetchMessages() {
        $.ajax({
            url: 'fetch_messages.php',
            method: 'GET',
            success: function(data) {
                const messages = JSON.parse(data);
                let chatContent = '';
                messages.forEach(function(msg) {
                    chatContent += '<div><strong>' + msg.username + ':</strong> ' + msg.message + 
                                   ' <small>(' + msg.timestamp + ')</small></div>';
                });
                $('#chat-messages').html(chatContent);
            }
        });
    }

    // Fetch messages every 2 seconds
    setInterval(fetchMessages, 2000);

    // Send message on form submission
    $('#chat-form').submit(function(e) {
        e.preventDefault();
        const message = $('#chat-input').val();
        if (message.trim() !== '') {
            $.ajax({
                url: 'send_message.php',
                method: 'POST',
                data: { message: message },
                success: function(response) {
                    $('#chat-input').val('');
                    fetchMessages();
                }
            });
        }
    });

    // Toggle chat container visibility
    $('#chat-icon').click(function() {
        $('#chat-container').toggle();
    });
    </script>
</body>
</html>
