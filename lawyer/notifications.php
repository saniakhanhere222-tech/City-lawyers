<?php
// ============================================================
// LAWYER - NOTIFICATIONS
// ============================================================
// This page displays all notifications for the logged-in lawyer.
// Uses the same shared includes/notifications.php file.
//
// Differences from customer notifications:
// - Different session variable: $_SESSION['lawyer_id']
// - Different sidebar and dashboard links
// - Notification types: new_request, confirmed, cancelled, etc.
// - Redirects to lawyer dashboard instead of customer
//
// Related Files:
// - ../includes/notifications.php - Shared notification logic
// ============================================================
include '../includes/notifications.php';
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
?>