<?php
// ============================================================
// DASHBOARD SIDEBAR - Reusable Navigation
// ============================================================
// Role-based sidebar (admin/lawyer/customer) with:
// - User avatar + name display
// - Role-specific menu items
// - Active menu highlighting ($page_title)
// - Logout with confirmation
//
// Expected: $user_type, $user_name, $page_title, BASE_URL
// Security: htmlspecialchars() on all output
// ============================================================
?>

<!-- ===================================================
     SIDEBAR (reusable)
     =================================================== -->
<aside class="dashboard-sidebar" id="dashboardSidebar">

    <!-- Brand header -->
    <!-- <div class="sidebar-brand">
        <img src="<//?php echo BASE_URL; ?>assets/images/legalFlowlogotransp.png" alt="<//?php echo SITE_NAME; ?>">
        <span class="brand-name"><//?php echo SITE_NAME; ?></span>
    </div> -->

    <!-- ===================================================
     USER AVATAR & NAME (Side by Side)
     =================================================== -->
<div class="sidebar-user">
    <div class="sidebar-avatar">
        <?php if ($user_type === 'admin'): ?>
            <i class="fas fa-user-shield"></i>
        <?php elseif ($user_type === 'lawyer'): ?>
            <i class="fas fa-user-tie"></i>
        <?php else: ?>
            <i class="fas fa-user-circle"></i>
        <?php endif; ?>
    </div>
    <span class="sidebar-username"><?php echo htmlspecialchars($user_name); ?></span>
</div>


    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">

            <?php if ($user_type === 'admin'): ?>
                <!-- ADMIN MENU -->
                <li>
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="<?php echo ($page_title == 'Admin Dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>admin/manage-lawyers.php" class="<?php echo ($page_title == 'Manage Lawyers') ? 'active' : ''; ?>">
                        <i class="fas fa-gavel"></i>
                        <span class="nav-text">Manage Lawyers</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>admin/manage-customers.php" class="<?php echo ($page_title == 'Manage Customers') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Manage Customers</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>admin/manage-appointments.php" class="<?php echo ($page_title == 'Manage Appointments') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>admin/manage-content.php" class="<?php echo ($page_title == 'Manage Homepage Content') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Homepage</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>admin/notifications.php" class="<?php echo ($page_title == 'Notifications') ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        <span class="nav-text">Notifications</span>
                    </a>
                </li>
                <li>
                   <a href="<?php echo BASE_URL; ?>admin/manage-reviews.php" class="<?php echo ($page_title == 'Manage Reviews') ? 'active' : ''; ?>">
                      <i class="fas fa-star"></i>
                     <span class="nav-text">Manage Reviews</span>
                </a>
                 </li>
                

            <?php elseif ($user_type === 'lawyer'): ?>
                <!-- LAWYER MENU -->
                <li>
                    <a href="<?php echo BASE_URL; ?>lawyer/index.php" class="<?php echo ($page_title == 'Lawyer Dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>lawyer/appointments.php" class="<?php echo ($page_title == 'My Appointments') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">My Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>lawyer/manage-slots.php" class="<?php echo ($page_title == 'Manage Slots') ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        <span class="nav-text">Manage Slots</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>lawyer/profile.php" class="<?php echo ($page_title == 'Edit Profile') ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog"></i>
                        <span class="nav-text">Profile</span>
                    </a>
                </li>
                <li>
                  <a href="<?php echo BASE_URL; ?>lawyer/receipts.php" class="<?php echo ($page_title == 'Payment Receipts') ? 'active' : ''; ?>">
                 <i class="fas fa-receipt"></i>
                <span class="nav-text">Receipts</span>
                 </a>
               </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>lawyer/notifications.php" class="<?php echo ($page_title == 'Notifications') ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        <span class="nav-text">Notifications</span>
                    </a>
                </li>

            <?php elseif ($user_type === 'customer'): ?>
                <!-- CUSTOMER MENU -->
                <li>
                    <a href="<?php echo BASE_URL; ?>customer/index.php" class="<?php echo ($page_title == 'Customer Dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>customer/my-appointments.php" class="<?php echo ($page_title == 'My Appointments') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">My Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>customer/search.php" class="<?php echo ($page_title == 'Find Lawyers') ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i>
                        <span class="nav-text">Find Lawyers</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>customer/notifications.php" class="<?php echo ($page_title == 'Notifications') ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        <span class="nav-text">Notifications</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Divider before logout -->
            <li class="menu-divider"></li>
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>

        </ul>
    </nav>

    <!-- Toggle button -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>

</aside>