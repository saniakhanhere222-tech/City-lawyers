<?php
// ============================================================
// ADMIN - MANAGE LAWYERS
// ============================================================
// This page manages all lawyer registrations with complete workflow:
// 
// 1. Status Management:
//    - Approve pending lawyers (status: pending → approved)
//    - Reject pending lawyers (status: pending → rejected)
//    - Re-approve rejected lawyers (status: rejected → approved)
//    - Reject approved lawyers (status: approved → rejected)
// 
// 2. Account Deletion:
//    - Permanently delete lawyer accounts
//    - Automatically removes associated profile pictures
//    - Cascading deletion of related data
// 
// 3. Notification System:
//    - Sends approval notification to lawyers
//    - Sends rejection notification with instructions
//    - Links to lawyer profile for updates
// 
// 4. Filtering & Display:
//    - Filter lawyers by status (all/pending/approved/rejected)
//    - Display lawyer details: image, name, email, specialization, city, experience, fees
//    - Status badges with color coding
//    - Conditional action buttons based on current status
// 
// 5. Pagination:
//    - Shows 10 lawyers per page
//    - Preserves status filter across pages
// 
// Features:
// - Session-based authentication (admin only)
// - File system cleanup on deletion (unlink)
// - Notification integration (addNotification function)
// - Responsive table with action icons
// - JavaScript confirm dialogs for safety
// 
// Database Tables Used:
// - lawyers (all columns)
// - notifications (via addNotification function)
// 
// Security Notes:
// - All IDs cast to integers before use
// - Prepared statements for all queries
// - File path verification before deletion
// - Confirmation required for all destructive actions
// 
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/dashboard-footer.php - Footer
// - ../includes/functions.php - Contains addNotification()
// - assets/css/dashboard.css - Dashboard styling
// - assets/css/tables.css - Table styling
// - assets/css/sidebar.css - Sidebar styling
// - uploads/lawyers/ - Profile picture storage
// ============================================================
$page_title = 'Manage Lawyers';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Set sidebar variables
$user_type = 'admin';
$user_name = $_SESSION['admin_name'];
$dashboard_link = BASE_URL . 'admin/index.php';

// ============================================================
// 1. Handle Approve / Reject / Delete actions
// ============================================================

// Approve Lawyer
if (isset($_GET['approve'])) {
    $lawyer_id = (int)$_GET['approve'];

    $stmt = $conn->prepare("UPDATE lawyers SET status = 'approved' WHERE id = ?");
    $stmt->execute([$lawyer_id]);

    $nameStmt = $conn->prepare("SELECT name FROM lawyers WHERE id = ?");
    $nameStmt->execute([$lawyer_id]);
    $lawyer = $nameStmt->fetch(PDO::FETCH_ASSOC);
    if ($lawyer) {
        addNotification(
            $lawyer_id,
            'lawyer',
            'approved',
            'Profile Approved',
            "Dear " . $lawyer['name'] . ", your lawyer profile has been approved. You can now login and start accepting appointments.",
            'profile.php',
            'fa-check-circle'
        );
    }

    header("Location: manage-lawyers.php");
    exit();
}

// Reject Lawyer
if (isset($_GET['reject'])) {
    $lawyer_id = (int)$_GET['reject'];

    $stmt = $conn->prepare("UPDATE lawyers SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$lawyer_id]);

    $nameStmt = $conn->prepare("SELECT name FROM lawyers WHERE id = ?");
    $nameStmt->execute([$lawyer_id]);
    $lawyer = $nameStmt->fetch(PDO::FETCH_ASSOC);
    if ($lawyer) {
        addNotification(
            $lawyer_id,
            'lawyer',
            'rejected',
            'Profile Update Required',
            "Dear " . $lawyer['name'] . ", your lawyer profile requires changes. Please contact admin for more information.",
            'profile.php',
            'fa-times-circle'
        );
    }

    header("Location: manage-lawyers.php");
    exit();
}

// Delete Lawyer
if (isset($_GET['delete'])) {
    $lawyer_id = (int)$_GET['delete'];

    // Get profile picture to delete file
    $picStmt = $conn->prepare("SELECT profile_pic FROM lawyers WHERE id = ?");
    $picStmt->execute([$lawyer_id]);
    $lawyer = $picStmt->fetch(PDO::FETCH_ASSOC);
    if ($lawyer && $lawyer['profile_pic'] && file_exists("../uploads/lawyers/" . $lawyer['profile_pic'])) {
        unlink("../uploads/lawyers/" . $lawyer['profile_pic']);
    }

    $delStmt = $conn->prepare("DELETE FROM lawyers WHERE id = ?");
    $delStmt->execute([$lawyer_id]);

    header("Location: manage-lawyers.php");
    exit();
}

// ============================================================
// 2. Filter by status
// ============================================================
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where = "1=1";
$params = [];

if ($status_filter == 'pending') {
    $where = "status = 'pending'";
} elseif ($status_filter == 'approved') {
    $where = "status = 'approved'";
} elseif ($status_filter == 'rejected') {
    $where = "status = 'rejected'";
}

// ============================================================
// 3. Pagination
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) as total FROM lawyers WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM lawyers WHERE $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$lawyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 4. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">



<div class="dashboard-wrapper">

    <?php include '../includes/dashboard-sidebar.php'; ?>

    <div class="main-content">

        <div class="dashboard-card">
            <h2 class="dashboard-title">Manage Lawyers</h2>
            <p class="dashboard-subtitle">Approve, reject or manage lawyer registrations</p>
        </div>

        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=approved" class="filter-tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status=rejected" class="filter-tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>

        <div class="dashboard-card">
            <?php if (count($lawyers) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>City</th>
                                <th>Experience</th>
                                <th>Fees</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lawyers as $row): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['profile_pic']) && file_exists("../uploads/lawyers/" . $row['profile_pic'])): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($row['profile_pic']); ?>" class="lawyer-img-small" alt="Profile">
                                    <?php else: ?>
                                        <i class="fas fa-user-advocate fa-2x" style="color: var(--primary-color);"></i>
                                    <?php endif; ?>
                                </td>
                                <td>Adv. <?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                <td><?php echo htmlspecialchars($row['city']); ?></td>
                                <td><?php echo $row['experience']; ?> yrs</td>
                                <td><?php echo number_format($row['fees']); ?> PKR</td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-icons">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <!-- Approve + Reject for pending -->
                                            <a href="?approve=<?php echo $row['id']; ?>" 
                                               class="action-icon approve" 
                                               data-tooltip="Approve"
                                               onclick="return confirm('Approve this lawyer?')">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <a href="?reject=<?php echo $row['id']; ?>" 
                                               class="action-icon reject" 
                                               data-tooltip="Reject"
                                               onclick="return confirm('Reject this lawyer?')">
                                                <i class="fas fa-times-circle"></i>
                                            </a>

                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <!-- Only Reject for approved (plus Delete) -->
                                            <a href="?reject=<?php echo $row['id']; ?>" 
                                               class="action-icon reject" 
                                               data-tooltip="Reject"
                                               onclick="return confirm('Reject this lawyer?')">
                                                <i class="fas fa-times-circle"></i>
                                            </a>

                                        <?php elseif ($row['status'] == 'rejected'): ?>
                                            <!-- Only Approve for rejected (plus Delete) -->
                                            <a href="?approve=<?php echo $row['id']; ?>" 
                                               class="action-icon approve" 
                                               data-tooltip="Approve"
                                               onclick="return confirm('Approve this lawyer?')">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Delete – always visible -->
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="action-icon delete" 
                                           data-tooltip="Delete"
                                           onclick="return confirm('Delete this lawyer permanently?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination-wrap">
                    <?php if ($page > 1): ?>
                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page-1; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page+1; ?>" class="page-link">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p class="no-data">No lawyers found.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>