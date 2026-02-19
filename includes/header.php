<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// work out base url so links work from subfolders
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$root_dir   = dirname($_SERVER['SCRIPT_NAME'], substr_count($_SERVER['SCRIPT_NAME'], '/'));

$current_folder = basename(dirname($_SERVER['SCRIPT_FILENAME']));
if (in_array($current_folder, ['admin', 'user'])) {
    $base_url = '../';
} else {
    $base_url = './';
}

// check for overdue rentals to show badge in navbar
$overdue_count = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    if ($_SESSION['role'] === 'admin') {
        $result = $conn->query("SELECT COUNT(*) AS total FROM rentals WHERE status = 'overdue'");
        if ($result) $overdue_count = $result->fetch_assoc()['total'];
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rentals WHERE user_id = ? AND status = 'overdue'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $overdue_count = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentO - Equipment Rental System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="<?php echo $base_url; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>index.php">
            <i class="bi bi-box-seam"></i> RentO
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <?php if (isset($_SESSION['user_id'])): ?>
                <ul class="navbar-nav me-auto">

                    <?php if ($_SESSION['role'] === 'admin'): ?>
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
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/rentals.php">
                                <i class="bi bi-clipboard-data"></i> Rentals
                                <?php if ($overdue_count > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $overdue_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                    <?php else: ?>
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
                                <?php if ($overdue_count > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $overdue_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>

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

        </div>
    </div>
</nav>

<!-- flash messages -->
<div class="container mt-3">
    <?php
    if (function_exists('displayFlash')) {
        displayFlash();
    }
    ?>
</div>

<div class="container mt-4">
