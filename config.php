<?php
$host = "localhost";
$user = "root";
$password = "ellyELIUD2005"; // default password for XAMPP is empty
$database = "lms_db"; // replace with your actual database name

$conn = new mysqli($host, $user, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
