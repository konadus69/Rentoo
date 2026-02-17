<?php
/**
 * Authentication Guard
 *
 * Include this file at the top of any page that requires login.
 * It checks whether the user is logged in (has a user_id in the session).
 * If not, it redirects them back to the login page.
 *
 * Usage:
 *   require_once '../includes/auth.php';          // Ensures user is logged in
 *   checkRole('admin');                            // Ensures user is an admin
 */

// Start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Check if the user is logged in ---
// If there's no user_id in the session, they're not logged in
if (!isset($_SESSION['user_id'])) {
    // Figure out the base URL (are we in a subfolder?)
    $current_folder = basename(dirname($_SERVER['SCRIPT_FILENAME']));
    if (in_array($current_folder, ['admin', 'user'])) {
        header("Location: ../index.php");
    } else {
        header("Location: index.php");
    }
    exit(); // Always exit after a redirect!
}

/**
 * Check if the logged-in user has the required role.
 * If they don't, redirect them to their own dashboard.
 *
 * @param string $requiredRole  The role needed to access the page ('admin' or 'user')
 */
function checkRole($requiredRole) {
    // If the user's role doesn't match what's required...
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {

        // Redirect them to the correct dashboard for their role
        $current_folder = basename(dirname($_SERVER['SCRIPT_FILENAME']));
        $prefix = in_array($current_folder, ['admin', 'user']) ? '../' : './';

        if ($_SESSION['role'] === 'admin') {
            header("Location: " . $prefix . "admin/dashboard.php");
        } else {
            header("Location: " . $prefix . "user/dashboard.php");
        }
        exit();
    }
}
