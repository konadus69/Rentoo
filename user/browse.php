<?php
require_once '../includes/auth.php';
checkRole('user');

require_once '../config/db.php';
require_once '../includes/functions.php';

// csrf token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$action = isset($_GET['action']) ? $_GET['action'] : 'browse';

// handle rent form submission
if ($action === 'rent' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('browse.php');
    }

    $equipment_id = (int) $_GET['id'];
    $user_id      = $_SESSION['user_id'];
    $quantity     = (int) $_POST['quantity'];
    $duration     = (int) $_POST['duration'];

    $allowed_durations = [1, 3, 7, 14, 30];
    if (!in_array($duration, $allowed_durations)) {
        flashMessage('danger', 'Invalid rental duration selected.');
        redirect('browse.php?action=rent&id=' . $equipment_id);
    }

    if ($quantity < 1) {
        flashMessage('danger', 'Quantity must be at least 1.');
        redirect('browse.php?action=rent&id=' . $equipment_id);
    }

    // check if user hit their rental limit
    $stmt = $conn->prepare("SELECT COUNT(*) AS active_count FROM rentals WHERE user_id = ? AND status IN ('rented', 'overdue')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $active_count = $stmt->get_result()->fetch_assoc()['active_count'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT max_rentals FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $max_rentals = $stmt->get_result()->fetch_assoc()['max_rentals'];
    $stmt->close();

    if ($active_count >= $max_rentals) {
        flashMessage('danger', 'You have reached your maximum rental limit (' . $max_rentals . '). Please return an item before renting more.');
        redirect('browse.php?action=rent&id=' . $equipment_id);
    }

    // check stock
    $stmt = $conn->prepare("SELECT name, available_quantity FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $equipment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$equipment) {
        flashMessage('danger', 'Equipment not found.');
        redirect('browse.php');
    }

    if ($equipment['available_quantity'] < $quantity) {
        flashMessage('danger', 'Not enough stock available. Only ' . $equipment['available_quantity'] . ' unit(s) remaining.');
        redirect('browse.php?action=rent&id=' . $equipment_id);
    }

    $rental_date = date('Y-m-d');
    $due_date    = date('Y-m-d', strtotime('+' . $duration . ' days'));

    // insert rental
    $stmt = $conn->prepare("INSERT INTO rentals (user_id, equipment_id, quantity_rented, rental_date, due_date, status) VALUES (?, ?, ?, ?, ?, 'rented')");
    $stmt->bind_param("iisss", $user_id, $equipment_id, $quantity, $rental_date, $due_date);

    if ($stmt->execute()) {
        $stmt->close();

        // reduce available stock
        $stmt = $conn->prepare("UPDATE equipment SET available_quantity = available_quantity - ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $equipment_id);
        $stmt->execute();
        $stmt->close();

        flashMessage('success', 'You have successfully rented "' . htmlspecialchars($equipment['name']) . '"! Due back on ' . date('d M Y', strtotime($due_date)) . '.');
        redirect('dashboard.php');
    } else {
        $stmt->close();
        flashMessage('danger', 'Error processing your rental. Please try again.');
        redirect('browse.php?action=rent&id=' . $equipment_id);
    }
}

// fetch equipment details for the rent form
$rent_equipment = null;
if ($action === 'rent' && isset($_GET['id'])) {
    $rent_id = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $rent_id);
    $stmt->execute();
    $rent_equipment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$rent_equipment) {
        flashMessage('danger', 'Equipment not found.');
        redirect('browse.php');
    }

    if ($rent_equipment['available_quantity'] <= 0) {
        flashMessage('warning', 'This equipment is currently out of stock.');
        redirect('browse.php');
    }
}

// fetch equipment list with search/filter
if ($action === 'browse') {

    $search    = isset($_GET['search'])    ? trim($_GET['search'])    : '';
    $category  = isset($_GET['category'])  ? trim($_GET['category'])  : '';
    $condition = isset($_GET['condition']) ? trim($_GET['condition']) : '';

    // get categories for the dropdown
    $categories_result = $conn->query("SELECT DISTINCT category FROM equipment ORDER BY category ASC");
    $categories = [];
    while ($cat_row = $categories_result->fetch_assoc()) {
        $categories[] = $cat_row['category'];
    }

    // build query with filters
    $sql    = "SELECT * FROM equipment WHERE 1=1";
    $params = [];
    $types  = "";

    if (!empty($search)) {
        $sql      .= " AND name LIKE ?";
        $params[]  = "%" . $search . "%";
        $types    .= "s";
    }

    if (!empty($category)) {
        $sql      .= " AND category = ?";
        $params[]  = $category;
        $types    .= "s";
    }

    if (!empty($condition)) {
        $sql      .= " AND `condition` = ?";
        $params[]  = $condition;
        $types    .= "s";
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $equipment_result = $stmt->get_result();
    $equipment_count  = $equipment_result->num_rows;
}

require_once '../includes/header.php';
?>

<?php if ($action === 'rent' && $rent_equipment): ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-bag-plus"></i> Rent Equipment</h1>
    <a href="browse.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Browse
    </a>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Equipment Details</h5>
            </div>
            <div class="card-body">
                <h4><?php echo htmlspecialchars($rent_equipment['name']); ?></h4>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($rent_equipment['category']); ?></p>

                <?php
                // colour for condition badge
                switch ($rent_equipment['condition']) {
                    case 'new':  $badge = 'bg-success'; break;
                    case 'good': $badge = 'bg-primary'; break;
                    case 'fair': $badge = 'bg-warning text-dark'; break;
                    case 'poor': $badge = 'bg-danger'; break;
                    default:     $badge = 'bg-secondary';
                }
                ?>
                <p>
                    <strong>Condition:</strong>
                    <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($rent_equipment['condition']); ?></span>
                </p>

                <p>
                    <strong>Available:</strong>
                    <?php echo $rent_equipment['available_quantity']; ?> / <?php echo $rent_equipment['total_quantity']; ?> units
                </p>

                <?php if (!empty($rent_equipment['description'])): ?>
                    <p><strong>Description:</strong><br>
                    <?php echo nl2br(htmlspecialchars($rent_equipment['description'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-plus"></i> Rental Form</h5>
            </div>
            <div class="card-body">

                <form method="POST" action="browse.php?action=rent&id=<?php echo $rent_equipment['id']; ?>">

                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="equipment_name" class="form-label">Equipment</label>
                        <input type="text" class="form-control" id="equipment_name"
                               value="<?php echo htmlspecialchars($rent_equipment['name']); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantity" name="quantity"
                               min="1" max="<?php echo $rent_equipment['available_quantity']; ?>"
                               value="1" required>
                        <div class="form-text">
                            Maximum available: <?php echo $rent_equipment['available_quantity']; ?> unit(s)
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="duration" class="form-label">Rental Duration <span class="text-danger">*</span></label>
                        <select class="form-select" id="duration" name="duration" required>
                            <option value="1">1 Day</option>
                            <option value="3">3 Days</option>
                            <option value="7" selected>7 Days</option>
                            <option value="14">14 Days</option>
                            <option value="30">30 Days</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="text" class="form-control" id="due_date_preview" readonly>
                        <div class="form-text">
                            The item must be returned by this date.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Confirm Rental
                    </button>
                    <a href="browse.php" class="btn btn-outline-secondary ms-2">Cancel</a>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- update due date preview when duration changes -->
<script>
    function updateDueDate() {
        var days = parseInt(document.getElementById('duration').value);
        var dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + days);

        var options = { day: '2-digit', month: 'short', year: 'numeric' };
        document.getElementById('due_date_preview').value = dueDate.toLocaleDateString('en-GB', options);
    }

    updateDueDate();
    document.getElementById('duration').addEventListener('change', updateDueDate);
</script>

<?php else: ?>

<h1 class="mb-4"><i class="bi bi-search"></i> Browse Equipment</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="browse.php">
            <div class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label for="search" class="form-label">Search by Name</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="e.g. Canon, Laptop..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"
                                    <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="condition_filter" class="form-label">Condition</label>
                    <select class="form-select" id="condition_filter" name="condition">
                        <option value="">All Conditions</option>
                        <?php
                        $conditions = ['new', 'good', 'fair', 'poor'];
                        foreach ($conditions as $cond):
                        ?>
                            <option value="<?php echo $cond; ?>"
                                    <?php echo ($condition === $cond) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cond); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="browse.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

<p class="text-muted mb-3">
    <i class="bi bi-grid"></i> Showing <strong><?php echo $equipment_count; ?></strong> item<?php echo ($equipment_count !== 1) ? 's' : ''; ?>
    <?php if (!empty($search) || !empty($category) || !empty($condition)): ?>
        — filtered
    <?php endif; ?>
</p>

<?php if ($equipment_count > 0): ?>

    <div class="row">
        <?php while ($item = $equipment_result->fetch_assoc()): ?>

            <div class="col-md-6 col-lg-4 mb-4" data-equipment-id="<?php echo $item['id']; ?>">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">

                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>

                        <p class="text-muted mb-2">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($item['category']); ?>
                        </p>

                        <?php
                        switch ($item['condition']) {
                            case 'new':  $badge = 'bg-success'; break;
                            case 'good': $badge = 'bg-primary'; break;
                            case 'fair': $badge = 'bg-warning text-dark'; break;
                            case 'poor': $badge = 'bg-danger'; break;
                            default:     $badge = 'bg-secondary';
                        }
                        ?>
                        <p class="mb-2">
                            <span class="badge <?php echo $badge; ?>">
                                <?php echo ucfirst($item['condition']); ?>
                            </span>
                        </p>

                        <p class="mb-2">
                            <strong>Available:</strong>
                            <span class="availability-badge <?php echo ($item['available_quantity'] > 0) ? 'text-success' : 'text-danger'; ?>"
                                  data-total="<?php echo $item['total_quantity']; ?>">
                                <?php echo $item['available_quantity']; ?> / <?php echo $item['total_quantity']; ?>
                            </span>
                        </p>

                        <?php if (!empty($item['description'])): ?>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php
                                // truncate long descriptions
                                $desc = htmlspecialchars($item['description']);
                                echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                ?>
                            </p>
                        <?php else: ?>
                            <p class="card-text text-muted small flex-grow-1">
                                <em>No description available.</em>
                            </p>
                        <?php endif; ?>

                        <div class="mt-auto rent-action">
                            <?php if ($item['available_quantity'] > 0): ?>
                                <a href="browse.php?action=rent&id=<?php echo $item['id']; ?>"
                                   class="btn btn-primary w-100">
                                    <i class="bi bi-bag-plus"></i> Rent
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="bi bi-x-circle"></i> Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        <?php endwhile; ?>
    </div>

<?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
        <p class="text-muted mt-2 mb-0">No equipment found matching your criteria.</p>
        <?php if (!empty($search) || !empty($category) || !empty($condition)): ?>
            <a href="browse.php" class="btn btn-outline-primary mt-3">
                <i class="bi bi-x-circle"></i> Clear Filters
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}

require_once '../includes/footer.php';
?>
