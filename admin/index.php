<?php
// ============================================================
// ADMIN DASHBOARD – Homepage for the Admin Panel
// ============================================================
// This page displays:
// 1. Welcome message for the logged‑in admin
// 2. Statistics (lawyers, customers, appointments) in two rows of four
// 3. A table of pending lawyer approvals (limit 5)
// 4. Uses reusable sidebar (with sidebar.css),
//  dashboard-footer that loads conditionlly from header.php where $footer_css = 'dashboard';
//  and CSS from dashboard.css/tables.css 
// ============================================================

$page_title = 'Admin Dashboard';
$page_layout = 'fluid'; //set on all dashboard pages for full width set in header.php
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. AUTHENTICATION – Ensure only logged‑in admins can access
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

// ============================================================
// 2. STATISTICS – Fetch all counts using PDO prepared statements
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
// 3. RECENT PENDING LAWYERS – Show latest 5 pending approvals
// ============================================================
$recentStmt = $conn->prepare("
    SELECT id, name, email, specialization, city, created_at
    FROM lawyers
    WHERE status = 'pending'
    ORDER BY created_at DESC
    LIMIT 5
");
$recentStmt->execute();
$recent_pending = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 4. INCLUDE HEADER – Loads global styles + navbar
// ============================================================
include '../includes/header.php';
?>



<!-- ============================================================
     CSS FILES – Loaded in order
     ============================================================ -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<!-- ============================================================
     DASHBOARD LAYOUT – Grid wrapper with sidebar + main content
     ============================================================ -->
<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h2>
            <p class="dashboard-subtitle">Manage lawyers, appointments, and website content.</p>
        </div>

        <!-- ============================================================
             FIRST STATS ROW – 4 columns with icons + trends (trends are not dynamic)
             ============================================================ -->
        <div class="stats-grid">
            <!-- Total Lawyers -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-gavel"></i></span>
                  <p>Total Lawyers</p>
                <h3><?php echo $total_lawyers; ?></h3>
              
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 12%
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-clock"></i></span>
                <p>Pending Approvals</p>
                <h3><?php echo $pending_lawyers; ?></h3>
                
                <div class="stat-trend down">
                    <i class="fas fa-arrow-down"></i> 5%
                </div>
            </div>

            <!-- Total Clients -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-users"></i></span>
                <p>Total Clients</p>
                <h3><?php echo $total_customers; ?></h3>
                
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 8%
                </div>
            </div>

            <!-- Total Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-calendar-check"></i></span>
                <p>Total Appointments</p>
                <h3><?php echo $total_appointments; ?></h3>
                
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 15%
                </div>
            </div>
        </div>

        <!-- ============================================================
             SECOND STATS ROW – 4 columns with icons + trends
             ============================================================ -->
        <div class="stats-grid">
            <!-- Approved Lawyers -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                <p>Approved Lawyers</p>
                <h3><?php echo $approved_lawyers; ?></h3>
                
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 10%
                </div>
            </div>

            <!-- Rejected Lawyers -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-times-circle"></i></span>
                <p>Rejected Lawyers</p>
                <h3><?php echo $rejected_lawyers; ?></h3>
                
                <div class="stat-trend neutral">
                    <i class="fas fa-minus"></i> 0%
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-hourglass-half"></i></span>
                 <p>Pending Appointments</p>
                <h3><?php echo $pending_appointments; ?></h3>
               
                <div class="stat-trend down">
                    <i class="fas fa-arrow-down"></i> 3%
                </div>
            </div>

            <!-- Completed Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-check-double"></i></span>
                 <p>Completed Appointments</p>
                <h3><?php echo $completed_appointments; ?></h3>
               
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 22%
                </div>
            </div>
        </div>

        <!-- ============================================================
             PENDING LAWYERS TABLE – Shows latest 5 pending approvals
             ============================================================ -->
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

    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<?php include '../includes/dashboard-footer.php'; ?>