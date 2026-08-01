<?php
/**
 * AJAX Endpoint – Mark all messages as read for an appointment
 * 
 * Called from customer/chat.php and lawyer/chat.php when chat is opened.
 * Expects POST request with:
 *   - appointment_id
 * 
 * Returns JSON: { success: true/false }
 */
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// ============================================================
// 1. Check if user is logged in
// ============================================================
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['lawyer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// ============================================================
// 2. Determine who is the receiver (who is reading the messages)
// ============================================================
if (isset($_SESSION['customer_id'])) {
    $receiver_id = $_SESSION['customer_id'];
    $receiver_type = 'customer';
} else {
    $receiver_id = $_SESSION['lawyer_id'];
    $receiver_type = 'lawyer';
}

// ============================================================
// 3. Get appointment ID from POST
// ============================================================
$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid appointment ID']);
    exit();
}

// ============================================================
// 4. Mark messages as read using the helper function
// ============================================================
$success = markMessagesAsRead($appointment_id, $receiver_id, $receiver_type);

// ============================================================
// 5. Return JSON response
// ============================================================
echo json_encode(['success' => $success]);