<?php
require_once '../includes/auth.php';
checkRole('user');

require_once '../config/db.php';
require_once '../includes/functions.php';

// generate csrf token if we don't have one yet
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];

// handle return action (POST only with CSRF check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {

    // verify csrf token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid request. Please try again.');
        redirect('rentals.php');
    }

    $rental_id = (int) $_POST['id'];

    // make sure this rental belongs to the user and is still active
    $stmt = $conn->prepare("SELECT id, equipment_id, quantity_rented, status
                            FROM rentals
                            WHERE id = ? AND user_id = ? AND status IN ('rented', 'overdue')");
    $stmt->bind_param("ii", $rental_id, $user_id);
    $stmt->execute();
    $rental = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$rental) {
        flashMessage('danger', 'Rental not found or already returned.');
        redirect('rentals.php');
    }

    // mark as returned
    $stmt = $conn->prepare("UPDATE rentals
                            SET return_date = CURDATE(), status = 'returned'
                            WHERE id = ?");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $stmt->close();

    // put stock back
    $stmt = $conn->prepare("UPDATE equipment
                            SET available_quantity = available_quantity + ?
                            WHERE id = ?");
    $stmt->bind_param("ii", $rental['quantity_rented'], $rental['equipment_id']);
    $stmt->execute();
    $stmt->close();

    flashMessage('success', 'Item returned successfully! Thank you.');
    redirect('rentals.php');
}

// auto-update overdue rentals
$stmt = $conn->prepare("UPDATE rentals
                         SET status = 'overdue'
                         WHERE user_id = ? AND status = 'rented' AND due_date < CURDATE()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// stats for the cards
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rentals WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_rentals = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rentals
                         WHERE user_id = ? AND status IN ('rented', 'overdue')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$currently_rented = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rentals
                         WHERE user_id = ? AND status = 'returned' AND return_date <= due_date");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$returned_on_time = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rentals
                         WHERE user_id = ? AND status = 'returned' AND return_date > due_date");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$returned_late = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// current rentals
$stmt = $conn->prepare("SELECT
                            rentals.id AS rental_id,
                            equipment.name AS equipment_name,
                            equipment.category,
                            rentals.quantity_rented,
                            rentals.rental_date,
                            rentals.due_date,
                            rentals.status,
                            DATEDIFF(rentals.due_date, CURDATE()) AS days_left
                         FROM rentals
                         JOIN equipment ON rentals.equipment_id = equipment.id
                         WHERE rentals.user_id = ?
                           AND rentals.status IN ('rented', 'overdue')
                         ORDER BY rentals.due_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_rentals = $stmt->get_result();
$stmt->close();

// rental history (returned items)
$stmt = $conn->prepare("SELECT
                            rentals.id AS rental_id,
                            equipment.name AS equipment_name,
                            equipment.category,
                            rentals.quantity_rented,
                            rentals.rental_date,
                            rentals.due_date,
                            rentals.return_date,
                            rentals.status
                         FROM rentals
                         JOIN equipment ON rentals.equipment_id = equipment.id
                         WHERE rentals.user_id = ?
                           AND rentals.status = 'returned'
                         ORDER BY rentals.return_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rental_history = $stmt->get_result();
$stmt->close();

require_once '../includes/header.php';
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-clipboard-check"></i> My Rentals</h2>
            <p class="text-muted mb-0">Track your current and past equipment rentals.</p>
        </div>
        <a href="browse.php" class="btn btn-sm btn-primary d-none d-md-inline-block">
            <i class="bi bi-search"></i> Browse Equipment
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary-soft"><i class="bi bi-collection"></i></div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $total_rentals; ?></h3>
                    <small class="text-muted">Total Rentals</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning-soft"><i class="bi bi-bag"></i></div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $currently_rented; ?></h3>
                    <small class="text-muted">Currently Rented</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success-soft"><i class="bi bi-check-circle"></i></div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $returned_on_time; ?></h3>
                    <small class="text-muted">On Time</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger-soft"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $returned_late; ?></h3>
                    <small class="text-muted">Returned Late</small>
                </div>
            </div>
        </div>
    </div>
</div>

<h5 class="fw-bold mb-3"><i class="bi bi-clipboard-data"></i> Current Rentals</h5>

<?php if ($current_rentals && $current_rentals->num_rows > 0): ?>
    <div class="table-responsive mb-4">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Rental Date</th>
                    <th>Due Date</th>
                    <th>Days Left / Overdue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $current_rentals->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><span class="text-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td><?php echo $row['quantity_rented']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['rental_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td>
                            <?php if ($row['days_left'] >= 0): ?>
                                <span class="text-success fw-bold">
                                    <?php echo $row['days_left']; ?> day<?php echo ($row['days_left'] != 1) ? 's' : ''; ?> left
                                </span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">
                                    <?php echo abs($row['days_left']); ?> day<?php echo (abs($row['days_left']) != 1) ? 's' : ''; ?> overdue
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'overdue'): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Rented</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="rentals.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to return this item?');">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="id" value="<?php echo $row['rental_id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-box-arrow-in-left"></i> Return
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="text-center py-5 mb-4">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-2 mb-0">No active rentals right now.</p>
        <a href="browse.php" class="btn btn-primary mt-3"><i class="bi bi-search"></i> Browse Equipment</a>
    </div>
<?php endif; ?>

<h5 class="fw-bold mb-3"><i class="bi bi-clock-history"></i> Rental History</h5>

<?php if ($rental_history && $rental_history->num_rows > 0): ?>
    <div class="table-responsive mb-4">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Rental Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $rental_history->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><span class="text-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td><?php echo $row['quantity_rented']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['rental_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['return_date'])); ?></td>
                        <td><span class="badge bg-secondary">Returned</span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="text-center py-5 mb-4">
        <i class="bi bi-clock" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-2 mb-0">No rental history yet.</p>
    </div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
