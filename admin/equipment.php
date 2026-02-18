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

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// handle delete — uses POST so it can't be triggered by a simple link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {

    // check csrf token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('equipment.php');
    }

    $equipment_id = (int) $_POST['id'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS active_count FROM rentals WHERE equipment_id = ? AND status = 'rented'");
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $active_count = $result->fetch_assoc()['active_count'];
    $stmt->close();

    if ($active_count > 0) {
        flashMessage('danger', 'Cannot delete this equipment — it has active rentals. Return all items first.');
    } else {
        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->bind_param("i", $equipment_id);

        if ($stmt->execute()) {
            flashMessage('success', 'Equipment deleted successfully.');
        } else {
            flashMessage('danger', 'Error deleting equipment. It may have rental history — please check.');
        }
        $stmt->close();
    }

    redirect('equipment.php');
}

// handle add
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('equipment.php?action=add');
    }

    $name          = sanitize($_POST['name']);
    $category      = sanitize($_POST['category']);
    $serial_number = sanitize($_POST['serial_number']);
    $condition     = sanitize($_POST['condition']);
    $total_qty     = (int) $_POST['total_quantity'];
    $description   = sanitize($_POST['description']);

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Equipment name is required.';
    }
    if (empty($category)) {
        $errors[] = 'Category is required.';
    }
    if (empty($serial_number)) {
        $errors[] = 'Serial number is required.';
    }
    if (!in_array($condition, ['new', 'good', 'fair', 'poor'])) {
        $errors[] = 'Invalid condition selected.';
    }
    if ($total_qty < 1) {
        $errors[] = 'Total quantity must be at least 1.';
    }

    if (!empty($errors)) {
        flashMessage('danger', implode('<br>', $errors));
        redirect('equipment.php?action=add');
    }

    $stmt = $conn->prepare("INSERT INTO equipment (name, category, serial_number, `condition`, total_quantity, available_quantity, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $available_qty = $total_qty;
    $stmt->bind_param("ssssiis", $name, $category, $serial_number, $condition, $total_qty, $available_qty, $description);

    if ($stmt->execute()) {
        flashMessage('success', 'Equipment added successfully!');
        redirect('equipment.php');
    } else {
        if ($conn->errno === 1062) {
            flashMessage('danger', 'That serial number already exists. Each item must have a unique serial number.');
        } else {
            flashMessage('danger', 'Error adding equipment. Please try again.');
        }
        redirect('equipment.php?action=add');
    }
    $stmt->close();
}

// handle edit
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('equipment.php');
    }

    $equipment_id = (int) $_GET['id'];

    $name          = sanitize($_POST['name']);
    $category      = sanitize($_POST['category']);
    $serial_number = sanitize($_POST['serial_number']);
    $condition     = sanitize($_POST['condition']);
    $new_total_qty = (int) $_POST['total_quantity'];
    $description   = sanitize($_POST['description']);

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Equipment name is required.';
    }
    if (empty($category)) {
        $errors[] = 'Category is required.';
    }
    if (empty($serial_number)) {
        $errors[] = 'Serial number is required.';
    }
    if (!in_array($condition, ['new', 'good', 'fair', 'poor'])) {
        $errors[] = 'Invalid condition selected.';
    }
    if ($new_total_qty < 1) {
        $errors[] = 'Total quantity must be at least 1.';
    }

    if (!empty($errors)) {
        flashMessage('danger', implode('<br>', $errors));
        redirect('equipment.php?action=edit&id=' . $equipment_id);
    }

    // get old qty so we can adjust available_quantity
    $stmt = $conn->prepare("SELECT total_quantity, available_quantity FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $old_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$old_data) {
        flashMessage('danger', 'Equipment not found.');
        redirect('equipment.php');
    }

    $qty_difference  = $new_total_qty - $old_data['total_quantity'];
    $new_available   = $old_data['available_quantity'] + $qty_difference;

    if ($new_available < 0) {
        $new_available = 0;
    }

    $stmt = $conn->prepare("UPDATE equipment SET name = ?, category = ?, serial_number = ?, `condition` = ?, total_quantity = ?, available_quantity = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssssiisi", $name, $category, $serial_number, $condition, $new_total_qty, $new_available, $description, $equipment_id);

    if ($stmt->execute()) {
        flashMessage('success', 'Equipment updated successfully!');
        redirect('equipment.php');
    } else {
        if ($conn->errno === 1062) {
            flashMessage('danger', 'That serial number already exists on another item.');
        } else {
            flashMessage('danger', 'Error updating equipment. Please try again.');
        }
        redirect('equipment.php?action=edit&id=' . $equipment_id);
    }
    $stmt->close();
}

// fetch equipment for editing if needed
$edit_equipment = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_id = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_equipment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$edit_equipment) {
        flashMessage('danger', 'Equipment not found.');
        redirect('equipment.php');
    }
}

$all_equipment = $conn->query("SELECT * FROM equipment ORDER BY name ASC");

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-tools"></i> Equipment Management</h1>

    <?php if ($action === 'list'): ?>
        <a href="equipment.php?action=add" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add New Equipment
        </a>
    <?php else: ?>
        <a href="equipment.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php
// same form for add and edit, just pre-fill values when editing
if ($action === 'add' || $action === 'edit'):
?>

<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <?php if ($action === 'add'): ?>
                <i class="bi bi-plus-circle"></i> Add New Equipment
            <?php else: ?>
                <i class="bi bi-pencil-square"></i> Edit Equipment
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">

        <form method="POST" action="equipment.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $edit_equipment['id'] : ''; ?>">

            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Equipment Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required
                           placeholder="e.g. Canon EOS R50"
                           value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_equipment['name']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="category" name="category" required
                           placeholder="e.g. Cameras, Audio, Laptops"
                           value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_equipment['category']) : ''; ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="serial_number" name="serial_number" required
                           placeholder="e.g. CAM-003"
                           value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_equipment['serial_number']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="condition" class="form-label">Condition</label>
                    <select class="form-select" id="condition" name="condition">
                        <?php
                        $conditions = ['new', 'good', 'fair', 'poor'];
                        foreach ($conditions as $cond) {
                            $selected = '';
                            if ($action === 'edit' && $edit_equipment['condition'] === $cond) {
                                $selected = 'selected';
                            } elseif ($action === 'add' && $cond === 'good') {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $cond . '" ' . $selected . '>' . ucfirst($cond) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="total_quantity" class="form-label">Total Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="total_quantity" name="total_quantity"
                           min="1" required
                           value="<?php echo ($action === 'edit') ? $edit_equipment['total_quantity'] : '1'; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"
                          placeholder="Optional — describe the equipment, what it's used for, etc."><?php echo ($action === 'edit') ? htmlspecialchars($edit_equipment['description']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <?php if ($action === 'add'): ?>
                    <i class="bi bi-plus-circle"></i> Add Equipment
                <?php else: ?>
                    <i class="bi bi-check-circle"></i> Update Equipment
                <?php endif; ?>
            </button>
            <a href="equipment.php" class="btn btn-outline-secondary ms-2">Cancel</a>

        </form>

    </div>
</div>

<?php
else:
?>

<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Equipment</h5>
    </div>
    <div class="card-body">

        <?php if ($all_equipment && $all_equipment->num_rows > 0): ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Serial Number</th>
                            <th>Condition</th>
                            <th>Total Qty</th>
                            <th>Available Qty</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $all_equipment->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><code><?php echo htmlspecialchars($item['serial_number']); ?></code></td>
                                <td>
                                    <?php
                                    // colour badge based on condition
                                    switch ($item['condition']) {
                                        case 'new':
                                            $badge = 'bg-success';
                                            break;
                                        case 'good':
                                            $badge = 'bg-primary';
                                            break;
                                        case 'fair':
                                            $badge = 'bg-warning text-dark';
                                            break;
                                        case 'poor':
                                            $badge = 'bg-danger';
                                            break;
                                        default:
                                            $badge = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge; ?>">
                                        <?php echo ucfirst($item['condition']); ?>
                                    </span>
                                </td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <td><?php echo $item['available_quantity']; ?></td>
                                <td>
                                    <a href="equipment.php?action=edit&id=<?php echo $item['id']; ?>"
                                       class="btn btn-sm btn-warning me-1" title="Edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>

                                    <form method="POST" action="equipment.php" style="display:inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this equipment? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2 mb-3">No equipment has been added yet.</p>
                <a href="equipment.php?action=add" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Add Your First Item
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
