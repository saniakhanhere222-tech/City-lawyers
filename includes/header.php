<?php
// ============================================================
// GLOBAL HEADER - Navigation + HTML Head
// ============================================================
// Provides: Meta/HTML head, CSS, navbar for guests and
// logged-in users (customer/lawyer/admin), notifications
// dropdown with AJAX mark-as-read, offcanvas mobile menu.
//
// Usage: include at top of every page.
// Expected: $page_title, $page_layout, $footer_css (optional)
// Related: config.php, functions.php
// ============================================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title ?? 'Find Best Lawyers'; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/forms.css">

 <!-- ============================================================
         CONDITIONAL FOOTER CSS 
============================================================ -->
    <?php if (isset($footer_css) && $footer_css === 'dashboard'): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard-footer.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
    <?php endif; ?>

  
</head>
<body>

<?php
// ============================================================
// DETERMINE WHO IS LOGGED IN
// ============================================================
$user_type      = null;
$user_name      = null;
$dashboard_link = null;
$user_id        = null;

if (isset($_SESSION['customer_id'])) {
    $user_type      = 'customer';
    $user_name      = $_SESSION['customer_name'];
    $dashboard_link = BASE_URL . 'customer/index.php';
    $user_id        = $_SESSION['customer_id'];
} elseif (isset($_SESSION['lawyer_id'])) {
    $user_type      = 'lawyer';
    $user_name      = $_SESSION['lawyer_name'];
    $dashboard_link = BASE_URL . 'lawyer/index.php';
    $user_id        = $_SESSION['lawyer_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $user_type      = 'admin';
    $user_name      = 'Admin';
    $dashboard_link = BASE_URL . 'admin/index.php';
    $user_id        = $_SESSION['admin_id'];
}

// ============================================================
// FETCH NOTIFICATIONS FOR DROPDOWN (only if logged in)
// ============================================================
$unread_count = 0;
$latest_notifications = [];

if ($user_type && $user_id) {
    $unread_count = getUnreadCount($user_id, $user_type);
    $latest_notifications = getLatestNotifications($user_id, $user_type, 10);
}
?>

<!-- ============================================================
     TOP NAVBAR
     ============================================================ -->
<div class="top-navbar">
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container-fluid navbar-container">

            <!-- Logo -->
            <a class="navbar-brand luxury-logo d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>assets/images/citylawyers_logo.png" alt="Logo" class="site-logo">
                <div class="logo-text-wrapper">
                    <span class="logo-title"><?php echo SITE_NAME; ?></span>
                </div>
            </a>

            <!-- Mobile hamburger - opens offcanvas (only for guest) -->
            <?php if (!$user_type): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            <?php endif; ?>

            <!-- ======================================================
                 DESKTOP NAVBAR (hidden on mobile)
                 ====================================================== -->
            <?php if (!$user_type): ?>
                <div class="d-none d-lg-flex flex-column align-items-end">
                    <!-- Row 1: Main navigation -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link right-to-left" href="<?php echo BASE_URL; ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link right-to-left" href="<?php echo BASE_URL; ?>customer/search.php">Find Lawyers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link right-to-left" href="<?php echo BASE_URL; ?>contact.php">Contact Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-success text-white px-3 rounded-0" href="<?php echo BASE_URL; ?>register.php">
                                <i class="fas fa-user-plus me-2"></i> Get Registered
                            </a>
                        </li>
                    </ul>
                    <!-- Row 2: Login links (desktop) -->
                    <div class="guest-row">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>customer/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login as User
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>lawyer/login.php">
                            <i class="fas fa-gavel"></i> Login as Lawyer
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- ======================================================
                     LOGGED-IN NAVBAR – Bell Dropdown + User Dropdown
                     ====================================================== -->
                <div class="d-flex align-items-center ms-auto">

                    <!-- Notification Bell Dropdown -->
                    <div class="dropdown me-2">
                        <a class="nav-link position-relative" href="#" id="notificationBell" 
                           data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationBell">
                            <div class="dropdown-header">
                                <span>Notifications</span>
                                <?php if ($unread_count > 0): ?>
                                    <a href="<?php echo BASE_URL . $user_type; ?>/notifications.php?mark_all_read=1" onclick="return confirm('Mark all as read?')">Mark all as read</a>
                                <?php endif; ?>
                            </div>

                            <div class="notification-list">
                                <?php if (count($latest_notifications) > 0): ?>
                                    <?php foreach ($latest_notifications as $notif): ?>
                                        <a href="<?php echo $notif['link'] ? $notif['link'] : '#'; ?>" 
                                           class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>"
                                           onclick="event.stopPropagation(); markNotificationRead(<?php echo $notif['id']; ?>, '<?php echo $notif['link'] ?: '#'; ?>')">
                                            <div class="n-icon">
                                                <i class="fas <?php echo getNotificationIcon($notif['type']); ?>"></i>
                                            </div>
                                            <div class="n-content">
                                                <div class="n-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                                <div class="n-message"><?php echo htmlspecialchars(substr($notif['message'], 0, 80)); ?></div>
                                                <div class="n-time"><?php echo timeAgo($notif['created_at']); ?></div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-bell-slash"></i>
                                        <p>No notifications yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="dropdown-footer">
                                <a href="<?php echo BASE_URL . $user_type; ?>/notifications.php">View all notifications →</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-lg"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo $dashboard_link; ?>">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- end container -->
    </nav>
</div><!-- end .top-navbar -->

<!-- ============================================================
     OFF CANVAS MENU – Mobile only (doesn't push content)
     ============================================================ -->
<?php if (!$user_type): ?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
    <div class="offcanvas-header offcanvas-header-custom">
        <span class="logo-title" style="font-size: 1.3rem;"><?php echo SITE_NAME; ?></span>
        <button type="button" class="btn-close-custom" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="offcanvas-body offcanvas-body-custom">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>customer/search.php">Find Lawyers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>contact.php">Contact Us</a>
            </li>
            <li class="nav-item">
                <a class="btn-register-offcanvas" href="<?php echo BASE_URL; ?>register.php">
                    <i class="fas fa-user-plus me-2"></i> Get Registered
                </a>
            </li>
        </ul>
        
        <div class="guest-row-offcanvas">
            <a class="nav-link" href="<?php echo BASE_URL; ?>customer/login.php">
                <i class="fas fa-sign-in-alt"></i> Login as User
            </a>
            <a class="nav-link" href="<?php echo BASE_URL; ?>lawyer/login.php">
                <i class="fas fa-gavel"></i> Login as Lawyer
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     JAVASCRIPT – Mark notification as read via AJAX
     ============================================================ -->
<script>
function markNotificationRead(notificationId, redirectUrl) {
    fetch('<?php echo BASE_URL; ?>includes/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent) - 1;
                if (currentCount > 0) {
                    badge.textContent = currentCount;
                } else {
                    badge.remove();
                }
            }
            if (redirectUrl && redirectUrl !== '#') {
                window.location.href = redirectUrl;
            }
        }
    })
    .catch(error => {
        if (redirectUrl && redirectUrl !== '#') {
            window.location.href = redirectUrl;
        }
    });
}
</script>

<!-- ============================================================
     HEADER AUTO-HIDE ON SCROLL - Only for public pages
============================================================ -->
<?php if (!isset($page_layout) || $page_layout !== 'fluid'): ?>
    <script src="<?php echo BASE_URL; ?>assets/js/header.js"></script>
<?php endif; ?>

<!-- ============================================================
     MAIN CONTENT WRAPPER
     ============================================================ -->
<main>

<?php if (isset($page_layout) && $page_layout === 'fluid'): ?>
    <div class="container-fluid p-0">
<?php else: ?>
    <div class="container">
<?php endif; ?>