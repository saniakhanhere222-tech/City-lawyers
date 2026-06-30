<?php
/**
 * Lawyer Dashboard
 * 
 * Displays statistics, today's appointments, and upcoming appointments
 * for the logged‑in lawyer.
 */
$page_title = 'Lawyer Dashboard';
$dashboard_page = true; // triggers full‑width container in header.php
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as lawyer
// ============================================================
if (!isset($_SESSION['lawyer_id']) || $_SESSION['user_type'] != 'lawyer') {
    header("Location: login.php");
    exit();
}

$lawyer_id = $_SESSION['lawyer_id'];
$lawyer_name = $_SESSION['lawyer_name'];

// ============================================================
// 2. Get lawyer details (optional, can be removed if not used)
// ============================================================
$lawyerStmt = $conn->prepare("SELECT * FROM lawyers WHERE id = ?");
$lawyerStmt->execute([$lawyer_id]);
$lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);

// ============================================================
// 3. Statistics using PDO
// ============================================================
// Total appointments
$totalStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE lawyer_id = ?");
$totalStmt->execute([$lawyer_id]);
$total = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending appointments
$pendingStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE lawyer_id = ? AND status = 'pending'");
$pendingStmt->execute([$lawyer_id]);
$pending = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Confirmed appointments
$confirmedStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE lawyer_id = ? AND status = 'confirmed'");
$confirmedStmt->execute([$lawyer_id]);
$confirmed = $confirmedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Completed appointments
$completedStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE lawyer_id = ? AND status = 'completed'");
$completedStmt->execute([$lawyer_id]);
$completed = $completedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Today's earnings (from completed appointments today)
$earningsStmt = $conn->prepare("
    SELECT SUM(l.fees) as total 
    FROM appointments a 
    JOIN lawyers l ON a.lawyer_id = l.id 
    WHERE a.lawyer_id = ? 
      AND a.status = 'completed' 
      AND a.appointment_date = CURDATE()
");
$earningsStmt->execute([$lawyer_id]);
$today_earnings = $earningsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ============================================================
// 4. Today's appointments
// ============================================================
$todaySql = "
    SELECT a.*, c.name as customer_name 
    FROM appointments a 
    JOIN customers c ON a.customer_id = c.id 
    WHERE a.lawyer_id = :lawyer_id 
      AND a.appointment_date = CURDATE() 
    ORDER BY a.appointment_time ASC
";
$todayStmt = $conn->prepare($todaySql);
$todayStmt->execute([':lawyer_id' => $lawyer_id]);
$today_appointments = $todayStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 5. Upcoming appointments (future dates, pending/confirmed)
// ============================================================
$upcomingSql = "
    SELECT a.*, c.name as customer_name 
    FROM appointments a 
    JOIN customers c ON a.customer_id = c.id 
    WHERE a.lawyer_id = :lawyer_id 
      AND a.appointment_date >= CURDATE() 
      AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC 
    LIMIT 10
";
$upcomingStmt = $conn->prepare($upcomingSql);
$upcomingStmt->execute([':lawyer_id' => $lawyer_id]);
$upcoming = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 6. Include header (global.css, dashboard.css, etc.)
// ============================================================
include '../includes/header.php';
?>

<<!-- DASHBOARD.CSS  Desktop: CSS Grid layout (sidebar fixed width, main auto)- 
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
        <a href="<?php echo BASE_URL; ?>lawyer/index.php" class="active">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>lawyer/appointments.php">My Appointments</a>
        <a href="<?php echo BASE_URL; ?>lawyer/manage-slots.php">Manage Slots</a>
        <a href="<?php echo BASE_URL; ?>lawyer/profile.php">Profile</a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Welcome, Adv. <?php echo htmlspecialchars($lawyer_name); ?>!</h2>
            <p class="dashboard-subtitle">Manage your appointments and availability.</p>
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
                <h3><?php echo number_format($today_earnings); ?> PKR</h3>
                <p>Today's Earnings</p>
                <div class="small">from completed appointments</div>
            </div>
        </div>

        <!-- TODAY'S APPOINTMENTS -->
        <div class="dashboard-card">
            <h3 class="dashboard-title" style="font-size:28px;">Today's Appointments</h3>

            <?php if (count($today_appointments) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_appointments as $row): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-btn-group">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="appointments.php?confirm=<?php echo $row['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Confirm this appointment?')">Confirm</a>
                                        <?php elseif ($row['status'] == 'confirmed'): ?>
                                            <a href="appointments.php?complete=<?php echo $row['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Mark as completed?')">Complete</a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No appointments scheduled for today.</p>
            <?php endif; ?>
        </div>

        <!-- UPCOMING APPOINTMENTS -->
        <div class="dashboard-card">
            <h3 class="dashboard-title" style="font-size:28px;">Upcoming Appointments</h3>

            <?php if (count($upcoming) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming as $row): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-btn-group">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="appointments.php?confirm=<?php echo $row['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Confirm this appointment?')">Confirm</a>
                                        <?php elseif ($row['status'] == 'confirmed'): ?>
                                            <a href="appointments.php?complete=<?php echo $row['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Mark as completed?')">Complete</a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No upcoming appointments.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>