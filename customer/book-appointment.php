<?php
// ============================================================
// CUSTOMER - BOOK APPOINTMENT (2-Step Booking)
// ============================================================
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
// 4.5 GET AVAILABLE DAYS FOR THIS LAWYER
// ============================================================
$availableDays = [];
$dayStmt = $conn->prepare("
    SELECT DISTINCT day_of_week 
    FROM slots 
    WHERE lawyer_id = ? AND is_available = 1 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
");
$dayStmt->execute([$lawyer_id]);
$availableDays = $dayStmt->fetchAll(PDO::FETCH_COLUMN);

// Generate available dates for the next 30 days
$availableDateList = [];
if (!empty($availableDays)) {
    $today = new DateTime();
    $endDate = new DateTime();
    $endDate->modify('+30 days');
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($today, $interval, $endDate);
    
    foreach ($period as $date) {
        $dayName = $date->format('l');
        if (in_array($dayName, $availableDays)) {
            $availableDateList[] = $date->format('Y-m-d');
        }
    }
}
$availableDatesJson = json_encode($availableDateList);

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
// 6. Process form submission (Step 1 & Step 2)
// ============================================================
$error = '';
$show_success = false;
$booked_lawyer_name = '';
$success_message = '';

// Step 1: Date, Time & Message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step']) && $_POST['step'] == '1') {
    $date    = $_POST['date'];
    $time    = $_POST['time'];
    $message = trim($_POST['message'] ?? '');
    $edit_post_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if (empty($date) || empty($time)) {
        $error = "Please select both date and time.";
    } else {
        $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE lawyer_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $checkStmt->execute([$lawyer_id, $date, $time]);
        $is_booked = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

        if ($is_booked) {
            $error = "This time slot is already booked. Please select another time.";
        } else {
            $_SESSION['booking_data'] = [
                'date' => $date,
                'time' => $time,
                'message' => $message,
                'edit_id' => $edit_post_id
            ];
            
            header("Location: book-appointment.php?id=" . $lawyer_id . "&step=2" . ($edit_post_id > 0 ? "&edit=" . $edit_post_id : ""));
            exit();
        }
    }
}

// Step 2: Payment Method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step']) && $_POST['step'] == '2') {
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $receipt_image = '';
    $account_details = '';
    $bank_name = '';
    $account_number = '';
    $account_holder_name = '';
    
    $booking_data = $_SESSION['booking_data'] ?? null;
    
    if (!$booking_data) {
        header("Location: search.php");
        exit();
    }
    
    $date = $booking_data['date'];
    $time = $booking_data['time'];
    $message = $booking_data['message'];
    $edit_post_id = $booking_data['edit_id'] ?? 0;
    
    if ($payment_method == 'jazzcash' || $payment_method == 'easypaisa') {
        $account_number = trim($_POST['account_number'] ?? '');
        $account_holder_name = trim($_POST['account_holder_name'] ?? '');
        $account_details = $account_holder_name . ' - ' . $account_number;
    } elseif ($payment_method == 'bank_transfer') {
        $bank_name = trim($_POST['bank_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_holder_name = trim($_POST['account_holder_name'] ?? '');
        $account_details = $bank_name . ' - ' . $account_holder_name . ' - ' . $account_number;
    }
    
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
        $upload_dir = "../uploads/receipts/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION));
        $new_filename = time() . '_' . uniqid() . '.' . $ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $destination)) {
            $receipt_image = $new_filename;
        }
    }
    
    try {
        $conn->beginTransaction();
        
        if ($edit_post_id > 0) {
            // Reschedule
            $updateStmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, booking_message = ?, status = 'pending', reschedule_count = reschedule_count + 1 WHERE id = ? AND customer_id = ?");
            $updateStmt->execute([$date, $time, $message, $edit_post_id, $customer_id]);
            $appointment_id = $edit_post_id;
            
            $checkPaymentStmt = $conn->prepare("SELECT id FROM payments WHERE appointment_id = ?");
            $checkPaymentStmt->execute([$appointment_id]);
            if ($checkPaymentStmt->fetch()) {
                $payStmt = $conn->prepare("UPDATE payments SET payment_method = ?, status = 'pending', receipt_image = ?, account_details = ?, bank_name = ?, account_number = ?, account_holder_name = ? WHERE appointment_id = ?");
                $payStmt->execute([$payment_method, $receipt_image, $account_details, $bank_name, $account_number, $account_holder_name, $appointment_id]);
            } else {
                $payStmt = $conn->prepare("INSERT INTO payments (appointment_id, customer_id, amount, payment_method, status, receipt_image, account_details, bank_name, account_number, account_holder_name) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
                $payStmt->execute([$appointment_id, $customer_id, $lawyer['fees'], $payment_method, $receipt_image, $account_details, $bank_name, $account_number, $account_holder_name]);
            }
            
            addNotification(
                $lawyer_id,
                'lawyer',
                'rescheduled',
                'Appointment Rescheduled',
                "Customer $customer_name rescheduled to " . date('d M Y', strtotime($date)) . " at " . date('h:i A', strtotime($time)) . " (Payment: $payment_method)",
                'appointments.php',
                'fa-calendar-alt'
            );
            
            $show_success = true;
            $booked_lawyer_name = $lawyer['name'];
            $success_message = "Your appointment has been rescheduled successfully!";
            
        } else {
            // New appointment
            $insertStmt = $conn->prepare("INSERT INTO appointments (lawyer_id, customer_id, appointment_date, appointment_time, booking_message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $insertStmt->execute([$lawyer_id, $customer_id, $date, $time, $message]);
            $appointment_id = $conn->lastInsertId();
            
            $payStmt = $conn->prepare("INSERT INTO payments (appointment_id, customer_id, amount, payment_method, status, receipt_image, account_details, bank_name, account_number, account_holder_name) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
            $payStmt->execute([$appointment_id, $customer_id, $lawyer['fees'], $payment_method, $receipt_image, $account_details, $bank_name, $account_number, $account_holder_name]);
            
            addNotification(
                $lawyer_id,
                'lawyer',
                'new_request',
                'New Appointment Request',
                "Customer $customer_name requested appointment on " . date('d M Y', strtotime($date)) . " at " . date('h:i A', strtotime($time)) . " (Payment: $payment_method)",
                'appointments.php',
                'fa-clock'
            );
            
            $show_success = true;
            $booked_lawyer_name = $lawyer['name'];
            $success_message = "Your appointment has been booked successfully!";
        }
        
        $conn->commit();
        unset($_SESSION['booking_data']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Booking failed. Please try again. " . $e->getMessage();
    }
}

// ============================================================
// 7. Determine current step
// ============================================================
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$is_step2 = ($current_step == 2);

if ($is_step2 && !isset($_SESSION['booking_data']) && !$show_success) {
    header("Location: book-appointment.php?id=" . $lawyer_id);
    exit();
}

$booking_data = $_SESSION['booking_data'] ?? null;

// ============================================================
// 8. Include header
// ============================================================
include '../includes/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/book-appointment.css">


<!-- ============================================================
     SUCCESS MESSAGE - Show at top when booking is successful
============================================================ -->
<?php if ($show_success): ?>
<div class="success-alert" style="max-width: 1000px; margin: 20px auto 0; padding: 0 20px;">
    <div class="success-alert-content" style="background: #dce9d7; border-left: 4px solid #2e7d32; padding: 16px 20px; border-radius: 4px; display: flex; align-items: flex-start; gap: 14px;">
        <i class="fas fa-check-circle" style="color: #2e7d32; font-size: 22px; margin-top: 2px;"></i>
        <div>
            <div style="font-weight: 600; color: #1e3a1e; font-size: 16px;">
                <?php echo $success_message; ?>
            </div>
            <div style="color: #2e5b2e; font-size: 14px; margin-top: 2px;">
                Your appointment with <strong>Adv. <?php echo htmlspecialchars($booked_lawyer_name); ?></strong> has been submitted. 
                The lawyer will confirm your appointment soon.
            </div>
            <div style="margin-top: 10px;">
                <a href="my-appointments.php" class="btn btn-sm" style="background: #2e7d32; color: white; padding: 6px 18px; text-decoration: none; font-size: 12px; border-radius: 4px; display: inline-block;">
                    <i class="fas fa-calendar-check"></i> View My Appointments
                </a>
                <a href="<?php echo BASE_URL; ?>" class="btn btn-sm" style="background: transparent; color: #2e5b2e; padding: 6px 18px; text-decoration: none; font-size: 12px; border-radius: 4px; border: 1px solid #2e7d32; display: inline-block; margin-left: 8px;">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     LOADER OVERLAY
============================================================ -->
<div class="loader-overlay" id="loaderOverlay">
    <div class="spinner"></div>
    <p>Processing your booking...</p>
</div>

<!-- ============================================================
     STEP INDICATOR
============================================================ -->
<div class="step-indicator-wrapper">
    <div class="step-indicator">
        <div class="step <?php echo $current_step == 1 ? 'active' : 'completed'; ?>">
            <span class="num"><?php echo $current_step == 1 ? '1' : '✓'; ?></span>
            <span class="step-label">Appointment</span>
        </div>
        <div class="step-line <?php echo $current_step == 2 ? 'completed' : ''; ?>"></div>
        <div class="step <?php echo $current_step == 2 ? 'active' : ''; ?>">
            <span class="num">2</span>
            <span class="step-label">Payment</span>
        </div>
    </div>
</div>

<!-- ============================================================
     BOOKING CONTAINER
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
                    <?php 
                    if ($is_step2 && $booking_data) {
                        echo date('l, d M Y', strtotime($booking_data['date']));
                    } elseif ($selected_date) {
                        echo date('l, d M Y', strtotime($selected_date));
                    } else {
                        echo '—';
                    }
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Selected Time</span>
                <span class="info-value" id="displayTime">
                    <?php 
                    if ($is_step2 && $booking_data) {
                        echo date('h:i A', strtotime($booking_data['time']));
                    } elseif ($is_edit && $appointment_data['appointment_time']) {
                        echo date('h:i A', strtotime($appointment_data['appointment_time']));
                    } else {
                        echo '—';
                    }
                    ?>
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
            
            <!-- Step 1 -->
            <?php if ($current_step == 1): ?>
            
            <h2 class="form-title"><?php echo $is_edit ? 'Reschedule Your Appointment' : 'Book Your Appointment'; ?></h2>

            <?php if ($error): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="step1Form">
                <input type="hidden" name="step" value="1">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Select Date</label>
                    <input type="date" name="date" class="form-control" id="bookingDate" required
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo htmlspecialchars($selected_date); ?>">
                    
                    <?php if (!empty($availableDays)): ?>
                        <div class="available-days-hint">
                            <i class="fas fa-calendar-check"></i>
                            <strong>Available days:</strong>
                            <?php echo implode(', ', $availableDays); ?>
                            <span class="hint-text">(Click a date to see available times)</span>
                        </div>
                    <?php else: ?>
                        <div style="padding: 10px 14px; background: #fff3e0; border-left: 3px solid #e65100; margin-top: 8px; font-size: 13px; color: #e65100;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            This lawyer has no availability slots set yet.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Select Time</label>
                    <select name="time" class="form-control" id="bookingTime" required>
                        <option value="">Select a date first</option>
                        <?php echo $time_options; ?>
                    </select>
                    
                    <?php if ($selected_date && empty($time_options)): ?>
                        <div style="padding: 8px 12px; background: #fff3e0; border-left: 3px solid #e65100; margin-top: 8px; font-size: 13px; color: #e65100;">
                            <i class="fas fa-info-circle"></i> 
                            No available slots for this date. Please select another date.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Additional Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Any specific concerns or questions for the lawyer?"><?php echo $is_edit ? htmlspecialchars($appointment_data['booking_message']) : ''; ?></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-next" id="step1Submit">
                        NEXT <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>

            <?php else: ?>
            
            <!-- Step 2: Payment Method -->
            <h2 class="form-title">Payment Method</h2>

            <?php if ($error): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="amount-display">
                <div class="label">Consultation Fee</div>
                <div class="amount"><?php echo number_format($lawyer['fees']); ?> PKR</div>
            </div>

            <form method="POST" id="step2Form" enctype="multipart/form-data">
                <input type="hidden" name="step" value="2">

                <div class="payment-methods">
                    <label class="payment-method-card selected" id="method-cash">
                        <input type="radio" name="payment_method" value="cash" checked>
                        <i class="fas fa-money-bill-wave"></i>
                        <div class="method-name">Cash</div>
                        <div class="method-desc">Pay at appointment</div>
                    </label>
                    <label class="payment-method-card" id="method-jazzcash">
                        <input type="radio" name="payment_method" value="jazzcash">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="method-name">JazzCash</div>
                        <div class="method-desc">Mobile wallet</div>
                    </label>
                    <label class="payment-method-card" id="method-easypaisa">
                        <input type="radio" name="payment_method" value="easypaisa">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="method-name">EasyPaisa</div>
                        <div class="method-desc">Mobile wallet</div>
                    </label>
                    <label class="payment-method-card" id="method-bank">
                        <input type="radio" name="payment_method" value="bank_transfer">
                        <i class="fas fa-university"></i>
                        <div class="method-name">Bank Transfer</div>
                        <div class="method-desc">Direct bank transfer</div>
                    </label>
                </div>

                <div class="payment-details" id="paymentDetails">
                    <div id="cash-details">
                        <p style="color: var(--text-light); font-size: 13px; margin: 0;">
                            <i class="fas fa-info-circle"></i> 
                            You will pay the consultation fee directly to the lawyer at the time of appointment.
                        </p>
                    </div>
                    
                    <div id="mobile-details" style="display: none;">
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="account_number" placeholder="e.g., 03XX-XXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>Account Holder Name</label>
                            <input type="text" name="account_holder_name" placeholder="Full name as per account">
                        </div>
                    </div>
                    
                    <div id="bank-details" style="display: none;">
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" placeholder="e.g., HBL, UBL, MCB">
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="account_number" placeholder="Bank account number">
                        </div>
                        <div class="form-group">
                            <label>Account Holder Name</label>
                            <input type="text" name="account_holder_name" placeholder="Full name as per bank account">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-upload"></i> Upload Payment Receipt</label>
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click or drag to upload receipt (optional)</p>
                        <div class="file-name" id="fileName"></div>
                        <input type="file" name="receipt_image" accept="image/*,.pdf" id="receiptInput">
                    </div>
                    <small style="color: var(--text-light); font-size: 11px;">Accepted formats: JPG, PNG, PDF (Max 5MB)</small>
                </div>

                <div style="display: flex; justify-content: space-between; gap: 12px; margin-top: 20px;">
                    <a href="book-appointment.php?id=<?php echo $lawyer_id; ?>" class="btn-nav btn-back">
                        <i class="fas fa-arrow-left"></i> BACK
                    </a>
                    <button type="submit" class="btn-book" id="step2Submit">
                        <i class="fas fa-check-circle"></i> CONFIRM BOOKING
                    </button>
                </div>
            </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- ============================================================
     JAVASCRIPT
============================================================ -->
<script>
// ============================================================
// AVAILABLE DATES - For validation
// ============================================================
const availableDates = <?php echo $availableDatesJson; ?>;

function isDateAvailable(dateStr) {
    return availableDates.includes(dateStr);
}

// ============================================================
// DATE CHANGE - Reload page with selected date
// ============================================================
const bookingDate = document.getElementById('bookingDate');
if (bookingDate) {
    bookingDate.addEventListener('change', function() {
        let date = this.value;
        let lawyer_id = <?php echo $lawyer_id; ?>;
        let edit_id = <?php echo $edit_id; ?>;
        
        if (date) {
            if (!isDateAvailable(date) && <?php echo !empty($availableDays) ? 'true' : 'false'; ?>) {
                alert('No available slots for this date. Please select a different date.\n\nAvailable days: <?php echo implode(', ', $availableDays); ?>');
                this.value = '';
                document.getElementById('bookingTime').innerHTML = '<option value="">Select a date first</option>';
                return;
            }
            
            let url = 'book-appointment.php?id=' + lawyer_id + '&date=' + date;
            if (edit_id > 0) url += '&edit=' + edit_id;
            window.location.href = url;
        }
    });
}

// ============================================================
// TIME CHANGE - Update displayed time
// ============================================================
const bookingTime = document.getElementById('bookingTime');
if (bookingTime) {
    bookingTime.addEventListener('change', function() {
        let time = this.value;
        if (time) {
            let displayTime = new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
            document.getElementById('displayTime').innerHTML = displayTime;
        } else {
            document.getElementById('displayTime').innerHTML = '—';
        }
    });
}

// ============================================================
// STEP 1 - Show loader on submit
// ============================================================
const step1Form = document.getElementById('step1Form');
if (step1Form) {
    step1Form.addEventListener('submit', function() {
        document.getElementById('loaderOverlay').classList.add('active');
    });
}

// ============================================================
// STEP 2 - Show loader on submit
// ============================================================
const step2Form = document.getElementById('step2Form');
if (step2Form) {
    step2Form.addEventListener('submit', function() {
        document.getElementById('loaderOverlay').classList.add('active');
    });
}

// ============================================================
// PAYMENT METHOD SELECTION
// ============================================================
document.querySelectorAll('.payment-method-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.payment-method-card').forEach(function(c) {
            c.classList.remove('selected');
        });
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
        
        const method = this.querySelector('input[type="radio"]').value;
        document.getElementById('cash-details').style.display = method === 'cash' ? 'block' : 'none';
        document.getElementById('mobile-details').style.display = (method === 'jazzcash' || method === 'easypaisa') ? 'block' : 'none';
        document.getElementById('bank-details').style.display = method === 'bank_transfer' ? 'block' : 'none';
    });
});

// ============================================================
// RECEIPT UPLOAD
// ============================================================
const uploadArea = document.getElementById('uploadArea');
const receiptInput = document.getElementById('receiptInput');
const fileName = document.getElementById('fileName');

if (uploadArea && receiptInput) {
    uploadArea.addEventListener('click', function() {
        receiptInput.click();
    });
    
    receiptInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
        } else {
            fileName.textContent = '';
        }
    });
}

// ============================================================
// DOM READY - Format initial date and pre-select time
// ============================================================
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