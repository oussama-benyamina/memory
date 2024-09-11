<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$servername = "localhost:3306";  // Adresse IP de votre serveur MariaDB
$username = "memoryG";   // Nom d'utilisateur MariaDB
$password = "Weaver12345!3";         // Mot de passe MariaDB
$database = "konstantine-garozashvili_memory_game_db";    // Nom de la base de données

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $database);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
// echo "Connexion réussie à la base de données memory_game"; // Remove or comment this line

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
