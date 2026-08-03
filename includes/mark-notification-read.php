<?php
// ============================================================
// AJAX ENDPOINT - MARK NOTIFICATION AS READ
// ============================================================
// Marks a single notification as read via POST request.
// Requires: notification_id (POST parameter)
// Returns: JSON { success: true/false }
// Called from header.php dropdown via AJAX.
// Security: Session auth, PDO prepared statements.
// Related: markNotificationRead() in functions.php
// ============================================================

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