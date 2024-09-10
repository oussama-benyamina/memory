<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '172.20.10.2');
define('DB_USER', 'linux-server');
define('DB_PASS', '010203');
define('DB_NAME', 'memory_game');



// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Europe/Paris');

