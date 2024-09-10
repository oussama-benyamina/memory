<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Memory game La Plateforme</title>
  <link rel="stylesheet" href="css/index.css">

</head>

<body style="flex-direction: column;">
<?php if (isset($_SESSION['message'])): ?>
			<p class="notification"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
		<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
			<p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
		<?php endif; ?>
	
	<div class="logo">
		<img src="img/logo.png" alt="Logo"> <!-- Add your logo image here -->
	</div>
	<div class="main">  	
		
		<input type="checkbox" id="chk" aria-hidden="true">

			<div class="signup">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
				<form action="register.php" method="POST">
					<label for="chk" aria-hidden="true">Sign up</label>
					<input type="text" name="username" placeholder="Username" required>
					<input type="email" name="email" placeholder="Email" required>
					<input type="password" name="password" placeholder="Password" required>
					<button type="submit">Register</button>
					</form>
			</div>

			<div class="login">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
				<form action="login.php" method="POST">
					<label for="chk" aria-hidden="true">Login</label>
					<input type="text" name="username" placeholder="Username" required>
					<input type="password" name="password" placeholder="Password" required>
					<button type="submit">Login</button>
				</form>
			</div>
	</div>
</body>
</html>
<!-- partial -->
  
</body>
</html>
