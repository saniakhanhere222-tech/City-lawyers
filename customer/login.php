<?php
// ============================================================
// CUSTOMER - LOGIN
// ============================================================
// This page authenticates customers with email and password:
//
// 1. Login Form:
//    - Email and password fields
//    - Error message display
//    - Registration link for new users
//
// 2. Authentication:
//    - Secure password verification (password_verify)
//    - Session creation on success
//    - Redirect to dashboard
//    - Redirect if already logged in
//
// 3. Security:
//    - Password hashing with bcrypt
//    - Prepared statements
//    - XSS prevention
//    - Generic error messages
//
// Features:
// - Session-based authentication
// - Responsive login card
// - Shared auth.css styling
// - Dashboard footer integration
//
// Database Tables Used:
// - customers (id, name, email, password)
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/dashboard-footer.php - Footer
// - assets/css/auth.css - Page styling
// - customer/index.php - Redirect destination
// - ../register.php - Registration link
// ============================================================
$page_title = 'Customer Login';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, email, password FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['customer_id']   = $user['id'];
        $_SESSION['customer_name'] = $user['name'];
        $_SESSION['user_type']     = 'customer';
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}

include '../includes/header.php';
?>

<!-- Load auth.css for this page -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">

<!-- Page wrapper – everything inside this class is independent -->
<div class="login-wrapper">

    <!-- Background image -->
    <div class="login-bg"></div>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>assets/images/citylawyers_logo.png" alt="Logo" class="login-logo">
                        <h3>Welcome User</h3>
                        <p>Please login to your account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-login w-100">LOGIN</button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">
                            Don't have an account?
                            <a href="<?php echo BASE_URL; ?>register.php?type=customer">Get Registered</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>