<?php
// Database configuration
$servername = "lrgs.ftsm.ukm.my";
$username = "a193635";
$password = "hugeredtiger";
$dbname = "a193635";

try {
    // Create a new PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set error reporting to exception mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: For testing connection
    // echo "Database connection successful!";

} catch (PDOException $e) {
    // Handle error if connection fails
    die("Connection failed: " . $e->getMessage());
}
?>