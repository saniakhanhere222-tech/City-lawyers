<?php
// ============================================================
// Registration Page – Customer / Lawyer
// ============================================================
$page_title = 'Create Your Account';
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
require_once 'includes/config.php';
$page_layout= 'fluid';

// ============================================================
// Determine which form to show (customer or lawyer)
// ============================================================
$show_form = $_GET['type'] ?? 'customer';
$error = '';
$success = '';

// ============================================================
// FETCH CATEGORIES FOR LAWYER REGISTRATION DROPDOWN
// ============================================================
$categories = [];
$catStmt = $conn->prepare("SELECT name FROM categories WHERE status = 'active' ORDER BY order_by ASC, name ASC");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// If no categories found, fallback to hardcoded list
if (empty($categories)) {
    $categories = ['Criminal', 'Divorce', 'Affidavit', 'Civil'];
}

// ============================================================
// CUSTOMER REGISTRATION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_type'] == 'customer') {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $city     = trim($_POST['city'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (name, email, password, city, phone, address, reg_date, status) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'active')");
            if ($stmt->execute([$name, $email, $hashed, $city, $phone, $address])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// ============================================================
// LAWYER REGISTRATION (simple file upload – original name)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_type'] == 'lawyer') {

    $name           = trim($_POST['name']);
    $email          = trim($_POST['email']);
    $password       = $_POST['password'];
    $confirm        = $_POST['confirm_password'];
    $city           = trim($_POST['city'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization']);
    $gender         = trim($_POST['gender']);
    $experience     = (int)($_POST['experience'] ?? 0);
    $fees           = (float)($_POST['fees'] ?? 0);
    $bio            = trim($_POST['bio'] ?? '');
    $core_specialization   = trim($_POST['core_specialization'] ?? '');
    $academic_credentials  = trim($_POST['academic_credentials'] ?? '');

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM lawyers WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Simple file upload – original filename
            $profile_pic = '';
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                $image_name = $_FILES['profile_pic']['name'];
                $tmp_path   = $_FILES['profile_pic']['tmp_name'];
                $upload_dir = "uploads/lawyers/";
                $destination = $upload_dir . $image_name;

                if (move_uploaded_file($tmp_path, $destination)) {
                    $profile_pic = $image_name;
                } else {
                    $error = "Image upload failed.";
                }
            }

            if (!$error) {
                $stmt = $conn->prepare("INSERT INTO lawyers (name, email, password, phone, city, specialization, gender, experience, fees, bio, profile_pic, core_specialization, academic_credentials, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                if ($stmt->execute([$name, $email, $hashed, $phone, $city, $specialization, $gender, $experience, $fees, $bio, $profile_pic, $core_specialization, $academic_credentials])) {
                    
                    // ✅ Send notification to ALL admins about new lawyer registration
                    $adminStmt = $conn->prepare("SELECT id FROM admins");
                    $adminStmt->execute();
                    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($admins as $admin) {
                        addNotification(
                            $admin['id'],
                            'admin',
                            'new_lawyer',
                            'New Lawyer Registration',
                            "New lawyer $name has registered and is pending approval.",
                            'manage-lawyers.php',  // ✅ Relative to admin folder
                            'fa-user-plus'
                        );
                    }
                    
                    $success = "Registration successful! Awaiting admin approval.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Load register.css for this page only -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/register.css">

<div class="register-bg">
    <div class="register-overlay">
        <div class="register-wrapper">
            <!-- Sidebar -->
            <aside class="register-sidebar">
                <div class="brand">
                    <h2>Select Identity</h2>
                    <p>Choose one below to create your account</p>
                </div>
                <div class="role-buttons">
                    <a href="?type=customer"
                       class="role-btn <?php echo ($show_form == 'customer') ? 'active' : ''; ?>">
                        Client
                    </a>
                    <a href="?type=lawyer"
                       class="role-btn <?php echo ($show_form == 'lawyer') ? 'active' : ''; ?>">
                        Lawyer
                    </a>
                </div>
            </aside>
            <!-- End Sidebar -->
            
            <!-- Registration Form Area -->
            <section class="register-form-area">
                <div class="form-box">
                    <h1 class="form-title">
                        Create an Account
                    </h1>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_form == 'customer'): ?>

                        <!-- Customer Registration -->
                        <form method="POST">
                            <input type="hidden" name="form_type" value="customer">

                            <div class="form-grid">
                                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                                <input type="text" name="city" class="form-control" placeholder="City">
                                <input type="text" name="phone" class="form-control" placeholder="Phone">
                                <textarea name="address" class="form-control form-full" placeholder="Address"></textarea>
                            </div>

                            <button type="submit" class="submit-btn">
                                Create Account
                            </button>
                        </form>

                    <?php else: ?>

                        <!-- Lawyer Registration - DYNAMIC SPECIALIZATION DROPDOWN -->
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="form_type" value="lawyer">

                            <div class="form-grid">
                                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                                <input type="text" name="city" class="form-control" placeholder="City">
                                <input type="text" name="phone" class="form-control" placeholder="Phone">
                                
                                <!-- DYNAMIC SPECIALIZATION DROPDOWN -->
                                <select name="specialization" class="form-control" required>
                                    <option value="">Select Specialization</option> 
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select name="gender" class="form-control">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                <input type="number" name="experience" class="form-control" placeholder="Experience (years)">
                                <input type="number" name="fees" class="form-control" placeholder="Fees (PKR)">
                                <textarea name="bio" class="form-control form-full" placeholder="Bio / About yourself"></textarea>
                                <input type="text" name="core_specialization" class="form-control form-full" placeholder="Core Specializations (comma separated)">
                                <textarea name="academic_credentials" class="form-control form-full" placeholder="Academic Credentials (comma separated)"></textarea>
                                <input type="file" name="profile_pic" class="form-control form-full" accept="image/*">
                            </div>

                            <button type="submit" class="submit-btn">
                                Create Account
                            </button>
                        </form>

                    <?php endif; ?>
                </div>
            </section>
        </div>  <!-- End wrapper -->
    </div><!--end overlay-->
</div><!--end bg -->

<?php include 'includes/dashboard-footer.php'; ?>