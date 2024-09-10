<?php
// Ensure the session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    // Fetch user profile picture
    $sql = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $profile_picture = $user['profile_picture'] ?: 'uploads/default_profile.png';
}
?>

<div class="navbar">
    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture"> 
   
    <div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin.php">Admin Dashboard</a>
        <?php endif; ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

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