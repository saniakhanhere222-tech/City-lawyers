<?php
/**
 * ============================================================
 * FILE: customer/book-appointment.php
 * 
 * PURPOSE: Allows a logged‑in customer to book a new appointment
 *          or reschedule an existing one with a lawyer.
 * ============================================================
 */
$page_title = 'Book Appointment';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as customer
// ============================================================
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// ============================================================
// 2. Check if we are editing (rescheduling) an existing appointment
// ============================================================
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$is_edit = false;
$appointment_data = null;
$lawyer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($edit_id > 0) {
    $editStmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND customer_id = ?");
    $editStmt->execute([$edit_id, $customer_id]);
    $appointment_data = $editStmt->fetch(PDO::FETCH_ASSOC);
    if ($appointment_data) {
        $is_edit = true;
        $lawyer_id = $appointment_data['lawyer_id'];
    }
}

// ============================================================
// 3. Fetch lawyer details (must be approved)
// ============================================================
$lawyerStmt = $conn->prepare("SELECT * FROM lawyers WHERE id = ? AND status = 'approved'");
$lawyerStmt->execute([$lawyer_id]);
$lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) {
    header("Location: search.php");
    exit();
}

// ============================================================
// 4. Determine the currently selected date
// ============================================================
$selected_date = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['date'])) {
    $selected_date = $_POST['date'];
} elseif (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
} elseif ($is_edit && $appointment_data) {
    $selected_date = $appointment_data['appointment_date'];
}

// ============================================================
// 5. Generate time slot options for the selected date
// ============================================================
$time_options = '';
if ($selected_date) {
    $day_of_week = date('l', strtotime($selected_date));
    $slotStmt = $conn->prepare("SELECT * FROM slots WHERE lawyer_id = ? AND day_of_week = ? AND is_available = 1 ORDER BY start_time");
    $slotStmt->execute([$lawyer_id, $day_of_week]);
    $slot_ranges = $slotStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($slot_ranges as $range) {
        $start = strtotime($range['start_time']);
        $end   = strtotime($range['end_time']);
        while ($start < $end) {
            $slot_time = date('H:i:s', $start);
            $display_time = date('h:i A', $start);

            // Check if slot is already booked
            $bookedStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE lawyer_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
            $bookedStmt->execute([$lawyer_id, $selected_date, $slot_time]);
            $is_booked = $bookedStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

            $disabled = $is_booked ? 'disabled' : '';
            $selected = ($is_edit && $appointment_data['appointment_time'] == $slot_time) ? 'selected' : '';

            $time_options .= "<option value='$slot_time' $selected $disabled>$display_time</option>";
            $start = strtotime('+30 minutes', $start);
        }
    }
}

// ============================================================
// 6. Process form submission
// ============================================================
$error = '';
$show_modal = false;
$booked_lawyer_name = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['date']) && isset($_POST['time'])) {
    $date    = $_POST['date'];
    $time    = $_POST['time'];
    $message = trim($_POST['message'] ?? '');
    $edit_post_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if (empty($date) || empty($time)) {
        $error = "Please select both date and time.";
    } else {
        // Double booking check
        $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE lawyer_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $checkStmt->execute([$lawyer_id, $date, $time]);
        $is_booked = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

        if ($is_booked) {
            $error = "This time slot is already booked. Please select another time.";
        } else {
            if ($edit_post_id > 0) {
                // Reschedule
                $updateStmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, booking_message = ?, status = 'pending', reschedule_count = reschedule_count + 1 WHERE id = ? AND customer_id = ?");
                if ($updateStmt->execute([$date, $time, $message, $edit_post_id, $customer_id])) {
                    $notifyMsg = "Customer $customer_name rescheduled to " . date('d M Y', strtotime($date)) . " at " . date('h:i A', strtotime($time));
                    $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message) VALUES (?, 'lawyer', 'Appointment Rescheduled', ?)");
                    $notifyStmt->execute([$lawyer_id, $notifyMsg]);
                    $show_modal = true;
                    $booked_lawyer_name = $lawyer['name'];
                } else {
                    $error = "Update failed. Please try again.";
                }
            } else {
                // New appointment
                $insertStmt = $conn->prepare("INSERT INTO appointments (lawyer_id, customer_id, appointment_date, appointment_time, booking_message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                if ($insertStmt->execute([$lawyer_id, $customer_id, $date, $time, $message])) {
                    $notifyMsg = "Customer $customer_name requested appointment on " . date('d M Y', strtotime($date)) . " at " . date('h:i A', strtotime($time));
                    $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message) VALUES (?, 'lawyer', 'New Appointment Request', ?)");
                    $notifyStmt->execute([$lawyer_id, $notifyMsg]);
                    $show_modal = true;
                    $booked_lawyer_name = $lawyer['name'];
                } else {
                    $error = "Booking failed. Please try again.";
                }
            }
        }
    }
}

// ============================================================
// 7. Include header and CSS
// ============================================================
include '../includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/book-appointment.css">

<!-- ============================================================
     BOOKING FORM (always visible)
============================================================ -->
<div class="booking-container">
    <div class="booking-bg"></div>

    <div class="booking-wrapper">
        <!-- LEFT PANEL: Lawyer information -->
        <div class="luxury-panel">
            <div class="lawyer-avatar">
                <?php if (!empty($lawyer['profile_pic']) && file_exists("../uploads/lawyers/" . $lawyer['profile_pic'])): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($lawyer['profile_pic']); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user-advocate"></i>
                <?php endif; ?>
            </div>
            <div class="lawyer-name-panel">
                <h2><?php echo htmlspecialchars($lawyer['name']); ?></h2>
                <p><?php echo htmlspecialchars($lawyer['specialization']); ?> Specialist</p>
            </div>
            <div class="divider"></div>
            <div class="info-row">
                <span class="info-label">Selected Date</span>
                <span class="info-value" id="displayDate">
                    <?php echo $selected_date ? date('l, d M Y', strtotime($selected_date)) : '—'; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Selected Time</span>
                <span class="info-value" id="displayTime">
                    <?php echo ($is_edit && $appointment_data['appointment_time']) ? date('h:i A', strtotime($appointment_data['appointment_time'])) : '—'; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Consultation Fee</span>
                <span class="info-value fee-value"><?php echo number_format($lawyer['fees']); ?> PKR</span>
            </div>
            <div class="info-row">
                <span class="info-label">Location</span>
                <span class="info-value"><?php echo htmlspecialchars($lawyer['city']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Experience</span>
                <span class="info-value"><?php echo $lawyer['experience']; ?> years</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rating</span>
                <span class="info-value">★ <?php echo $lawyer['avg_rating'] ?: 'New'; ?></span>
            </div>
        </div>

        <!-- RIGHT PANEL: Booking form -->
        <div class="form-panel">
            <h2 class="form-title"><?php echo $is_edit ? 'Reschedule Consultation' : 'Book Consultation'; ?></h2>

            <?php if ($error): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Select Date</label>
                    <input type="date" name="date" class="form-control" id="bookingDate" required
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo htmlspecialchars($selected_date); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Select Time</label>
                    <select name="time" class="form-control" id="bookingTime" required>
                        <option value="">Select a date first</option>
                        <?php echo $time_options; ?>
                    </select>
                    <?php if ($selected_date && empty($time_options)): ?>
                        <small style="color: red;">No available slots for this date. Please select another date.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Additional Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Any specific concerns or questions for the lawyer?"><?php echo $is_edit ? htmlspecialchars($appointment_data['booking_message']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn-book"><?php echo $is_edit ? 'UPDATE BOOKING →' : 'CONFIRM BOOKING →'; ?></button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- ============================================================
     MODAL – appears on top of everything (form + footer)
============================================================ -->
<?php if ($show_modal): ?>
<div class="modal-overlay">
    <div class="success-card">
        <i class="fas fa-check-circle"></i>
        <h3><?php echo $is_edit ? 'APPOINTMENT UPDATED' : 'REQUEST DISPATCHED'; ?></h3>
        <p>Your appointment with <strong>Adv. <?php echo htmlspecialchars($booked_lawyer_name); ?></strong> has been <?php echo $is_edit ? 'updated' : 'submitted'; ?>.</p>
        <p>The lawyer will confirm your appointment soon.</p>
        <div class="success-actions">
            <a href="index.php" class="btn-dashboard">DASHBOARD</a>
            <a href="<?php echo BASE_URL; ?>" class="btn-home">HOME</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     JAVASCRIPT: Live update of displayed date/time
============================================================ -->
<script>
document.getElementById('bookingDate').addEventListener('change', function() {
    let date = this.value;
    let lawyer_id = <?php echo $lawyer_id; ?>;
    let edit_id = <?php echo $edit_id; ?>;
    if (date) {
        let url = 'book-appointment.php?id=' + lawyer_id + '&date=' + date;
        if (edit_id > 0) url += '&edit=' + edit_id;
        window.location.href = url;
    }
});

document.getElementById('bookingTime').addEventListener('change', function() {
    let time = this.value;
    if (time) {
        let displayTime = new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        document.getElementById('displayTime').innerHTML = displayTime;
    } else {
        document.getElementById('displayTime').innerHTML = '—';
    }
});

<?php if ($selected_date): ?>
document.addEventListener('DOMContentLoaded', function() {
    let date = '<?php echo $selected_date; ?>';
    let d = new Date(date);
    let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let formattedDate = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    document.getElementById('displayDate').innerHTML = formattedDate;

    <?php if ($is_edit && $appointment_data && $appointment_data['appointment_time']): ?>
    let currentTime = '<?php echo $appointment_data['appointment_time']; ?>';
    let timeSelect = document.getElementById('bookingTime');
    for (let i = 0; i < timeSelect.options.length; i++) {
        if (timeSelect.options[i].value === currentTime) {
            timeSelect.selectedIndex = i;
            let displayTime = new Date('2000-01-01 ' + currentTime).toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
            document.getElementById('displayTime').innerHTML = displayTime;
            break;
        }
    }
    <?php endif; ?>
});
<?php endif; ?>
</script>