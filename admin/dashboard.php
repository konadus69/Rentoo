<?php
require_once '../includes/auth.php';
checkRole('admin');

require_once '../config/db.php';
require_once '../includes/functions.php';

// auto-update overdue rentals so the counts are accurate
$conn->query("UPDATE rentals SET status = 'overdue' WHERE status = 'rented' AND due_date < CURDATE()");

// grab stats for the cards
$sql_equipment = "SELECT COUNT(*) AS total FROM equipment";
$result_equipment = $conn->query($sql_equipment);
$total_equipment = $result_equipment->fetch_assoc()['total'];

$sql_users = "SELECT COUNT(*) AS total FROM users WHERE role = 'user'";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total'];

$sql_active = "SELECT COUNT(*) AS total FROM rentals WHERE status = 'rented'";
$result_active = $conn->query($sql_active);
$active_rentals = $result_active->fetch_assoc()['total'];

$sql_overdue = "SELECT COUNT(*) AS total FROM rentals WHERE status = 'overdue'";
$result_overdue = $conn->query($sql_overdue);
$overdue_rentals = $result_overdue->fetch_assoc()['total'];

// -- chart data: rental status counts --
$rented_count = $active_rentals; // already got this above
$overdue_count = $overdue_rentals; // already got this above

$sql_returned = "SELECT COUNT(*) AS total FROM rentals WHERE status = 'returned'";
$result_returned = $conn->query($sql_returned);
$returned_count = $result_returned->fetch_assoc()['total'];

// -- chart data: equipment by category --
$sql_categories = "SELECT category, COUNT(*) AS count FROM equipment GROUP BY category";
$result_categories = $conn->query($sql_categories);

$category_labels_arr = [];
$category_data_arr = [];
while ($cat = $result_categories->fetch_assoc()) {
    $category_labels_arr[] = "'" . htmlspecialchars($cat['category']) . "'";
    $category_data_arr[] = $cat['count'];
}
$category_labels = implode(', ', $category_labels_arr);
$category_data = implode(', ', $category_data_arr);

// last 5 rentals for the table
$sql_recent = "SELECT
                    rentals.id,
                    users.name AS user_name,
                    equipment.name AS equipment_name,
                    rentals.quantity_rented,
                    rentals.rental_date,
                    rentals.due_date,
                    rentals.status
               FROM rentals
               JOIN users ON rentals.user_id = users.id
               JOIN equipment ON rentals.equipment_id = equipment.id
               ORDER BY rentals.rental_date DESC
               LIMIT 5";
$result_recent = $conn->query($sql_recent);

require_once '../includes/header.php';
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p class="text-muted mb-0">Here's what's happening with your rental system today.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="text-muted"><i class="bi bi-calendar3"></i> <?php echo date('l, d M Y'); ?></span>
        </div>
    </div>
</div>

<?php if ($overdue_rentals > 0): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
            <strong>Warning:</strong> There are <?php echo $overdue_rentals; ?> overdue rental(s) that need attention!
            <a href="rentals.php?status=overdue" class="alert-link ms-1">View overdue rentals</a>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-4">

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary-soft">
                    <i class="bi bi-tools"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $total_equipment; ?></h3>
                    <small class="text-muted">Total Equipment</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success-soft">
                    <i class="bi bi-people"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $total_users; ?></h3>
                    <small class="text-muted">Total Users</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning-soft">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $active_rentals; ?></h3>
                    <small class="text-muted">Active Rentals</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger-soft">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $overdue_rentals; ?></h3>
                    <small class="text-muted">Overdue Rentals</small>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Charts row -->
<div class="row mb-4">
    <div class="col-md-5 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Rental Status Breakdown</h6>
                <canvas id="rentalStatusChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-7 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Equipment by Category</h6>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history"></i> Recent Rentals</h5>
    <div class="d-flex gap-2">
        <a href="equipment.php" class="btn btn-sm btn-primary">
            <i class="bi bi-tools"></i> Manage Equipment
        </a>
        <a href="users.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-people"></i> Manage Users
        </a>
    </div>
</div>

<?php if ($result_recent && $result_recent->num_rows > 0): ?>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Equipment</th>
                    <th>Quantity</th>
                    <th>Rental Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_recent->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo $row['quantity_rented']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['rental_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td>
                            <?php
                            // badge colour based on status
                            switch ($row['status']) {
                                case 'returned':
                                    $badge_class = 'bg-success';
                                    break;
                                case 'rented':
                                    $badge_class = 'bg-warning text-dark';
                                    break;
                                case 'overdue':
                                    $badge_class = 'bg-danger';
                                    break;
                                default:
                                    $badge_class = 'bg-secondary';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <p class="text-muted mb-0">No rentals found yet.</p>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>

<!-- Chart.js scripts (after footer so Chart.js CDN is loaded) -->
<script>
// doughnut chart - rental status breakdown
new Chart(document.getElementById('rentalStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Rented', 'Overdue', 'Returned'],
        datasets: [{
            data: [<?php echo $rented_count; ?>, <?php echo $overdue_count; ?>, <?php echo $returned_count; ?>],
            backgroundColor: ['#f39c12', '#e74c3c', '#2ecc71']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// bar chart - equipment by category
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: [<?php echo $category_labels; ?>],
        datasets: [{
            label: 'Equipment Count',
            data: [<?php echo $category_data; ?>],
            backgroundColor: '#4DA8DA',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
