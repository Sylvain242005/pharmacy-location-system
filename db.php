<?php
$host = "localhost";
$user = "root"; // XAMPP default
$pass = "";     // XAMPP default
$dbname = "pharmacy_systems";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
