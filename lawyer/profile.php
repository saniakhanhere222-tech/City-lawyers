<?php
/**
 * Lawyer - Edit Profile
 * 
 * Allows a logged‑in lawyer to update their profile information,
 * including name, email, phone, city, specialization, gender,
 * experience, fees, bio, core specialization, academic credentials,
 * and profile picture.
 */
$page_title = 'Edit Profile';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as lawyer
// ============================================================
if (!isset($_SESSION['lawyer_id']) || $_SESSION['user_type'] != 'lawyer') {
    header("Location: login.php");
    exit();
}

// Set sidebar variables
$user_type = 'lawyer';
$user_name = $_SESSION['lawyer_name'];
$dashboard_link = BASE_URL . 'lawyer/index.php';

$lawyer_id = $_SESSION['lawyer_id'];

// ============================================================
// 2. Fetch current lawyer data using PDO
// ============================================================
$lawyerStmt = $conn->prepare("SELECT * FROM lawyers WHERE id = ?");
$lawyerStmt->execute([$lawyer_id]);
$lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) {
    header("Location: index.php");
    exit();
}

$message = '';
$error = '';

// ============================================================
// 3. Handle form submission
// ============================================================
if (isset($_POST['update'])) {
    // Sanitize inputs
    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $phone  = trim($_POST['phone'] ?? '');
    $city   = trim($_POST['city'] ?? '');
    $specialization = trim($_POST['specialization']);
    $gender = trim($_POST['gender']);
    $experience = (int)($_POST['experience'] ?? 0);
    $fees   = (float)($_POST['fees'] ?? 0);
    $bio    = trim($_POST['bio'] ?? '');
    $core_specialization   = trim($_POST['core_specialization'] ?? '');
    $academic_credentials  = trim($_POST['academic_credentials'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        // Check if email already exists (excluding current lawyer)
        $checkStmt = $conn->prepare("SELECT id FROM lawyers WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $lawyer_id]);
        if ($checkStmt->fetch()) {
            $error = "Email already in use by another lawyer.";
        } else {
            // ============================================================
            // Handle profile picture upload (FIXED)
            // ============================================================
            $profile_pic = $lawyer['profile_pic']; // keep old by default

            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                $image_name = $_FILES['profile_pic']['name'];
                $tmp_path   = $_FILES['profile_pic']['tmp_name'];
                
                // ✅ CORRECT PATH: go up one level from lawyer/ to root
                $upload_dir = "../uploads/lawyers/";
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename to avoid overwrites
                $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $new_filename = time() . '_' . uniqid() . '.' . $ext;
                $destination = $upload_dir . $new_filename;

                // Delete old image if it exists
                if (!empty($lawyer['profile_pic']) && file_exists($upload_dir . $lawyer['profile_pic'])) {
                    unlink($upload_dir . $lawyer['profile_pic']);
                }

                if (move_uploaded_file($tmp_path, $destination)) {
                    $profile_pic = $new_filename;
                } else {
                    $error = "Image upload failed. Please check folder permissions.";
                }
            }

            if (!$error) {
                // Update using PDO prepared statement
                $updateStmt = $conn->prepare("
                    UPDATE lawyers SET 
                        name = ?, email = ?, phone = ?, city = ?, 
                        specialization = ?, gender = ?, 
                        experience = ?, fees = ?, bio = ?, 
                        core_specialization = ?, 
                        academic_credentials = ?,
                        profile_pic = ?
                    WHERE id = ?
                ");
                
                if ($updateStmt->execute([
                    $name, $email, $phone, $city,
                    $specialization, $gender,
                    $experience, $fees, $bio,
                    $core_specialization,
                    $academic_credentials,
                    $profile_pic,
                    $lawyer_id
                ])) {
                    $message = "Profile updated successfully!";
                    // Refresh lawyer data
                    $lawyerStmt->execute([$lawyer_id]);
                    $lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Update failed. Please try again.";
                }
            }
        }
    }
}

// ============================================================
// 4. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<!-- Page-specific styles for profile -->
<style>
/* Profile form styles */
.profile-form .form-group {
    margin-bottom: 20px;
}
.profile-form .form-group label {
    display: block;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text-light);
    margin-bottom: 8px;
}
.profile-form .form-control,
.profile-form .form-select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    background: var(--white);
    font-size: 14px;
    border-radius: 0;
}
.profile-form .form-control:focus,
.profile-form .form-select:focus {
    outline: none;
    border-color: var(--primary-color);
}
.profile-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.profile-form .current-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    margin: 10px 0;
    background: #ebe5db;
}
.profile-form .btn-update {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 25px;
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
}
.profile-form .btn-update:hover {
    background: #1f291f;
}
.profile-form .alert-success {
    background: #dce9d7;
    color: #2e5b2e;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #c5d4c5;
}
.profile-form .alert-danger {
    background: #f0e0e0;
    color: #8b3a3a;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #d4c5c5;
}
.profile-form small {
    font-size: 11px;
    color: var(--text-light);
}
@media (max-width: 768px) {
    .profile-form .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
</style>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Header Card -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Edit Profile</h2>
            <p class="dashboard-subtitle">Update your professional information</p>
        </div>

        <!-- Success / Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Profile Form -->
        <div class="dashboard-card profile-form">
            <form method="post" enctype="multipart/form-data">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($lawyer['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($lawyer['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($lawyer['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($lawyer['city']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Specialization</label>
                        <select name="specialization" class="form-select">
                            <option value="Criminal" <?php echo $lawyer['specialization'] == 'Criminal' ? 'selected' : ''; ?>>Criminal</option>
                            <option value="Divorce" <?php echo $lawyer['specialization'] == 'Divorce' ? 'selected' : ''; ?>>Divorce</option>
                            <option value="Affidavit" <?php echo $lawyer['specialization'] == 'Affidavit' ? 'selected' : ''; ?>>Affidavit</option>
                            <option value="Civil" <?php echo $lawyer['specialization'] == 'Civil' ? 'selected' : ''; ?>>Civil</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-select">
                            <option value="male" <?php echo $lawyer['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $lawyer['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $lawyer['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Experience (years)</label>
                        <input type="number" name="experience" class="form-control" value="<?php echo $lawyer['experience']; ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Consultation Fees (PKR)</label>
                        <input type="number" name="fees" class="form-control" value="<?php echo $lawyer['fees']; ?>" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Bio / About</label>
                    <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($lawyer['bio']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Core Specialization</label>
                    <textarea name="core_specialization" class="form-control" rows="3" placeholder="e.g., Criminal Litigation, Legal Consultation, Evidence Analysis, Strategic Defense"><?php echo htmlspecialchars($lawyer['core_specialization']); ?></textarea>
                    <small>Separate each specialization with a comma</small>
                </div>
                
                <div class="form-group">
                    <label>Academic Credentials</label>
                    <textarea name="academic_credentials" class="form-control" rows="3" placeholder="e.g., LL.B - University of Karachi, L.L.M - International Law, Member Bar Council"><?php echo htmlspecialchars($lawyer['academic_credentials']); ?></textarea>
                    <small>Separate each credential with a comma</small>
                </div>
                
                <div class="form-group">
                    <label>Profile Picture</label>
                    <?php if (!empty($lawyer['profile_pic']) && file_exists("../uploads/lawyers/" . $lawyer['profile_pic'])): ?>
                        <div>
                            <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($lawyer['profile_pic']); ?>" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="profile_pic" class="form-control" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>
                
                <button type="submit" name="update" class="btn-update">Update Profile</button>
                
            </form>
        </div>

    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>