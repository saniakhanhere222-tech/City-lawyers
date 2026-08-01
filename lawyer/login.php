<?php
// ============================================================
// Lawyer Login Page – same classes as customer login
// ============================================================
$page_title = 'Lawyer Login';
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['lawyer_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// ============================================================
// Handle login form submission
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare and execute query to fetch lawyer by email
    $stmt = $conn->prepare("SELECT id, name, email, password, status FROM lawyers WHERE email = ?");
    $stmt->execute([$email]);
    $lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lawyer && password_verify($password, $lawyer['password'])) {
        // Check if lawyer is approved by admin
        if ($lawyer['status'] !== 'approved') {
            $error = "Your account is pending admin approval.";
        } else {
            $_SESSION['lawyer_id']   = $lawyer['id'];
            $_SESSION['lawyer_name'] = $lawyer['name'];
            $_SESSION['user_type']   = 'lawyer';
            header("Location: index.php");
            exit();
        }
    } else {
        $error = "Invalid email or password!";
    }
}

include '../includes/header.php';
?>

<!-- Load auth.css for this page -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">

<!-- Lawyer login wrapper -->
<div class="login-wrapper">

    <!-- Background image div (styled in auth.css) -->
    <div class="login-bg"></div>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <!-- Logo – styled by .login-wrapper .login-logo -->
                        <img src="<?php echo BASE_URL; ?>assets/images/citylawyers_logo.png"
                             alt="Logo"
                             class="login-logo">
                        <h3>Welcome Lawyer</h3>
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
                            <a href="<?php echo BASE_URL; ?>register.php?type=lawyer">Register as Lawyer</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
 
<?php include '../includes/dashboard-footer.php'; ?>