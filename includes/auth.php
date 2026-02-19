<?php
// auth guard - include this on pages that need login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $current_folder = basename(dirname($_SERVER['SCRIPT_FILENAME']));
    if (in_array($current_folder, ['admin', 'user'])) {
        header("Location: ../index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// check if user has the right role
function checkRole($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {

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
