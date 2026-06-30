<?php
// ============================================================
// Admin Login Page – same classes as customer login
// ============================================================
$page_title = 'Admin Login';
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

<!-- Same wrapper class as customer login, plus ID for custom background -->
<div class="login-wrapper" id="admin-login">
    <div class="login-bg"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>assets/images/legalFlowlogotransp.png"
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

<?php include '../includes/footer.php'; ?>