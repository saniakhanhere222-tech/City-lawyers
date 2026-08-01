<?php
/**
 * Customer Dashboard
 * 
 * Displays appointment statistics and upcoming appointments
 * for the logged‑in customer.
 */
$page_title = 'Customer Dashboard';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as customer
// ============================================================
if (!isset($_SESSION['customer_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: ../customer/login.php");
    exit();
}

// Set sidebar variables
$user_type = 'customer';
$user_name = $_SESSION['customer_name'];
$dashboard_link = BASE_URL . 'customer/index.php';

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// ============================================================
// 2. Get upcoming appointments (pending/confirmed, limit 5)
// ============================================================
$upcomingSql = "
    SELECT a.*, 
           l.name AS lawyer_name, 
           l.specialization, 
           l.fees
    FROM appointments a
    INNER JOIN lawyers l ON a.lawyer_id = l.id
    WHERE a.customer_id = :customer_id
      AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date ASC
    LIMIT 5
";
$upcomingStmt = $conn->prepare($upcomingSql);
$upcomingStmt->execute([':customer_id' => $customer_id]);
$upcomingAppointments = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 3. Count appointments by status
// ============================================================
// Total
$totalStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ?");
$totalStmt->execute([$customer_id]);
$total = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending
$pendingStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ? AND status = 'pending'");
$pendingStmt->execute([$customer_id]);
$pending = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Confirmed
$confirmedStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ? AND status = 'confirmed'");
$confirmedStmt->execute([$customer_id]);
$confirmed = $confirmedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Completed
$completedStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ? AND status = 'completed'");
$completedStmt->execute([$customer_id]);
$completed = $completedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// ============================================================
// 4. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- ============================================================
     CSS FILES
     dashboard.css – layout + cards + stats grid
     tables.css – table styles, badges
     sidebar.css – collapsible sidebar
     ============================================================ -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- ============================================================
             WELCOME CARD
             ============================================================ -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($customer_name); ?>!</h2>
            <p class="dashboard-subtitle">Manage appointments and connect with trusted legal professionals.</p>
        </div>

        <!-- ============================================================
             STATISTICS GRID – 4 columns with icons + trends
             The .stat-icon and .stat-trend styles are already in dashboard.css
             ============================================================ -->
        <div class="stats-grid">

            <!-- Total Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-calendar-check"></i></span>
                 <p>Total Appointments</p>
                <h3><?php echo $total; ?></h3>
               
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 12%
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-clock"></i></span>
                 <p>Pending</p>
                <h3><?php echo $pending; ?></h3>
               
                <div class="stat-trend down">
                    <i class="fas fa-arrow-down"></i> 5%
                </div>
            </div>

            <!-- Confirmed Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                  <p>Confirmed</p>
                <h3><?php echo $confirmed; ?></h3>
              
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 8%
                </div>
            </div>

            <!-- Completed Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-check-double"></i></span>
                 <p>Completed</p>
                <h3><?php echo $completed; ?></h3>
               
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 15%
                </div>
            </div>

        </div>

        <!-- ============================================================
             UPCOMING APPOINTMENTS TABLE
             ============================================================ -->
        <div class="dashboard-card">
            <h3 class="dashboard-title" style="font-size:28px;">Upcoming Appointments</h3>

            <?php if (count($upcomingAppointments) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Lawyer</th>
                                <th>Specialization</th>
                                <th>Status</th>
                                <th>Fees</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $row): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['lawyer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                <td>
                                    <span class="status status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($row['fees']); ?> PKR</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No upcoming appointments found.</p>
            <?php endif; ?>
        </div>

    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<?php include '../includes/dashboard-footer.php'; ?>