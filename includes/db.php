<?php
// Database connection parameters
$host = "localhost"; // Change if your database is on a different server
$username = "Nova";  // Replace with your database username
$password = "nova1234";      // Replace with your database password
$database = "nova_ecommerce";  // Replace with your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4
$conn->set_charset("utf8mb4");
?>