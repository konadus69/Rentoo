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

// handle delete (POST only with CSRF check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {

    // check csrf token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('users.php');
    }

    $user_id = (int) $_POST['id'];

    if ($user_id === (int) $_SESSION['user_id']) {
        flashMessage('danger', 'You cannot delete your own account!');
        redirect('users.php');
    }

    // check if they have active rentals first
    $stmt = $conn->prepare("SELECT COUNT(*) AS active_count FROM rentals WHERE user_id = ? AND status IN ('rented', 'overdue')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $active_count = $result->fetch_assoc()['active_count'];
    $stmt->close();

    if ($active_count > 0) {
        flashMessage('danger', 'Cannot delete this user — they have ' . $active_count . ' active rental(s). All items must be returned first.');
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            flashMessage('success', 'User deleted successfully.');
        } else {
            flashMessage('danger', 'Error deleting user. They may have rental history — please check.');
        }
        $stmt->close();
    }

    redirect('users.php');
}

// handle add
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('users.php?action=add');
    }

    $name         = sanitize($_POST['name']);
    $email        = sanitize($_POST['email']);
    $username     = sanitize($_POST['username']);
    $password     = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $role         = sanitize($_POST['role']);
    $max_rentals  = (int) $_POST['max_rentals'];

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    if ($password !== $confirm_pass) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = 'Invalid role selected.';
    }
    if ($max_rentals < 1) {
        $errors[] = 'Max rentals must be at least 1.';
    }

    if (!empty($errors)) {
        flashMessage('danger', implode('<br>', $errors));
        redirect('users.php?action=add');
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role, max_rentals) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $name, $email, $username, $hashed_password, $role, $max_rentals);

    if ($stmt->execute()) {
        flashMessage('success', 'User "' . $name . '" added successfully!');
        redirect('users.php');
    } else {
        if ($conn->errno === 1062) {
            flashMessage('danger', 'That username or email already exists. Please choose a different one.');
        } else {
            flashMessage('danger', 'Error adding user. Please try again.');
        }
        redirect('users.php?action=add');
    }
    $stmt->close();
}

// handle edit
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('users.php');
    }

    $user_id = (int) $_GET['id'];

    $name         = sanitize($_POST['name']);
    $email        = sanitize($_POST['email']);
    $username     = sanitize($_POST['username']);
    $password     = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $role         = sanitize($_POST['role']);
    $max_rentals  = (int) $_POST['max_rentals'];

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (!empty($password) && $password !== $confirm_pass) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = 'Invalid role selected.';
    }
    if ($max_rentals < 1) {
        $errors[] = 'Max rentals must be at least 1.';
    }

    // dont let admin change their own role
    if ($user_id === (int) $_SESSION['user_id']) {
        $role = 'admin';
    }

    if (!empty($errors)) {
        flashMessage('danger', implode('<br>', $errors));
        redirect('users.php?action=edit&id=' . $user_id);
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, username = ?, password = ?, role = ?, max_rentals = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $name, $email, $username, $hashed_password, $role, $max_rentals, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, username = ?, role = ?, max_rentals = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $name, $email, $username, $role, $max_rentals, $user_id);
    }

    if ($stmt->execute()) {
        flashMessage('success', 'User "' . $name . '" updated successfully!');
        redirect('users.php');
    } else {
        if ($conn->errno === 1062) {
            flashMessage('danger', 'That username or email already exists on another account.');
        } else {
            flashMessage('danger', 'Error updating user. Please try again.');
        }
        redirect('users.php?action=edit&id=' . $user_id);
    }
    $stmt->close();
}

// fetch user for edit form
$edit_user = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $edit_id = (int) $_GET['id'];
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$edit_user) {
        flashMessage('danger', 'User not found.');
        redirect('users.php');
    }
}

$all_users = $conn->query("SELECT * FROM users ORDER BY name ASC");

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-people"></i> User Management</h1>

    <?php if ($action === 'list'): ?>
        <a href="users.php?action=add" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Add New User
        </a>
    <?php else: ?>
        <a href="users.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php
if ($action === 'add' || $action === 'edit'):
?>

<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <?php if ($action === 'add'): ?>
                <i class="bi bi-person-plus"></i> Add New User
            <?php else: ?>
                <i class="bi bi-pencil-square"></i> Edit User
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">

        <form method="POST" action="users.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $edit_user['id'] : ''; ?>">

            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required
                           placeholder="e.g. John Smith"
                           value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_user['name']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           placeholder="e.g. john@student.ac.uk"
                           value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_user['email']) : ''; ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" required
                           placeholder="e.g. johnsmith"
                           value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_user['username']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <?php
                    $is_self = ($action === 'edit' && $edit_user['id'] === (int) $_SESSION['user_id']);
                    ?>
                    <select class="form-select" id="role" name="role" <?php echo $is_self ? 'disabled' : ''; ?>>
                        <option value="user" <?php echo ($action === 'edit' && $edit_user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($action === 'edit' && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <?php if ($is_self): ?>
                        <input type="hidden" name="role" value="admin">
                        <small class="text-muted">You cannot change your own role.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="max_rentals" class="form-label">Max Rentals</label>
                    <input type="number" class="form-control" id="max_rentals" name="max_rentals"
                           min="1" required
                           value="<?php echo ($action === 'edit') ? $edit_user['max_rentals'] : '3'; ?>">
                    <small class="text-muted">Maximum items this user can rent at once.</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">
                        Password
                        <?php if ($action === 'add'): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="<?php echo ($action === 'edit') ? 'Leave blank to keep current password' : 'Enter a password'; ?>"
                           <?php echo ($action === 'add') ? 'required' : ''; ?>>
                    <?php if ($action === 'edit'): ?>
                        <small class="text-muted">Only fill this in if you want to change the password.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">
                        Confirm Password
                        <?php if ($action === 'add'): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter password"
                           <?php echo ($action === 'add') ? 'required' : ''; ?>>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <?php if ($action === 'add'): ?>
                    <i class="bi bi-person-plus"></i> Add User
                <?php else: ?>
                    <i class="bi bi-check-circle"></i> Update User
                <?php endif; ?>
            </button>
            <a href="users.php" class="btn btn-outline-secondary ms-2">Cancel</a>

        </form>

    </div>
</div>

<?php
else:
?>

<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Users</h5>
    </div>
    <div class="card-body">

        <?php if ($all_users && $all_users->num_rows > 0): ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Max Rentals</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $all_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                <td>
                                    <?php
                                    if ($user['role'] === 'admin') {
                                        $badge = 'bg-primary';
                                    } else {
                                        $badge = 'bg-success';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['max_rentals']; ?></td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>"
                                       class="btn btn-sm btn-warning me-1" title="Edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>

                                    <?php if ($user['id'] !== (int) $_SESSION['user_id']): ?>
                                        <form method="POST" action="users.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-danger" disabled title="You cannot delete your own account">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2 mb-3">No users found.</p>
                <a href="users.php?action=add" class="btn btn-success">
                    <i class="bi bi-person-plus"></i> Add Your First User
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
