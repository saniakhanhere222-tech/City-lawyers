<?php
// ============================================================
// header.php — Global navigation bar + HTML <head>
// Included at the top of every public page.
//
// IMPROVEMENTS:
// - Wrapped navbar inside .legalflow-navbar to avoid CSS conflicts with Bootstrap.
// - Added clear comments for top-navbar (collapsible for mobile).
// - Placeholder for side-navbar (dashboard context).
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title ?? 'Find Best Lawyers'; ?></title>

    <!-- Bootstrap 5 CSS (layout framework) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS (will contain .legalflow-navbar styles to override Bootstrap if needed) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/forms.css">

</head>
<body>

<?php
// ============================================================
// DETERMINE WHO IS LOGGED IN
// ============================================================
$user_type      = null;
$user_name      = null;
$dashboard_link = null;

if (isset($_SESSION['customer_id'])) {
    $user_type      = 'customer';
    $user_name      = $_SESSION['customer_name'];
    $dashboard_link = BASE_URL . 'customer/index.php';
} elseif (isset($_SESSION['lawyer_id'])) {
    $user_type      = 'lawyer';
    $user_name      = $_SESSION['lawyer_name'];
    $dashboard_link = BASE_URL . 'lawyer/index.php';
} elseif (isset($_SESSION['admin_id'])) {
    $user_type      = 'admin';
    $user_name      = 'Admin';
    $dashboard_link = BASE_URL . 'admin/index.php';
}
?>

<!-- ============================================================
     TOP NAVBAR (Collapsible for mobile)
     Wrapped in .legalflow-navbar to isolate custom CSS from Bootstrap.
     Bootstrap classes still work, but any custom CSS will target .legalflow-navbar .navbar etc.
     ============================================================ -->
<div class="legalflow-top-navbar">
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container-fluid navbar-container">

            <!-- Logo -->
            <a class="navbar-brand luxury-logo d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>assets/images/legalFlowlogotransp.png" alt="Logo" class="site-logo">
                <div class="logo-text-wrapper">
                    <span class="logo-title"><?php echo SITE_NAME; ?></span>
                </div>
            </a>

            <!-- Mobile hamburger -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible content -->
            <div class="collapse navbar-collapse" id="navbarNav">

                <?php if ($user_type): ?>
                    <!-- Logged-in navbar -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>customer/search.php">Find Lawyers</a></li>

                        <?php if ($user_type === 'customer'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>customer/my-appointments.php">My Appointments</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user"></i> <?php echo $user_name; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo $dashboard_link; ?>">Dashboard</a></li>
                                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php elseif ($user_type === 'lawyer'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>lawyer/appointments.php">My Appointments</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>lawyer/manage-slots.php">Manage Slots</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-tie"></i> <?php echo $user_name; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo $dashboard_link; ?>">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>lawyer/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php elseif ($user_type === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/manage-lawyers.php">Manage Lawyers</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/manage-appointments.php">Appointments</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-shield"></i> <?php echo $user_name; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo $dashboard_link; ?>">Dashboard</a></li>
                                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                   <!-- Guest navbar (two rows) -->
<div class="d-flex flex-column w-100">
    <!-- Row 1: Main navigation links - right aligned -->
    <div class="d-flex justify-content-end">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link right-to-left" href="<?php echo BASE_URL; ?>">Home</a></li>
            <li class="nav-item"><a class="nav-link right-to-left" href="<?php echo BASE_URL; ?>customer/search.php">Find Lawyers</a></li>
            <li class="nav-item">
                <a class="nav-link btn btn-success text-white px-3 rounded-0" href="<?php echo BASE_URL; ?>register.php">
                    <i class="fas fa-user-plus"></i> Get Registered
                </a>
            </li>
        </ul>
    </div>
    <!-- Row 2: Login links -->
    <div class="guest-row">
        <a class="nav-link" href="<?php echo BASE_URL; ?>customer/login.php"><i class="fas fa-sign-in-alt"></i> Login as User</a>
        <a class="nav-link" href="<?php echo BASE_URL; ?>lawyer/login.php"><i class="fas fa-gavel"></i> Login as Lawyer</a>
    </div>
</div>
                <?php endif; ?>

            </div><!-- end collapse -->
        </div><!-- end container -->
    </nav>
</div><!-- end .legalflow-top-navbar -->


<!-- ============================================================
     MAIN CONTENT WRAPPER
     - For dashboard pages ($dashboard_page === true): use container-fluid p-0 (full width, no padding)
     - For regular pages: use standard container (centered with padding)
     ============================================================ -->
<main>
    <?php if (isset($dashboard_page) && $dashboard_page === true): ?>
        <!-- Dashboard pages: full width, no padding (allows sidebar to touch left edge) -->
        <div class="container-fluid p-0">
    <?php else: ?>
        <!-- Regular pages: standard Bootstrap container with padding and max-width -->
        <div class="container">
    <?php endif; ?>