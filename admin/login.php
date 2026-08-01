<?php
// ============================================================
// Admin Login Page – same classes as customer login
// ============================================================
// This page displays:
// 1. Login form with username and password fields
// 2. Error message display for invalid credentials
// 3. Logo and branding at the top
// 4. Redirects to dashboard if already logged in
// Uses: auth.css for styling (shared with customer login)
// Features: 
// - Plain text password comparison (not hashed - needs security improvement)
// - Session-based authentication
// - Redirect to admin dashboard on successful login
// ============================================================

$page_title = 'Admin Login';
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['user_type']  = 'admin';
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">

<!-- Same wrapper class as customer login for reusable css for all login pages-->
<div class="login-wrapper">
    <div class="login-bg"></div>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>assets/images/citylawyers_logo.png"
                             alt="Logo"
                             class="login-logo">
                        <h3>Admin Portal</h3>
                        <p>Enter credentials to access dashboard</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-login w-100">LOGIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>