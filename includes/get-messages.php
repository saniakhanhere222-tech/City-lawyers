<?php
/**
 * AJAX Endpoint – Get all messages for an appointment
 * 
 * Called from customer/chat.php and lawyer/chat.php via fetch().
 * Expects GET request with:
 *   - appointment_id
 * 
 * Returns JSON: { success: true, messages: [...] }
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
// 2. Get appointment ID from URL
// ============================================================
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid appointment ID']);
    exit();
}

// ============================================================
// 3. Fetch messages using the helper function
// ============================================================
$messages = getChatMessages($appointment_id);

// ============================================================
// 4. Format messages for JSON (escape special characters)
// ============================================================
foreach ($messages as &$msg) {
    $msg['message'] = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
}

// ============================================================
// 5. Return JSON response
// ============================================================
echo json_encode([
    'success' => true,
    'messages' => $messages
]);