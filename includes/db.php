<?php
$host = "localhost";
$username = "Nova";
$password = "nova1234";
$database = "nova_ecommerce";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>