<?php
/**
 * Database Connection Configuration
 *
 * This file creates a connection to the MySQL database using mysqli.
 * Include this file at the top of any PHP page that needs database access.
 *
 * Usage: require_once 'config/db.php';
 * Then use $conn for your queries.
 *
 * XAMPP default: username is 'root' with no password.
 */

// --- Database credentials ---
$db_host = 'localhost';    // XAMPP runs MySQL on localhost
$db_user = 'root';         // Default XAMPP username
$db_pass = '';             // Default XAMPP password (empty)
$db_name = 'rento_db';    // Our database name

// --- Create the connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- Check if the connection worked ---
if ($conn->connect_error) {
    // If it fails, stop everything and show the error
    die("Database connection failed: " . $conn->connect_error);
}

// --- Set the character encoding to UTF-8 ---
// This makes sure special characters (accents, symbols, etc.) display correctly
$conn->set_charset("utf8mb4");
