<?php
// ============================================================
// Admin - Manage Lawyers (PDO version)
// Approve, reject, delete lawyer accounts
// ============================================================
$page_title = 'Manage Lawyers';
$dashboard_page = true; // to dynamically change container class (used in header.php)
require_once '../includes/config.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

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
        $title   = "Profile Approved";
        $message = "Dear " . $lawyer['name'] . ", your lawyer profile has been approved. You can now login and start accepting appointments.";
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at) VALUES (?, 'lawyer', ?, ?, 0, NOW())");
        $notifStmt->execute([$lawyer_id, $title, $message]);
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
        $title   = "Profile Update Required";
        $message = "Dear " . $lawyer['name'] . ", your lawyer profile requires changes. Please contact admin for more information.";
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at) VALUES (?, 'lawyer', ?, ?, 0, NOW())");
        $notifStmt->execute([$lawyer_id, $title, $message]);
    }

    header("Location: manage-lawyers.php");
    exit();
}

// Delete Lawyer
if (isset($_GET['delete'])) {
    $lawyer_id = (int)$_GET['delete'];

    // Get profile picture to delete file (FIXED path)
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

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<!---TABLES.CSS – reusable dashboard table styles
   (filter tabs, tables, status badges, action buttons, pagination) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">

<div class="dashboard-wrapper">

    <div class="sidebar">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <a href="<?php echo BASE_URL; ?>admin/index.php">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-lawyers.php" class="active">Manage Lawyers</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-appointments.php">Appointments</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-content.php">Homepage</a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Logout</a>
    </div>

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
                                <td class="action-btn-group">
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <a href="?approve=<?php echo $row['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Approve this lawyer?')">Approve</a>
                                        <a href="?reject=<?php echo $row['id']; ?>" class="action-btn btn-reject" onclick="return confirm('Reject this lawyer?')">Reject</a>
                                    <?php elseif ($row['status'] == 'approved'): ?>
                                        <a href="?reject=<?php echo $row['id']; ?>" class="action-btn btn-reject" onclick="return confirm('Reject this lawyer?')">Reject</a>
                                    <?php elseif ($row['status'] == 'rejected'): ?>
                                        <a href="?approve=<?php echo $row['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Approve this lawyer?')">Approve</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this lawyer permanently?')">Delete</a>
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

<?php include '../includes/footer.php'; ?>