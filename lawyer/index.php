<?php
// ============================================================
// LAWYER - DASHBOARD
// ============================================================
// This dashboard provides lawyers with key metrics and quick actions:
//
// 1. Statistics: Total, Pending, Confirmed, Today's Earnings (from payments)
// 2. Today's Appointments: Full day schedule with actions
// 3. Upcoming Appointments: Next 10 future appointments
// 4. Quick Actions: Confirm, Complete, Cancel from dashboard
// 5. Earnings: SUM of paid payments for today's appointments
//
// Features:
// - Authentication required (lawyer only)
// - Real-time statistics
// - Quick action buttons
// - Earnings tracking from payments table using appointment_date
// - Responsive layout
//
// Database Tables: appointments, customers, lawyers, payments
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/dashboard-sidebar.php - Navigation
// - lawyer/appointments.php - Full appointment management
// - lawyer/chat.php - Chat with customers
// - lawyer/receipts.php - Payment receipts management
// ============================================================
$page_title = 'Lawyer Dashboard';
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

$lawyer_id = $_SESSION['lawyer_id'];
$lawyer_name = $_SESSION['lawyer_name'];

// ============================================================
// 2. Get lawyer details (optional)
// ============================================================
$lawyerStmt = $conn->prepare("SELECT * FROM lawyers WHERE id = ?");
$lawyerStmt->execute([$lawyer_id]);
$lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);

// ============================================================
// 3. Statistics using PDO (FIXED - Using payments table with appointment_date)
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

// ============================================================
// TODAY'S EARNINGS - From payments table using appointment_date (FIXED)
// Uses appointment_date instead of payment_date so payments
// made in advance count toward the appointment day
// ============================================================
$earningsStmt = $conn->prepare("
    SELECT SUM(p.amount) as total 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ? 
      AND p.status = 'paid' 
      AND a.appointment_date = CURDATE()
");
$earningsStmt->execute([$lawyer_id]);
$today_earnings = $earningsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ============================================================
// TOTAL EARNINGS (All time - from payments table)
// ============================================================
$totalEarningsStmt = $conn->prepare("
    SELECT SUM(p.amount) as total 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ? 
      AND p.status = 'paid'
");
$totalEarningsStmt->execute([$lawyer_id]);
$total_earnings = $totalEarningsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ============================================================
// PENDING PAYMENTS COUNT
// ============================================================
$pendingPaymentsStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ? 
      AND p.status = 'pending'
");
$pendingPaymentsStmt->execute([$lawyer_id]);
$pending_payments = $pendingPaymentsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

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
// 6. Include header
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
            <h2 class="dashboard-title">Welcome, Adv. <?php echo htmlspecialchars($lawyer_name); ?>!</h2>
            <p class="dashboard-subtitle">Manage your appointments and availability.</p>
        </div>

        <!-- ============================================================
             STATISTICS GRID – 4 columns with icons + trends
             ============================================================ -->
        <div class="stats-grid">

            <!-- Total Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-calendar-check"></i></span>
                <h3><?php echo $total; ?></h3>
                <p>Total Appointments</p>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 12%
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-clock"></i></span>
                <h3><?php echo $pending; ?></h3>
                <p>Pending</p>
                <div class="stat-trend down">
                    <i class="fas fa-arrow-down"></i> 5%
                </div>
            </div>

            <!-- Confirmed Appointments -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                <h3><?php echo $confirmed; ?></h3>
                <p>Confirmed</p>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 8%
                </div>
            </div>

            <!-- Today's Earnings (from payments table - using appointment_date) -->
            <div class="stat-box">
                <span class="stat-icon"><i class="fas fa-money-bill-wave"></i></span>
                <h3><?php echo number_format($today_earnings); ?> PKR</h3>
                <p>Today's Earnings</p>
                <?php if ($pending_payments > 0): ?>
                    <small style="color: var(--text-muted); font-size: 10px; display: block; margin-top: 2px;">
                        <?php echo $pending_payments; ?> pending payment(s)
                    </small>
                <?php endif; ?>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 15%
                </div>
            </div>

        </div>

        <!-- ============================================================
             TODAY'S APPOINTMENTS
             ============================================================ -->
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
                                             <!-- Confirm – Green checkmark -->
                                             <a href="appointments.php?confirm=<?php echo $row['id']; ?>" 
                                                class="action-icon approve" 
                                                data-tooltip="Confirm"
                                                onclick="return confirm('Confirm this appointment?')">
                                                 <i class="fas fa-check-circle"></i>
                                             </a>
                                        <?php elseif ($row['status'] == 'confirmed'): ?>
                                           <!-- Complete – Blue double-check -->
                                             <a href="appointments.php?complete=<?php echo $row['id']; ?>" 
                                                class="action-icon complete" 
                                                data-tooltip="Complete"
                                                onclick="return confirm('Mark as completed?')">
                                                 <i class="fas fa-check-double"></i>
                                             </a>
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

        <!-- ============================================================
             UPCOMING APPOINTMENTS
             ============================================================ -->
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
                                    <td class="action-btn-group" >
                                     <div class="action-icons ">
                                         <?php if ($row['status'] == 'pending'): ?>
                                             <!-- Confirm – Green checkmark -->
                                             <a href="appointments.php?confirm=<?php echo $row['id']; ?>" 
                                                class="action-icon approve" 
                                                data-tooltip="Confirm"
                                                onclick="return confirm('Confirm this appointment?')">
                                                 <i class="fas fa-check-circle"></i>
                                             </a>
                                               <!-- Cancel – Red -->
                                              <a href="appointments.php?cancel=<?php echo $row['id']; ?>" 
                                                 class="action-icon reject" 
                                                 data-tooltip="Cancel"
                                                 onclick="return confirm('Cancel this appointment?')">
                                                  <i class="fas fa-times-circle"></i>
                                              </a>
                                         <?php elseif ($row['status'] == 'confirmed'): ?>
                                             <!-- Complete – Blue double-check -->
                                             <a href="appointments.php?complete=<?php echo $row['id']; ?>" 
                                                class="action-icon complete" 
                                                data-tooltip="Complete"
                                                onclick="return confirm('Mark as completed?')">
                                                 <i class="fas fa-check-double"></i>
                                             </a>
                                         <?php else: ?>
                                             <span class="no-action">—</span>
                                         <?php endif; ?>
                                     </div>
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

    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<?php include '../includes/dashboard-footer.php'; ?>