<?php
// ============================================================
// CUSTOMER - MY APPOINTMENTS
// ============================================================
// This page manages all customer appointments with tabbed interface:
//
// 1. Tabs:
//    - Upcoming: Pending + Confirmed appointments
//    - Completed: Completed appointments without review
//    - Reviewed: Completed appointments with reviews
//
// 2. Actions by Tab:
//    - Upcoming: Cancel, Edit, Chat, View Profile
//    - Completed: Write Review, View Profile (with checkbox for bulk delete)
//    - Reviewed: View Profile only (with checkbox for bulk delete)
//
// 3. Features:
//    - Bulk delete for completed and reviewed tabs (with warning)
//    - Checkbox selection + Reset
//    - Filter row (search, sort)
//    - Pagination (5 per page)
//    - N+1 query prevention for review status
//    - Notification on cancel
//
// Database Tables: appointments, lawyers, reviews, notifications
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/dashboard-footer.php - Dashboard footer
// - customer/book-appointment.php - Edit link
// - customer/review.php - Review link
// - customer/chat.php - Chat link
// - customer/lawyer-profile.php - View profile
// ============================================================
$page_title = 'My Appointments';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as customer
// ============================================================
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Set sidebar variables
$user_type = 'customer';
$user_name = $_SESSION['customer_name'];
$dashboard_link = BASE_URL . 'customer/index.php';

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// ============================================================
// 2. Get active tab from URL
// ============================================================
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

// ============================================================
// 3. Handle Cancel (pending/confirmed) – deletes appointment and notifies lawyer
// ============================================================
if (isset($_GET['cancel'])) {
    $appointment_id = (int)$_GET['cancel'];

    // Get lawyer_id before deleting
    $lawyerStmt = $conn->prepare("SELECT lawyer_id FROM appointments WHERE id = ? AND customer_id = ?");
    $lawyerStmt->execute([$appointment_id, $customer_id]);
    $lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);
    if ($lawyer) {
        // Delete appointment
        $delStmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND customer_id = ?");
        $delStmt->execute([$appointment_id, $customer_id]);

        // Notify lawyer using new notification system
        addNotification(
            $lawyer['lawyer_id'],
            'lawyer',
            'cancelled',
            'Appointment Cancelled',
            "Customer $customer_name has cancelled their appointment.",
            'appointments.php',
            'fa-times-circle'
        );
    }
    header("Location: my-appointments.php?tab=" . $active_tab);
    exit();
}

// ============================================================
// 4. Handle Delete (only for cancelled appointments)
// ============================================================
if (isset($_GET['delete'])) {
    $appointment_id = (int)$_GET['delete'];
    $delStmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND customer_id = ? AND status = 'cancelled'");
    $delStmt->execute([$appointment_id, $customer_id]);
    header("Location: my-appointments.php?tab=" . $active_tab);
    exit();
}

// ============================================================
// 5. Handle Bulk Delete for Completed & Reviewed Appointments
// ============================================================
if (isset($_POST['bulk_delete_selected']) && isset($_POST['selected_ids'])) {
    $selected_ids = array_map('intval', $_POST['selected_ids']);
    
    if (!empty($selected_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $params = array_merge($selected_ids, [$customer_id]);
        
        $delStmt = $conn->prepare("
            DELETE FROM appointments 
            WHERE id IN ($placeholders) 
            AND customer_id = ? 
            AND status = 'completed'
        ");
        
        if ($delStmt->execute($params)) {
            $success = count($selected_ids) . " appointment(s) deleted successfully!";
        } else {
            $error = "Failed to delete selected appointments.";
        }
    }
    header("Location: my-appointments.php?tab=" . $active_tab);
    exit();
}

// ============================================================
// 6. Get counts for tabs
// ============================================================
// Upcoming count (pending + confirmed)
$upcomingStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ? AND status IN ('pending', 'confirmed')");
$upcomingStmt->execute([$customer_id]);
$upcoming_count = $upcomingStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Completed without review count
$completedStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM appointments a 
    WHERE a.customer_id = ? 
    AND a.status = 'completed'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.appointment_id = a.id AND r.customer_id = ?)
");
$completedStmt->execute([$customer_id, $customer_id]);
$completed_count = $completedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Reviewed count
$reviewedStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM appointments a 
    INNER JOIN reviews r ON a.id = r.appointment_id 
    WHERE a.customer_id = ? AND a.status = 'completed' AND r.customer_id = ?
");
$reviewedStmt->execute([$customer_id, $customer_id]);
$reviewed_count = $reviewedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// ============================================================
// 7. Build WHERE clause based on active tab
// ============================================================
$where = "a.customer_id = :customer_id";
$params = [':customer_id' => $customer_id];

if ($active_tab == 'upcoming') {
    $where .= " AND a.status IN ('pending', 'confirmed')";
    $orderBy = "ORDER BY a.appointment_date ASC, a.appointment_time ASC";
} elseif ($active_tab == 'completed') {
    $where .= " AND a.status = 'completed'";
    $where .= " AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.appointment_id = a.id AND r.customer_id = :review_customer_id)";
    $params[':review_customer_id'] = $customer_id;
    $orderBy = "ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} elseif ($active_tab == 'reviewed') {
    $where .= " AND a.status = 'completed'";
    $where .= " AND EXISTS (SELECT 1 FROM reviews r WHERE r.appointment_id = a.id AND r.customer_id = :review_customer_id)";
    $params[':review_customer_id'] = $customer_id;
    $orderBy = "ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

// ============================================================
// 8. Pagination setup
// ============================================================
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total for this tab
$countSql = "SELECT COUNT(*) as total FROM appointments a WHERE $where";
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch appointments with lawyer details
$sql = "
    SELECT a.*, 
           l.name as lawyer_name, 
           l.specialization, 
           l.city, 
           l.fees, 
           l.profile_pic 
    FROM appointments a 
    JOIN lawyers l ON a.lawyer_id = l.id 
    WHERE $where 
    $orderBy
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 9. Pre-fetch review status for all appointments
// ============================================================
$appointment_ids = array_column($appointments, 'id');
$reviewed_map = [];

if (!empty($appointment_ids)) {
    $placeholders = implode(',', array_fill(0, count($appointment_ids), '?'));
    $reviewStmt = $conn->prepare("
        SELECT appointment_id 
        FROM reviews 
        WHERE appointment_id IN ($placeholders) AND customer_id = ?
    ");
    $params_review = array_merge($appointment_ids, [$customer_id]);
    $reviewStmt->execute($params_review);
    $reviewed = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
    $reviewed_map = array_flip($reviewed);
}

// ============================================================
// 10. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<!-- Custom CSS for appointment cards (page-specific) -->
<style>
/* ========================================
   APPOINTMENT CARDS - Compact Version
======================================== */
.appointment-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    padding: 16px 18px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}
.appointment-card:hover {
    border-color: #c4b8a8;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.appointment-card-inner {
    display: grid;
    grid-template-columns: 80px 1fr 130px;
    gap: 14px;
    align-items: flex-start;
}
.appointment-img-wrapper {
    width: 80px;
    height: 100px;
    background: #ebe5db;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.appointment-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    
}
.appointment-img-wrapper i {
    font-size: 32px;
    color: var(--primary-color);
}
.appointment-lawyer-name {
    font-size: 18px;
    color: var(--primary-color);
    margin-bottom: 2px;
    line-height: 1.2;
}
.appointment-lawyer-spec {
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #8a8479;
}
.appointment-fee {
    text-align: right;
    min-width: 80px;
}
.fee-badge {
    background: var(--light-badge);
    color: var(--dark-badge);
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 2px;
}
.appointment-fee span {
    font-size: 8px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #8a8479;
    display: block;
    text-align: right;
}
.appointment-meta {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    border-top: 2px solid var(--secondary-color);
    padding-top: 6px;
}
.appointment-meta span {
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #7d766b;
}
.appointment-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.action-btn {
    display: block;
    text-align: center;
    padding: 6px 10px;
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-decoration: none;
    transition: 0.3s;
    border-radius: 4px;
    width: 100%;
}
.btn-cancel {
    border: 1px solid #ddd4c7;
    background: transparent;
    color: var(--text-dark);
}
.btn-cancel:hover {
    background: #f2ece3;
}
.btn-edit {
    background: var(--primary-color);
    color: white;
}
.btn-edit:hover {
    background: #1f291f;
}
.btn-delete {
    background: #7f8c8d;
    color: white;
}
.btn-profile {
    border: 1px solid #ddd4c7;
    background: transparent;
    color: var(--text-dark);
}
.btn-profile:hover {
    background: #f2ece3;
}
.btn-review {
    background: #c6a43f;
    color: white;
}
.btn-review:hover {
    background: #a8872e;
}
.reviewed-badge {
    color: #2e5b2e;
    font-size: 8px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-block;
    padding: 1px 8px;
    text-align: center;
    background: #dce9d7;
    border: none;
}
.empty-state {
    text-align: center;
    padding: 40px 20px;
}
.empty-state i {
    font-size: 48px;
    color: var(--text-light);
    margin-bottom: 15px;
}
.empty-state p {
    color: var(--text-light);
    margin-bottom: 20px;
}
.btn-find {
    background: var(--primary-color);
    color: white;
    padding: 10px 20px;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    display: inline-block;
    text-decoration: none;
}
.page-link-custom {
    border: 1px solid var(--border-color);
    background: transparent;
    color: var(--text-dark);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 6px 14px;
    margin: 0 4px;
    display: inline-block;
    text-decoration: none;
}
.page-link-custom:hover {
    background: var(--primary-color);
    color: white;
}

/* Chat button */
.btn-chat {
    background: var(--accent-color);
    border-radius: 100px;
    box-shadow: rgba(105, 206, 47, 0.2) 0 -25px 18px -14px inset,
                rgba(105, 206, 47, 0.15) 0 1px 2px,
                rgba(105, 206, 47, 0.15) 0 2px 4px,
                rgba(105, 206, 47, 0.15) 0 4px 8px,
                rgba(105, 206, 47, 0.15) 0 8px 16px,
                rgba(105, 206, 47, 0.15) 0 16px 32px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 250ms;
    border: 0;
    cursor: pointer;
    width: 100%;
    font-family: 'Inter', sans-serif;
}
.btn-chat:hover {
    box-shadow: rgba(105, 206, 47, 0.35) 0 -25px 18px -14px inset,
                rgba(105, 206, 47, 0.25) 0 1px 2px,
                rgba(105, 206, 47, 0.25) 0 2px 4px,
                rgba(105, 206, 47, 0.25) 0 4px 8px,
                rgba(105, 206, 47, 0.25) 0 8px 16px,
                rgba(105, 206, 47, 0.25) 0 16px 32px;
    transform: scale(1.02);
    color: white;
}
.btn-chat i {
    font-size: 12px;
}

/* ========================================
   CHECKBOX STYLING
======================================== */
.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 2px 0;
    border-bottom: 1px solid #f0ebe4;
    margin-bottom: 4px;
}
.checkbox-wrapper .review-checkbox {
    width: 14px;
    height: 14px;
    cursor: pointer;
    accent-color: var(--primary-color);
    margin: 0;
}
.checkbox-wrapper label {
    font-size: 9px;
    color: var(--text-light);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    cursor: pointer;
}

/* ========================================
   BULK ACTIONS BAR
======================================== */
.bulk-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: #f8f6f2;
    border: 1px solid var(--border-color);
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.bulk-actions .selected-count {
    font-size: 13px;
    color: var(--text-dark);
    font-weight: 500;
}
.bulk-actions .selected-count strong {
    color: var(--primary-color);
}
.btn-bulk-delete {
    background: #c62828;
    color: white;
    border: none;
    padding: 5px 16px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: 0.3s;
    border-radius: 4px;
}
.btn-bulk-delete:hover {
    background: #b71c1c;
}
.btn-bulk-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.btn-reset-selection {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-dark);
    padding: 5px 16px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: 0.3s;
    text-decoration: none;
    border-radius: 4px;
}
.btn-reset-selection:hover {
    background: #f0ebe4;
}

/* Status badge */
.status {
    padding: 1px 8px;
    font-size: 8px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-radius: 3px;
    font-weight: 600;
}
.status-pending {
    background: #f7ecd1;
    color: #8c650f;
}
.status-confirmed {
    background: #dcecdc;
    color: #2f6a33;
}
.status-completed {
    background: #dfe9f5;
    color: #2e5c86;
}
.status-cancelled {
    background: #f3dede;
    color: #9a3737;
}

/* ========================================
   RESPONSIVE - MOBILE (FIXED)
======================================== */
@media (max-width: 767px) {
    .appointment-card-inner {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .appointment-img-wrapper {
        width: 100%;
        height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #ebe5db;
    }
    .appointment-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: contain;  /* Full image visible */
        object-position: center;
    }
    .appointment-img-wrapper i {
        font-size: 48px;
    }
    .appointment-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }
    .appointment-actions .action-btn,
    .appointment-actions .reviewed-badge {
        flex: 1;
        min-width: 70px;
    }
    .appointment-fee {
        text-align: left;
    }
    .appointment-fee span {
        text-align: left;
    }
    .bulk-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }
    .bulk-actions .selected-count {
        text-align: center;
    }
    .checkbox-wrapper {
        border-bottom: none;
        margin-bottom: 0;
    }
}

@media (max-width: 576px) {
    .appointment-card {
        padding: 14px;
    }
    .appointment-lawyer-name {
        font-size: 16px;
    }
    .appointment-meta span {
        font-size: 8px;
        gap: 8px;
    }
    .action-btn {
        font-size: 8px;
        padding: 5px 8px;
        min-width: 60px;
    }
    .btn-chat {
        padding: 5px 10px;
        font-size: 8px;
    }
    .btn-chat i {
        font-size: 10px;
    }
    .appointment-img-wrapper {
        height: 140px;
    }
    .appointment-img-wrapper img {
        object-fit: contain;
    }
}

/* ========================================
   FIX: 412px and Below - Full Image
======================================== */
@media (max-width: 430px) {
    .appointment-card {
        padding: 12px;
    }
    .appointment-card-inner {
        gap: 10px;
    }
    .appointment-lawyer-name {
        font-size: 15px;
    }
    .appointment-actions .action-btn {
        font-size: 7px;
        padding: 4px 6px;
        min-width: 50px;
    }
    .appointment-img-wrapper {
        height: 120px;
    }
    .appointment-img-wrapper img {
        object-fit: contain;  /* Full image visible */
        object-position: center;
    }
}

/* ========================================
   FIX: 360px and Below
======================================== */
@media (max-width: 380px) {
    .appointment-card {
        padding: 10px;
    }
    .appointment-lawyer-name {
        font-size: 14px;
    }
    .appointment-img-wrapper {
        height: 100px;
    }
    .appointment-img-wrapper img {
        object-fit: contain;
        object-position: center;
    }
    .btn-chat {
        padding: 4px 8px;
        min-width: 28px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        aspect-ratio: 1/1;
        justify-content: center;
        flex: 0 0 28px;
    }
    .btn-chat .chat-text {
        display: none;
    }
    .btn-chat i {
        font-size: 12px;
        margin: 0;
    }
}

/* Bulk actions - mobile */
@media (max-width: 575px) {
    .bulk-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
        padding: 8px 12px;
    }
    .bulk-actions .selected-count {
        text-align: center;
        font-size: 12px;
    }
    .btn-bulk-delete,
    .btn-reset-selection {
        font-size: 9px;
        padding: 5px 12px;
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Header card -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">My Appointments</h2>
            <p class="dashboard-subtitle"><?php echo $total_rows; ?> total appointments found</p>
        </div>

        <!-- ============================================================
             FILTER TABS
             ============================================================ -->
        <div class="filter-tabs">
            <a href="?tab=upcoming" class="filter-tab <?php echo $active_tab == 'upcoming' ? 'active' : ''; ?>">
                Upcoming
                <?php if ($upcoming_count > 0): ?>
                    <span class="tab-badge"><?php echo $upcoming_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=completed" class="filter-tab <?php echo $active_tab == 'completed' ? 'active' : ''; ?>">
                Completed
                <?php if ($completed_count > 0): ?>
                    <span class="tab-badge"><?php echo $completed_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=reviewed" class="filter-tab <?php echo $active_tab == 'reviewed' ? 'active' : ''; ?>">
                Reviewed
                <?php if ($reviewed_count > 0): ?>
                    <span class="tab-badge"><?php echo $reviewed_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- ============================================================
             FILTER ROW (only for Completed & Reviewed tabs)
             ============================================================ -->
        <?php if (($active_tab == 'completed' || $active_tab == 'reviewed') && $total_rows > 0): ?>
        <div class="dashboard-card" style="padding: 20px;">
            <form method="GET" action="">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search Lawyer</label>
                        <input type="text" name="search" placeholder="Search by name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Sort By</label>
                        <select name="sort">
                            <option value="date_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_desc') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="date_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_asc') ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                    <div class="filter-group">
                        <a href="?tab=<?php echo $active_tab; ?>" class="reset-btn">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             BULK ACTIONS BAR (only for Completed & Reviewed tabs)
             ============================================================ -->
        <?php if (($active_tab == 'completed' || $active_tab == 'reviewed') && $total_rows > 0): ?>
        <div class="bulk-actions" id="bulkActions">
            <div class="selected-count">
                <span id="selectedCount">0</span> appointment(s) selected
            </div>
            <form method="POST" action="" id="bulkDeleteForm" style="display: inline;">
                <div id="selectedIdsContainer"></div>
                <button type="submit" name="bulk_delete_selected" class="btn-bulk-delete" id="bulkDeleteBtn" disabled
                        onclick="return confirm('⚠️ WARNING: You are about to delete selected appointment(s) permanently.\n\nThis action cannot be undone.\n\nAre you sure you want to proceed?')">
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
                <button type="button" class="btn-reset-selection" id="resetSelectionBtn">
                    <i class="fas fa-undo-alt"></i> Reset
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             APPOINTMENT CARDS - EXACT ORIGINAL STRUCTURE
             ============================================================ -->
        <?php if ($total_rows > 0): ?>
            <?php foreach ($appointments as $row): ?>
                <div class="dashboard-card appointment-card">
                    <div class="appointment-card-inner">
                        <!-- ============================================
                             COLUMN 1: Lawyer Image
                             ============================================ -->
                        <div class="appointment-img-wrapper">
                            <?php if (!empty($row['profile_pic']) && file_exists("../uploads/lawyers/" . $row['profile_pic'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user-advocate"></i>
                            <?php endif; ?>
                        </div>

                        <!-- ============================================
                             COLUMN 2: Appointment Details
                             ============================================ -->
                        <div class="appointment-details">
                            <div class="appointment-header-row">
                                <div>
                                    <h3 class="appointment-lawyer-name"><?php echo htmlspecialchars($row['lawyer_name']); ?></h3>
                                    <p class="appointment-lawyer-spec"><?php echo htmlspecialchars($row['specialization']); ?> | <?php echo htmlspecialchars($row['city']); ?></p>
                                </div>
                                <div class="appointment-fee">
                                    <div class="fee-badge"><?php echo number_format($row['fees']); ?> PKR</div>
                                    <span>Consultation Fee</span>
                                </div>
                            </div>

                            <div class="appointment-meta">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($row['appointment_date'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($row['appointment_time'])); ?></span>
                                <?php if ($active_tab == 'upcoming'): ?>
                                    <span class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                                <?php elseif ($active_tab == 'reviewed'): ?>
                                    <span class="reviewed-badge">✔ Reviewed</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ============================================
                             COLUMN 3: Actions + Checkbox (for Completed & Reviewed)
                             ============================================ -->
                        <div class="appointment-actions">
                            <!-- Checkbox - Only for Completed & Reviewed tabs -->
                            <?php if ($active_tab == 'completed' || $active_tab == 'reviewed'): ?>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="review-checkbox" value="<?php echo $row['id']; ?>" onchange="updateSelection()" id="select_<?php echo $row['id']; ?>">
                                    <label for="select_<?php echo $row['id']; ?>">Select</label>
                                </div>
                            <?php endif; ?>

                            <?php if ($active_tab == 'upcoming'): ?>
                                <!-- Upcoming: Cancel + Edit -->
                                <a href="?cancel=<?php echo $row['id']; ?>&tab=<?php echo $active_tab; ?>" class="action-btn btn-cancel" onclick="return confirm('⚠️ WARNING: This action will permanently delete your appointment and notify the lawyer. Cannot be undone. Cancel appointment?')">Cancel</a>
                                <a href="book-appointment.php?id=<?php echo $row['lawyer_id']; ?>&edit=<?php echo $row['id']; ?>" class="action-btn btn-edit">Edit</a>
                            
                            <?php elseif ($active_tab == 'completed'): ?>
                                <!-- Completed: Write Review -->
                                <a href="review.php?appointment_id=<?php echo $row['id']; ?>" class="action-btn btn-review">Write Review</a>
                            
                            <?php elseif ($active_tab == 'reviewed'): ?>
                                <!-- Reviewed: No actions (already reviewed) -->
                            <?php endif; ?>

                            <!-- View Profile (always visible for all tabs) -->
                            <a href="lawyer-profile.php?id=<?php echo $row['lawyer_id']; ?>" class="action-btn btn-profile">Profile →</a>

                            <!-- Chat button (only for Upcoming tab) -->
                            <?php if ($active_tab == 'upcoming'): ?>
                                <a href="chat.php?appointment_id=<?php echo $row['id']; ?>" class="action-btn btn-chat">
                                    <i class="fas fa-comment"></i> <span class="chat-text">Chat</span>
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrap">
                <?php if ($page > 1): ?>
                    <a href="?tab=<?php echo $active_tab; ?>&page=<?php echo $page - 1; ?>" class="page-link-custom">← Previous</a>
                <?php endif; ?>
                <span style="font-size: 12px; color: #8a8479; margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="?tab=<?php echo $active_tab; ?>&page=<?php echo $page + 1; ?>" class="page-link-custom">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty state -->
            <div class="dashboard-card empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>
                    <?php if ($active_tab == 'upcoming'): ?>
                        You have no upcoming appointments.
                    <?php elseif ($active_tab == 'completed'): ?>
                        You have no completed appointments to review.
                    <?php else: ?>
                        You have no reviewed appointments yet.
                    <?php endif; ?>
                </p>
                <?php if ($active_tab == 'upcoming'): ?>
                    <a href="search.php" class="btn-find">Find a Lawyer</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ============================================================
     JAVASCRIPT: Selection management for bulk delete
============================================================ -->
<script>
function updateSelection() {
    const checkboxes = document.querySelectorAll('.review-checkbox');
    const selected = document.querySelectorAll('.review-checkbox:checked');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedIdsContainer = document.getElementById('selectedIdsContainer');
    
    if (!selectedCount || !bulkDeleteBtn || !selectedIdsContainer) return;
    
    selectedCount.textContent = selected.length;
    bulkDeleteBtn.disabled = selected.length === 0;
    
    selectedIdsContainer.innerHTML = '';
    selected.forEach(function(cb) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_ids[]';
        input.value = cb.value;
        selectedIdsContainer.appendChild(input);
    });
}

const resetBtn = document.getElementById('resetSelectionBtn');
if (resetBtn) {
    resetBtn.addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.review-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = false;
        });
        updateSelection();
    });
}
</script>

<?php include '../includes/dashboard-footer.php'; ?>