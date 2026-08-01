<?php
/**
 * AJAX endpoint – Mark a notification as read
 * 
 * Called from header.php dropdown via fetch().
 * Expects POST request with notification_id.
 * Returns JSON response.
 */
session_start();
require_once 'config.php';

// ============================================================
// 1. Check if user is logged in
// ============================================================
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['lawyer_id']) && !isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// ============================================================
// 2. Get notification ID from POST
// ============================================================
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($notification_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit();
}

// ============================================================
// 3. Mark as read using the helper function
// ============================================================
$success = markNotificationRead($notification_id);

// ============================================================
// 4. Return JSON response
// ============================================================
header('Content-Type: application/json');
echo json_encode(['success' => $success]);