<?php
// helper functions

// just trim whitespace here — escaping happens in the templates when we echo stuff
function sanitize($data) {
    return trim($data);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

// store a one-time message to show on next page load
function flashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function displayFlash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['flash'])) {
        $type    = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];

        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo    $message;
        echo    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';

        unset($_SESSION['flash']);
    }
}
