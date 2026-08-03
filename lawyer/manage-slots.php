<?php
// ============================================================
// LAWYER - MANAGE TIME SLOTS
// ============================================================
// This page manages lawyer availability for appointments:
//
// 1. Add: Create new time slots (day + start/end time)
// 2. Delete: Remove slots (protected if appointments exist)
// 3. Display: Show all slots ordered by day (Monday → Saturday)
//
// Features:
// - Authentication required (lawyer only)
// - Booking protection on deletion
// - Custom day ordering
// - Time formatting (24h input, 12h display)
//
// Database Tables: slots, appointments
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/dashboard-sidebar.php - Navigation
// - customer/book-appointment.php - Uses slots for booking
// ============================================================
$page_title = 'Manage Slots';
$page_layout= 'fluid'; //set in header.php 
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
require_once '../includes/config.php';

if (!isset($_SESSION['lawyer_id']) || $_SESSION['user_type'] != 'lawyer') {
    header("Location: login.php");
    exit();
}

$lawyer_id = $_SESSION['lawyer_id'];
$message = '';
$error = '';

// Add slot
if (isset($_POST['add_slot'])) {
    $day = trim($_POST['day']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);

    if (empty($day) || empty($start_time) || empty($end_time)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO slots (lawyer_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$lawyer_id, $day, $start_time, $end_time])) {
            $message = "Slot added successfully!";
        } else {
            $error = "Failed to add slot.";
        }
    }
}

// Delete slot
if (isset($_GET['delete'])) {
    $slot_id = (int)$_GET['delete'];
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE slot_id = ?");
    $checkStmt->execute([$slot_id]);
    $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count > 0) {
        $error = "Cannot delete this slot. It has existing appointments.";
    } else {
        $delStmt = $conn->prepare("DELETE FROM slots WHERE id = ? AND lawyer_id = ?");
        if ($delStmt->execute([$slot_id, $lawyer_id])) {
            $message = "Slot deleted successfully!";
        } else {
            $error = "Failed to delete slot.";
        }
    }
}

// Fetch slots
$slotsStmt = $conn->prepare("
    SELECT * FROM slots 
    WHERE lawyer_id = ? 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time
");
$slotsStmt->execute([$lawyer_id]);
$slots = $slotsStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<!-- DASHBOARD.CSS  Desktop: CSS Grid layout (sidebar fixed width, main auto)- 
 Mobile: horizontal navigation strip 
 - Cards, stats grid, layout only -->
<link rel="stylesheet" href="<?php echo BASE_URL;?>assets/css/dashboard.css">
<!---TABLES.CSS – reusable dashboard table styles
   (filter tabs, tables, status badges, action buttons, pagination) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">


<div class="dashboard-wrapper">
    
<!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>


    <div class="main-content">
        <div class="dashboard-card">
            <h2 class="dashboard-title">Manage Time Slots</h2>
            <p class="dashboard-subtitle">Add or remove your available consultation hours</p>
        </div>

        <?php if ($message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Slot Form (self‑contained) -->
        <div class="dashboard-card">
            <h3 class="dashboard-title" style="font-size:24px;">Add New Slot</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Day of Week</label>
                    <select name="day" class="form-control" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <button type="submit" name="add_slot" class="btn-submit">Add Slot</button>
            </form>
        </div>

        <!-- Existing Slots List -->
        <div class="dashboard-card">
            <h3 class="dashboard-title" style="font-size:24px;">Your Available Slots</h3>
            <?php if (count($slots) > 0): ?>
                <?php foreach ($slots as $row): ?>
                    <div class="slot-item">
                        <div class="slot-day"><?php echo htmlspecialchars($row['day_of_week']); ?></div>
                        <div class="slot-time">
                            <?php echo date('h:i A', strtotime($row['start_time'])); ?> -
                            <?php echo date('h:i A', strtotime($row['end_time'])); ?>
                        </div>
                        <div class="slot-actions">
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this slot?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No slots added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>