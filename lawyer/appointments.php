<?php
// ============================================================
// LAWYER - MY APPOINTMENTS
// ============================================================
// This page manages lawyer appointments with lifecycle actions:
//
// 1. Actions: Confirm pending, Complete confirmed, Cancel any
// 2. Notifications: Auto-sent to customers on each action
// 3. Review Request: Auto-sent when appointment is completed
// 4. Chat: Available for all non-cancelled appointments
//
// Status Flow: Pending → Confirmed → Completed
// Cancellation: Available from Pending or Confirmed
//
// Features:
// - Authentication required (lawyer only)
// - Ownership verification on all actions
// - Status-based action buttons
// - Automatic customer notifications
//
// Database Tables: appointments, customers, notifications
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/functions.php - addNotification()
// - lawyer/chat.php - Chat link
// - customer/review.php - Review link
// ============================================================
$page_title = 'My Appointments';
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
$lawyer_name = $_SESSION['lawyer_name'];

// ============================================================
// 2. Handle actions (Confirm, Complete, Cancel)
// ============================================================

// Confirm appointment
if (isset($_GET['confirm'])) {
    $id = (int)$_GET['confirm'];
    $updateStmt = $conn->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ? AND lawyer_id = ?");
    $updateStmt->execute([$id, $lawyer_id]);
    
    $custStmt = $conn->prepare("SELECT customer_id FROM appointments WHERE id = ?");
    $custStmt->execute([$id]);
    $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        addNotification(
            $customer['customer_id'],
            'customer',
            'confirmed',
            'Appointment Confirmed',
            "Your appointment with Adv. $lawyer_name has been confirmed.",
            'my-appointments.php',
            'fa-check-circle'
        );
    }
    
    header("Location: appointments.php");
    exit();
}

// Complete appointment
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    
    $custStmt = $conn->prepare("SELECT customer_id FROM appointments WHERE id = ? AND lawyer_id = ?");
    $custStmt->execute([$id, $lawyer_id]);
    $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        $updateStmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE id = ? AND lawyer_id = ?");
        $updateStmt->execute([$id, $lawyer_id]);
        
        addNotification(
            $customer['customer_id'],
            'customer',
            'review_request',
            'Review Your Lawyer',
            "Your appointment with Adv. $lawyer_name has been completed. How was your experience?",
            'review.php?appointment_id=' . $id,
            'fa-star'
        );
        
        header("Location: appointments.php");
        exit();
    } else {
        header("Location: appointments.php");
        exit();
    }
}

// Cancel appointment
if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    $updateStmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND lawyer_id = ?");
    $updateStmt->execute([$id, $lawyer_id]);
    
    $custStmt = $conn->prepare("SELECT customer_id FROM appointments WHERE id = ?");
    $custStmt->execute([$id]);
    $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        addNotification(
            $customer['customer_id'],
            'customer',
            'cancelled',
            'Appointment Cancelled',
            "Your appointment with Adv. $lawyer_name has been cancelled.",
            'my-appointments.php',
            'fa-times-circle'
        );
    }
    
    header("Location: appointments.php");
    exit();
}

// ============================================================
// 3. Fetch all appointments for this lawyer
// ============================================================
$apptStmt = $conn->prepare("
    SELECT a.*, c.name as customer_name 
    FROM appointments a 
    JOIN customers c ON a.customer_id = c.id 
    WHERE a.lawyer_id = ? 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$apptStmt->execute([$lawyer_id]);
$appointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 4. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Header Card -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">My Appointments</h2>
            <p class="dashboard-subtitle">Manage all your appointments here</p>
        </div>

        <!-- Appointments Table -->
        <div class="dashboard-card">
            <?php if (count($appointments) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['booking_message'] ?? '', 0, 50)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- ========================================
                                         ACTION BUTTONS – Icon Only (Lawyer)
                                         ======================================== -->
                                    <div class="action-icons">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <!-- Confirm – Green -->
                                            <a href="?confirm=<?php echo $row['id']; ?>" 
                                               class="action-icon approve" 
                                               data-tooltip="Confirm"
                                               onclick="return confirm('Confirm this appointment? Once confirmed cannot be cancelled')">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <!-- Cancel – Red -->
                                            <a href="?cancel=<?php echo $row['id']; ?>" 
                                               class="action-icon reject" 
                                               data-tooltip="Cancel"
                                               onclick="return confirm('Cancel this appointment?')">
                                                <i class="fas fa-times-circle"></i>
                                            </a>

                                        <?php elseif ($row['status'] == 'confirmed'): ?>
                                            <!-- Complete – Blue -->
                                            <a href="?complete=<?php echo $row['id']; ?>" 
                                               class="action-icon complete" 
                                               data-tooltip="Complete"
                                               onclick="return confirm('Mark as completed?')">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                            

                                        <?php elseif ($row['status'] == 'completed' || $row['status'] == 'cancelled'): ?>
                                            <span class="no-action">—</span>
                                        <?php endif; ?>

                                        <!-- ========================================
                                             CHAT BUTTON – Icon Only (Lawyer)
                                             ======================================== -->
                                        <?php if ($row['status'] != 'cancelled'): ?>
                                            <a href="chat.php?appointment_id=<?php echo $row['id']; ?>" 
                                               class="btn-chat-icon-only" 
                                               data-tooltip="Chat">
                                                <i class="fas fa-comment"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No appointments found.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>