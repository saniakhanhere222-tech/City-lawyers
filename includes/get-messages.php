<?php
// ============================================================
// Fetches all messages for an appointment via GET request.
// Requires: appointment_id (GET parameter)
// Returns: JSON { success: true, messages: [...] }
// Used by: customer/chat.php, lawyer/chat.php (auto-refresh)
// Security: Session auth, XSS prevention with htmlspecialchars()
// Related: getChatMessages() in functions.php
// ============================================================
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