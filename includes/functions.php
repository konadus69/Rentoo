<?php
/**
 * Helper Functions
 *
 * A collection of simple utility functions used throughout the application.
 * Include this file on any page where you need these helpers.
 *
 * Usage: require_once '../includes/functions.php';
 */

/**
 * Sanitize user input to prevent XSS attacks.
 * Trims whitespace and converts special characters to HTML entities.
 *
 * @param  string $data  The raw input string
 * @return string        The cleaned/safe string
 */
function sanitize($data) {
    $data = trim($data);                          // Remove extra whitespace
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');  // Convert special chars
    return $data;
}

/**
 * Redirect the user to a different page.
 *
 * @param string $url  The URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit(); // Always stop the script after redirecting
}

/**
 * Set a flash message in the session.
 * Flash messages are shown once and then cleared (e.g., "Item added successfully!").
 *
 * @param string $type     The Bootstrap alert type: 'success', 'danger', 'warning', 'info'
 * @param string $message  The message text to display
 */
function flashMessage($type, $message) {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Store the flash message in the session
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Display any flash message that's stored in the session, then clear it.
 * This is called in the header so messages appear at the top of the page.
 *
 * The message is shown as a Bootstrap alert with a dismiss button.
 */
function displayFlash() {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if there's a flash message waiting to be shown
    if (isset($_SESSION['flash'])) {
        $type    = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];

        // Output a Bootstrap alert
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo    $message;
        echo    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';

        // Clear the message so it only shows once
        unset($_SESSION['flash']);
    }
}
