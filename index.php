<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

// csrf token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('index.php');
    }

    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role     = sanitize($_POST['role']);

    if (empty($username) || empty($password) || empty($role)) {
        flashMessage('danger', 'Please fill in all fields.');
        redirect('index.php');
    }

    if ($role !== 'admin' && $role !== 'user') {
        flashMessage('danger', 'Invalid role selected.');
        redirect('index.php');
    }

    // check db for matching user
    $stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            if ($user['role'] === $role) {

                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['name']     = $user['name'];

                // new csrf token after login
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                flashMessage('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('user/dashboard.php');
                }

            } else {
                flashMessage('danger', 'Invalid role selected for this account.');
            }

        } else {
            flashMessage('danger', 'Invalid username or password.');
        }

    } else {
        flashMessage('danger', 'Invalid username or password.');
    }

    $stmt->close();
    redirect('index.php');
}

require_once 'includes/header.php';
?>

<div class="login-wrapper">
    <div class="card login-card">

        <div class="card-header">
            <h4><i class="bi bi-box-seam"></i> RentO</h4>
            <small class="d-block mt-1" style="opacity: 0.85;">Equipment Rental Management System</small>
        </div>

        <div class="card-body">
            <p class="text-muted text-center mb-4">Sign in to your account</p>

            <form action="index.php" method="POST">

                <!-- csrf token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="bi bi-person"></i> Username
                    </label>
                    <input type="text"
                           class="form-control"
                           id="username"
                           name="username"
                           placeholder="Enter your username"
                           required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required>
                </div>

                <div class="mb-4">
                    <label for="role" class="form-label">
                        <i class="bi bi-shield"></i> Login As
                    </label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="" disabled selected>Select your role</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
