<?php
/**
 * AJAX Endpoint – Send a new message
 * 
 * Called from customer/chat.php and lawyer/chat.php via fetch().
 * Expects POST request with:
 *   - appointment_id
 *   - receiver_id
 *   - receiver_type (customer or lawyer)
 *   - message
 * 
 * Returns JSON: { success: true/false, error: 'message' }
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
// 2. Determine sender (who is sending the message)
// ============================================================
if (isset($_SESSION['customer_id'])) {
    $sender_id = $_SESSION['customer_id'];
    $sender_type = 'customer';
} else {
    $sender_id = $_SESSION['lawyer_id'];
    $sender_type = 'lawyer';
}

// ============================================================
// 3. Get POST data
// ============================================================
$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$receiver_type = isset($_POST['receiver_type']) ? $_POST['receiver_type'] : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// ============================================================
// 4. Validate data
// ============================================================
if ($appointment_id <= 0 || $receiver_id <= 0 || empty($receiver_type) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

// ============================================================
// 5. Send the message using the helper function
// ============================================================
$success = sendMessage(
    $appointment_id,
    $sender_id,
    $sender_type,
    $receiver_id,
    $receiver_type,
    $message
);

// ============================================================
// 6. Return JSON response
// ============================================================
echo json_encode(['success' => $success]);