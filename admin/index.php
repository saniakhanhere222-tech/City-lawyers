<?php
// ============================================================
// Admin Dashboard
// ============================================================
$page_title = 'Admin Dashboard';
$dashboard_page = true; // triggers full‑width container in header.php
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as admin
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

// ============================================================
// 2. Get statistics using PDO
// ============================================================

// Total lawyers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyers");
$stmt->execute();
$total_lawyers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending lawyers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyers WHERE status = 'pending'");
$stmt->execute();
$pending_lawyers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Approved lawyers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyers WHERE status = 'approved'");
$stmt->execute();
$approved_lawyers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Rejected lawyers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyers WHERE status = 'rejected'");
$stmt->execute();
$rejected_lawyers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total customers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers");
$stmt->execute();
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total appointments
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments");
$stmt->execute();
$total_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending appointments
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$stmt->execute();
$pending_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Completed appointments
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
$stmt->execute();
$completed_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// ============================================================
// 3. Get recent pending lawyers (limit 5)
// ============================================================
$recentStmt = $conn->prepare("SELECT id, name, email, specialization, city, created_at FROM lawyers WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
$recentStmt->execute();
$recent_pending = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 4. Include header (global.css, dashboard.css, etc.)
// ============================================================
include '../includes/header.php';
?>

<!-- DASHBOARD.CSS  Desktop: CSS Grid layout (sidebar fixed width, main auto)- 
 Mobile: horizontal navigation strip 
 - Cards, stats grid, layout only -->
<link rel="stylesheet" href="<?php echo BASE_URL;?>assets/css/dashboard.css">
<!---TABLES.CSS – reusable dashboard table styles
   (filter tabs, tables, status badges, action buttons, pagination) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="active">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-lawyers.php">Manage Lawyers</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-appointments.php">Appointments</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-content.php">Homepage</a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h2>
            <p class="dashboard-subtitle">Manage lawyers, appointments, and website content.</p>
        </div>

        <!-- FIRST STATS ROW -->
        <div class="stats-grid">
            <div class="stat-box">
                <h3><?php echo $total_lawyers; ?></h3>
                <p>Total Lawyers</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $pending_lawyers; ?></h3>
                <p>Pending Approvals</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $total_customers; ?></h3>
                <p>Total Clients</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $total_appointments; ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>

        <!-- SECOND STATS ROW -->
        <div class="stats-grid">
            <div class="stat-box">
                <h3><?php echo $approved_lawyers; ?></h3>
                <p>Approved Lawyers</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $rejected_lawyers; ?></h3>
                <p>Rejected Lawyers</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $pending_appointments; ?></h3>
                <p>Pending Appointments</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $completed_appointments; ?></h3>
                <p>Completed Appointments</p>
            </div>
        </div>

        <!-- PENDING LAWYERS TABLE -->
        <div class="dashboard-card">
            <h3 class="dashboard-title" style="font-size:28px;">Pending Lawyer Approvals</h3>

            <?php if (count($recent_pending) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>City</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_pending as $row): ?>
                                <tr>
                                    <td>Adv. <?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($row['city']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="manage-lawyers.php?approve=<?php echo $row['id']; ?>" class="btn-approve" onclick="return confirm('Approve this lawyer?')">Approve</a>
                                        <a href="manage-lawyers.php?reject=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this lawyer?')">Reject</a>
                                        <a href="manage-lawyers.php?view=<?php echo $row['id']; ?>" class="btn-view">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No pending lawyer approvals.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>