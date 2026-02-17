<?php
/**
 * Header Include File
 *
 * This file is included at the top of every page.
 * It starts the session, outputs the HTML head, and renders the navbar.
 * The navbar links change depending on whether the user is logged in
 * and whether they are an admin or a regular user.
 */

// Start the session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Work out the base URL so links work from any subfolder ---
// This figures out where the project root is relative to the current page.
// For example, if we're in /admin/dashboard.php, base_url becomes "../"
// If we're in /index.php (root), base_url becomes "./"
$script_dir = dirname($_SERVER['SCRIPT_NAME']);   // e.g. "/rento/admin"
$root_dir   = dirname($_SERVER['SCRIPT_NAME'], substr_count($_SERVER['SCRIPT_NAME'], '/'));

// Simple approach: check if we're in a subfolder
$current_folder = basename(dirname($_SERVER['SCRIPT_FILENAME']));
if (in_array($current_folder, ['admin', 'user'])) {
    $base_url = '../';
} else {
    $base_url = './';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentO - Equipment Rental System</title>

    <!-- Bootstrap 5 CSS (loaded from CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (for nice icons in the navbar and elsewhere) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Our custom stylesheet -->
    <link href="<?php echo $base_url; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ======= NAVBAR ======= -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">

        <!-- Brand / Logo -->
        <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>index.php">
            <i class="bi bi-box-seam"></i> RentO
        </a>

        <!-- Hamburger button for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="navbarMain">

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- === Links shown when user IS logged in === -->
                <ul class="navbar-nav me-auto">

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <!-- Admin navigation links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/equipment.php">
                                <i class="bi bi-tools"></i> Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/users.php">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>

                    <?php else: ?>
                        <!-- Regular user navigation links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/browse.php">
                                <i class="bi bi-search"></i> Browse Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/rentals.php">
                                <i class="bi bi-clipboard-check"></i> My Rentals
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>

                <!-- Right side: user's name and logout -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                            <span class="badge bg-light text-primary ms-1">
                                <?php echo ucfirst($_SESSION['role']); ?>
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>

            <?php endif; ?>
            <!-- If not logged in, no links are shown — just the brand -->

        </div>
    </div>
</nav>

<!-- Flash messages (success/error alerts) -->
<div class="container mt-3">
    <?php
    // If the functions file is loaded, display any flash messages
    if (function_exists('displayFlash')) {
        displayFlash();
    }
    ?>
</div>

<!-- Main content container starts here -->
<div class="container mt-4">
