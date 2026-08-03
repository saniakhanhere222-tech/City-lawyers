<?php
// ============================================================
// CONFIGURATION - Database + Site + Session
// ============================================================
// This file provides:
// 1. PDO database connection (MySQL, exceptions, FETCH_ASSOC)
// 2. Site constants: BASE_URL, SITE_NAME
// 3. Include functions.php for reusable helpers
// 4. Session start (if not already started)
//
// Usage: require_once 'includes/config.php';
// Provides: $conn (PDO), BASE_URL, SITE_NAME, session
//
// Security: Use prepared statements via PDO
// Related: includes/functions.php
// ============================================================

// Database Settings
$db_host = "localhost";
$db_name = "city_lawyers";
$db_user = "root";
$db_password = "";

// Create PDO Connection
try {
    $conn = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password
    );

    // PDO Error Mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch Associative Arrays by Default
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Site Configuration
define('BASE_URL', 'http://localhost/city-lawyers/');
define('SITE_NAME', 'CityLawyers');

//reusable functions for notifications 
require_once __DIR__ . '/functions.php';

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>