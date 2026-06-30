<?php
/**
 * Customer Dashboard
 * 
 * Displays appointment statistics and upcoming appointments
 * for the logged‑in customer.
 */
$page_title = 'Customer Dashboard';
$dashboard_page = true; // triggers full‑width container in header.php
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as customer
// ============================================================
if (!isset($_SESSION['customer_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: ../customer/login.php");
    exit();
}

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
        <a href="<?php echo BASE_URL; ?>customer/index.php" class="active">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>customer/my-appointments.php">My Appointments</a>
        <a href="<?php echo BASE_URL; ?>customer/search.php">Find Lawyers</a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($customer_name); ?>!</h2>
            <p class="dashboard-subtitle">Manage appointments and connect with trusted legal professionals.</p>
        </div>

        <!-- STATISTICS GRID -->
        <div class="stats-grid">
            <div class="stat-box">
                <h3><?php echo $total; ?></h3>
                <p>Total Appointments</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $pending; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $confirmed; ?></h3>
                <p>Confirmed</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $completed; ?></h3>
                <p>Completed</p>
            </div>
        </div>

        <!-- UPCOMING APPOINTMENTS TABLE -->
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

    </div>
</div>

<?php include '../includes/footer.php'; ?>