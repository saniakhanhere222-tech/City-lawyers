<?php
// ============================================================
// NOTIFICATIONS PAGE - SHARED INCLUDE FOR ALL ROLES
// ============================================================
// Reusable notification center for customer, lawyer, admin.
// Features: Unread count badge, filter tabs (All/Unread/Read/type),
// pagination (15 per page), mark as read (single/all).
// User auto-detected from session.
//
// Helper Functions: getNotifications(), getUnreadCount(),
// getNotificationTypes(), markNotificationRead(), markAllRead()
//
// Database: notifications table
// Related: config.php, functions.php, header.php


$page_title = 'Notifications';
$page_layout= 'fluid'; //set in header.php 
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in
// ============================================================
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['lawyer_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Determine user type and ID from session
if (isset($_SESSION['customer_id'])) {
    $user_type = 'customer';
    $user_id = $_SESSION['customer_id'];
    $user_name = $_SESSION['customer_name'];
    $dashboard_link = BASE_URL . 'customer/index.php';
} elseif (isset($_SESSION['lawyer_id'])) {
    $user_type = 'lawyer';
    $user_id = $_SESSION['lawyer_id'];
    $user_name = $_SESSION['lawyer_name'];
    $dashboard_link = BASE_URL . 'lawyer/index.php';
} elseif (isset($_SESSION['admin_id'])) {
    $user_type = 'admin';
    $user_id = $_SESSION['admin_id'];
    $user_name = 'Admin';
    $dashboard_link = BASE_URL . 'admin/index.php';
}

// ============================================================
// 2. Handle Mark as Read (single notification)
// ============================================================
if (isset($_GET['read'])) {
    $notification_id = (int)$_GET['read'];
    markNotificationRead($notification_id);
    header("Location: notifications.php" . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
    exit();
}

// ============================================================
// 3. Handle Mark All as Read
// ============================================================
if (isset($_GET['mark_all_read'])) {
    markAllRead($user_id, $user_type);
    header("Location: notifications.php" . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
    exit();
}

// ============================================================
// 4. Get filter and pagination values
// ============================================================
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;

// ============================================================
// 5. Get notifications using the helper function
// ============================================================
$result = getNotifications($user_id, $user_type, $filter, $page, $perPage);
$notifications = $result['notifications'];
$total_rows = $result['total'];
$total_pages = $result['total_pages'];

// ============================================================
// 6. Get unread count for badge
// ============================================================
$unread_count = getUnreadCount($user_id, $user_type);

// ============================================================
// 7. Get notification types for tabs
// ============================================================
$types = getNotificationTypes($user_id, $user_type);

// ============================================================
// 8. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<style>
/* ========================================
   NOTIFICATIONS PAGE – additional styles
======================================== */
.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
    cursor: pointer;
}
.notification-item:hover {
    background: #f8f6f2;
}
.notification-item.unread {
    background: #faf8f5;
    border-left: 3px solid var(--primary-color);
}
.notification-icon {
    font-size: 22px;
    min-width: 40px;
    text-align: center;
    color: var(--primary-color);
    margin-top: 2px;
}
.notification-content {
    flex: 1;
}
.notification-title {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 3px;
}
.notification-title .badge-type {
    font-size: 9px;
    padding: 2px 8px;
    border-radius: 0;
    margin-left: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.notification-message {
    color: var(--text-light);
    font-size: 13px;
    line-height: 1.5;
}
.notification-time {
    font-size: 11px;
    color: #aaa;
    white-space: nowrap;
    margin-top: 3px;
}
.notification-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.mark-read-btn {
    font-size: 11px;
    color: var(--text-light);
    text-decoration: none;
    padding: 4px 12px;
    border: 1px solid var(--border-color);
    transition: 0.2s;
}
.mark-read-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 48px;
    color: var(--text-light);
    margin-bottom: 15px;
}
.empty-state p {
    color: var(--text-light);
}
.mark-all-btn {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-dark);
    padding: 8px 20px;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 20px;
}
.mark-all-btn:hover {
    background: #f0ebe4;
}
.notification-count {
    background: var(--primary-color);
    color: white;
    padding: 2px 10px;
    font-size: 12px;
    border-radius: 0;
    margin-left: 10px;
}
.filter-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 25px;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
}
.filter-tab {
    padding: 10px 20px;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-light);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: 0.3s;
}
.filter-tab:hover {
    color: var(--primary-color);
}
.filter-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}
.notification-item .status-badge {
    font-size: 9px;
    padding: 2px 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
@media (max-width: 768px) {
    .notification-item {
        flex-wrap: wrap;
        gap: 10px;
    }
    .notification-actions {
        width: 100%;
        justify-content: flex-end;
    }
    .filter-tab {
        padding: 8px 14px;
        font-size: 10px;
    }
}
</style>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Header Card -->
        <div class="dashboard-card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 class="dashboard-title" style="margin:0;">Notifications</h2>
                    <p class="dashboard-subtitle">View all your notifications</p>
                </div>
                <div>
                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_all_read=1<?php echo $filter != 'all' ? '&filter=' . $filter : ''; ?>" class="mark-all-btn" onclick="return confirm('Mark all notifications as read?')">Mark All as Read</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FILTER TABS -->
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=unread" class="filter-tab <?php echo $filter == 'unread' ? 'active' : ''; ?>">
                Unread 
                <?php if ($unread_count > 0): ?>
                    <span class="notification-count"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="?filter=read" class="filter-tab <?php echo $filter == 'read' ? 'active' : ''; ?>">Read</a>
            
            <?php 
            // Dynamic tabs based on notification types present for this user
            $typeLabels = [
                'new_request' => 'New Requests',
                'confirmed' => 'Confirmed',
                'cancelled' => 'Cancelled',
                'completed' => 'Completed',
                'rescheduled' => 'Rescheduled',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'review_request' => 'Review Request',
                'new_lawyer' => 'New Lawyers',
            ];
            foreach ($types as $type): 
                if (isset($typeLabels[$type])): 
            ?>
                <a href="?filter=<?php echo $type; ?>" class="filter-tab <?php echo $filter == $type ? 'active' : ''; ?>"><?php echo $typeLabels[$type]; ?></a>
            <?php endif; endforeach; ?>
        </div>

        <!-- NOTIFICATIONS LIST -->
        <div class="dashboard-card">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                         onclick="window.location.href='<?php echo $notif['link'] ? BASE_URL . $user_type . '/' . $notif['link'] : '#'; ?>'">
                        
                        <div class="notification-icon">
                            <i class="fas <?php echo getNotificationIcon($notif['type']); ?>"></i>
                        </div>

                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <span class="status-badge <?php echo getNotificationTypeClass($notif['type']); ?>">
                                    <?php echo ucfirst($notif['type']); ?>
                                </span>
                            </div>
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </div>
                            <div class="notification-time">
                                <?php echo timeAgo($notif['created_at']); ?>
                            </div>
                        </div>

                        <div class="notification-actions">
                            <?php if (!$notif['is_read']): ?>
                                <a href="?read=<?php echo $notif['id']; ?>&filter=<?php echo $filter; ?>" 
                                   class="mark-read-btn" 
                                   onclick="event.stopPropagation();"
                                   title="Mark as read">Mark Read</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-wrap">
                    <?php if ($page > 1): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page-1; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page+1; ?>" class="page-link">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications found.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>