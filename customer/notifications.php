<?php
// ============================================================
// CUSTOMER - NOTIFICATIONS
// ============================================================
// This page displays all notifications for the logged-in customer.
// It uses the shared includes/notifications.php which works for
// all user types (customer, lawyer, admin).
//
// The shared include file automatically detects the user type
// from session variables and displays the appropriate notifications.
//
// Features:
// - Authentication required (customer only)
// - Shared notification system with all user types
// - Mark as read (individual and all)
// - Filter by status and type
// - Pagination (15 per page)
// - Dynamic filter tabs
// - Time-ago formatting
// - Click to navigate to related pages
//
// How it works:
// 1. User must be logged in as customer
// 2. The include file detects customer session
// 3. Fetches notifications for this customer only
// 4. Displays in a unified interface
// 5. All actions update the notifications table
//
// Database Tables Used:
// - notifications (all user types share same table)
// - Differentiated by recipient_type = 'customer'
//
// Related Files:
// - ../includes/notifications.php - Shared notification logic
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/dashboard-footer.php - Dashboard footer
// - assets/css/dashboard.css - Dashboard styling
// - assets/css/tables.css - Table styling
// - assets/css/sidebar.css - Sidebar styling
// ============================================================
include '../includes/notifications.php';
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
?>