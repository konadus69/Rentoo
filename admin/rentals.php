<?php
require_once '../includes/auth.php';
checkRole('admin');

require_once '../config/db.php';
require_once '../includes/functions.php';

// csrf token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// auto-update overdue rentals
$conn->query("UPDATE rentals SET status = 'overdue' WHERE status = 'rented' AND due_date < CURDATE()");

// handle mark as returned
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {

    // verify csrf token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid request. Please try again.');
        redirect('rentals.php');
    }

    $rental_id = (int) $_POST['id'];

    // grab the rental info — make sure it's actually active
    $stmt = $conn->prepare("SELECT id, equipment_id, quantity_rented, status
                            FROM rentals
                            WHERE id = ? AND status IN ('rented', 'overdue')");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $rental = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$rental) {
        flashMessage('danger', 'Rental not found or already returned.');
        redirect('rentals.php');
    }

    // mark as returned
    $stmt = $conn->prepare("UPDATE rentals SET return_date = CURDATE(), status = 'returned' WHERE id = ?");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $stmt->close();

    // put the stock back
    $stmt = $conn->prepare("UPDATE equipment SET available_quantity = available_quantity + ? WHERE id = ?");
    $stmt->bind_param("ii", $rental['quantity_rented'], $rental['equipment_id']);
    $stmt->execute();
    $stmt->close();

    flashMessage('success', 'Rental marked as returned successfully.');
    redirect('rentals.php');
}

// get filter values from GET params
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

// stats for the cards (these don't change with filters)
$total_rentals = $conn->query("SELECT COUNT(*) AS total FROM rentals")->fetch_assoc()['total'];
$active_rentals = $conn->query("SELECT COUNT(*) AS total FROM rentals WHERE status = 'rented'")->fetch_assoc()['total'];
$overdue_rentals = $conn->query("SELECT COUNT(*) AS total FROM rentals WHERE status = 'overdue'")->fetch_assoc()['total'];
$returned_rentals = $conn->query("SELECT COUNT(*) AS total FROM rentals WHERE status = 'returned'")->fetch_assoc()['total'];

// build the query based on filters
$sql = "SELECT
            rentals.id AS rental_id,
            users.name AS user_name,
            equipment.name AS equipment_name,
            rentals.quantity_rented,
            rentals.rental_date,
            rentals.due_date,
            rentals.return_date,
            rentals.status
        FROM rentals
        JOIN users ON rentals.user_id = users.id
        JOIN equipment ON rentals.equipment_id = equipment.id
        WHERE 1=1";

$params = [];
$types = '';

// filter by status
if ($filter_status !== 'all' && in_array($filter_status, ['rented', 'overdue', 'returned'])) {
    $sql .= " AND rentals.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

// filter by search (user name or equipment name)
if (!empty($filter_search)) {
    $sql .= " AND (users.name LIKE ? OR equipment.name LIKE ?)";
    $search_term = '%' . $filter_search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

$sql .= " ORDER BY rentals.rental_date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$rentals = $stmt->get_result();
$stmt->close();

require_once '../includes/header.php';
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-clipboard-data"></i> Rental Management</h2>
            <p class="text-muted mb-0">View and manage all equipment rentals across the system.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="text-muted"><i class="bi bi-calendar3"></i> <?php echo date('l, d M Y'); ?></span>
        </div>
    </div>
</div>

<!-- stat cards -->
<div class="row mb-4">

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary-soft">
                    <i class="bi bi-collection"></i>
                </div>
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

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stat-card-v2 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success-soft">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0 fw-bold"><?php echo $returned_rentals; ?></h3>
                    <small class="text-muted">Returned</small>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- filter form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="rentals.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search"
                       placeholder="Search by user or equipment name..."
                       value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="rented" <?php echo ($filter_status === 'rented') ? 'selected' : ''; ?>>Rented</option>
                    <option value="overdue" <?php echo ($filter_status === 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                    <option value="returned" <?php echo ($filter_status === 'returned') ? 'selected' : ''; ?>>Returned</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="rentals.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- rentals table -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-list-ul"></i> All Rentals</h5>
</div>

<?php if ($rentals && $rentals->num_rows > 0): ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Rental Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $rentals->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo $row['quantity_rented']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['rental_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td>
                            <?php if ($row['return_date']): ?>
                                <?php echo date('d M Y', strtotime($row['return_date'])); ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            switch ($row['status']) {
                                case 'rented':
                                    $badge_class = 'bg-warning text-dark';
                                    break;
                                case 'overdue':
                                    $badge_class = 'bg-danger';
                                    break;
                                case 'returned':
                                    $badge_class = 'bg-success';
                                    break;
                                default:
                                    $badge_class = 'bg-secondary';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'rented' || $row['status'] === 'overdue'): ?>
                                <form method="POST" action="rentals.php" style="display:inline;"
                                      onsubmit="return confirm('Mark this rental as returned?');">
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="id" value="<?php echo $row['rental_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-box-arrow-in-left"></i> Mark Returned
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-2 mb-0">No rentals found matching your filters.</p>
        <?php if ($filter_status !== 'all' || !empty($filter_search)): ?>
            <a href="rentals.php" class="btn btn-outline-primary mt-3">
                <i class="bi bi-arrow-counterclockwise"></i> Clear Filters
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
