<?php
// Database connection using MySQLi for WESSH enrollment system
// This file establishes a connection to the MySQL database using MySQLi extension

// Define database credentials (use environment variables for security in production)
// Fallback to defaults for local XAMPP development
$servername = getenv('DB_HOST') ?: "localhost";  // Database server (localhost for XAMPP)
$username = getenv('DB_USER') ?: "root";         // Default XAMPP MySQL username
$password = getenv('DB_PASS') ?: "";             // Default XAMPP MySQL password (empty)
$dbname = getenv('DB_NAME') ?: "wessh_db";       // Database name as per schema

// Optional: Define other variables if expected from external sources (prevents undefined warnings)
$email = isset($email) ? $email : '';  // Example: If $email is passed from another file

// Pre-check: Ensure MySQL is likely running (basic port check)
$mysql_running = @fsockopen($servername, 3306, $errno, $errstr, 1);  // Timeout 1 second
if (!$mysql_running) {
    error_log("MySQL server not accessible on $servername:3306. Error: $errstr ($errno)");
    die("Database server is unavailable. Please check if MySQL is running in XAMPP.");
}
fclose($mysql_running);

// Create connection with retry logic and timeout
$maxRetries = 3;
$retryDelay = 2; // seconds
$connectTimeout = 10; // seconds

$conn = null;
for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $connectTimeout);
    if ($conn->real_connect($servername, $username, $password, $dbname)) {
        break;
    }
    error_log("Database connection attempt $attempt failed: " . mysqli_connect_error() . " | Host: $servername | User: $username | DB: $dbname");
    if ($attempt < $maxRetries) {
        sleep($retryDelay);
    }
    $conn = null;
}

// Check connection
if (!$conn || $conn->connect_error) {
    // Log detailed error for debugging
    error_log("Database connection failed after $maxRetries attempts: " . ($conn ? $conn->connect_error : mysqli_connect_error()) . " | Host: $servername | User: $username | DB: $dbname");
    die("Sorry, we are experiencing technical difficulties. Please try again later.");
}

// Set charset to utf8 for proper character encoding
if (!$conn->set_charset("utf8")) {
    error_log("Error loading character set utf8: " . $conn->error);
    // Optional: Die or continue
    // die("Charset error. Please contact support.");
}

// Optional: Set timezone if needed (adjust as per your requirements)
$conn->query("SET time_zone = '+08:00'");  // Example for Philippine timezone

// Security reminder: Always use prepared statements for queries.
// Example: $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?"); $stmt->bind_param("s", $email);

// Note: Do not close the connection here; it will be closed at the end of the script.
?>