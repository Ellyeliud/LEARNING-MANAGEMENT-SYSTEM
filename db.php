<?php
$host = "localhost";
$user = "root";
$pass = "ellyELIUD2005";
$dbname = "lms_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
