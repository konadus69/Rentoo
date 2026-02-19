<?php
require_once '../includes/auth.php';
checkRole('user');

require_once '../config/db.php';
require_once '../includes/functions.php';

// auto-update overdue rentals
$sql_update_overdue = "UPDATE rentals
                       SET status = 'overdue'
                       WHERE status = 'rented'
                         AND due_date < CURDATE()
                         AND user_id = ?";
$stmt_overdue = $conn->prepare($sql_update_overdue);
$stmt_overdue->bind_param("i", $_SESSION['user_id']);
$stmt_overdue->execute();
$stmt_overdue->close();

// generate csrf token if we don't have one yet
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];

// count active rentals
$sql_active = "SELECT COUNT(*) AS total FROM rentals
               WHERE user_id = ? AND status = 'rented'";
$stmt_active = $conn->prepare($sql_active);
$stmt_active->bind_param("i", $user_id);
$stmt_active->execute();
$active_rentals = $stmt_active->get_result()->fetch_assoc()['total'];
$stmt_active->close();

// count overdue
$sql_overdue_count = "SELECT COUNT(*) AS total FROM rentals
                      WHERE user_id = ? AND status = 'overdue'";
$stmt_overdue_count = $conn->prepare($sql_overdue_count);
$stmt_overdue_count->bind_param("i", $user_id);
$stmt_overdue_count->execute();
$overdue_rentals = $stmt_overdue_count->get_result()->fetch_assoc()['total'];
$stmt_overdue_count->close();

// get max rentals for this user
$sql_max = "SELECT max_rentals FROM users WHERE id = ?";
$stmt_max = $conn->prepare($sql_max);
$stmt_max->bind_param("i", $user_id);
$stmt_max->execute();
$max_rentals = $stmt_max->get_result()->fetch_assoc()['max_rentals'];
$stmt_max->close();

$remaining_slots = $max_rentals - ($active_rentals + $overdue_rentals);

// get current rentals to show in the table
$sql_current = "SELECT
                    rentals.id AS rental_id,
                    equipment.name AS equipment_name,
                    equipment.category,
                    rentals.quantity_rented,
                    rentals.rental_date,
                    rentals.due_date,
                    rentals.status,
                    DATEDIFF(CURDATE(), rentals.due_date) AS days_overdue
                FROM rentals
                JOIN equipment ON rentals.equipment_id = equipment.id
                WHERE rentals.user_id = ?
                  AND rentals.status IN ('rented', 'overdue')
                ORDER BY rentals.due_date ASC";
$stmt_current = $conn->prepare($sql_current);
$stmt_current->bind_param("i", $user_id);
$stmt_current->execute();
$result_current = $stmt_current->get_result();

require_once '../includes/header.php';
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p class="text-muted mb-0">Here's an overview of your current rentals.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="text-muted"><i class="bi bi-calendar3"></i> <?php echo date('l, d M Y'); ?></span>
        </div>
    </div>
</div>

<div class="row mb-4">

    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary-soft">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $active_rentals; ?></h3>
                    <small class="text-muted">Active Rentals</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger-soft">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $overdue_rentals; ?></h3>
                    <small class="text-muted">Overdue Items</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success-soft">
                    <i class="bi bi-bag-plus"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $remaining_slots; ?></h3>
                    <small class="text-muted">Remaining Slots</small>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-clipboard-data"></i> Current Rentals</h5>
    <div class="d-flex gap-2">
        <a href="browse.php" class="btn btn-sm btn-primary">
            <i class="bi bi-search"></i> Browse Equipment
        </a>
        <a href="rentals.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clock-history"></i> Rental History
        </a>
    </div>
</div>

<?php if ($result_current && $result_current->num_rows > 0): ?>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Rental Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_current->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><span class="text-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td><?php echo $row['quantity_rented']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['rental_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td>
                            <?php if ($row['status'] === 'overdue'): ?>
                                <span class="badge bg-danger">Overdue</span>
                                <br>
                                <small class="text-danger fw-bold">
                                    <?php echo $row['days_overdue']; ?> day<?php echo ($row['days_overdue'] != 1) ? 's' : ''; ?> overdue
                                </small>
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
    <div class="text-center py-5">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-2 mb-0">No active rentals right now.</p>
        <a href="browse.php" class="btn btn-primary mt-3">
            <i class="bi bi-search"></i> Browse Equipment
        </a>
    </div>
<?php endif; ?>

<?php
$stmt_current->close();
require_once '../includes/footer.php';
?>

