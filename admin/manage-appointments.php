<?php
/**
 * Admin - Manage Appointments
 * 
 * This page allows the admin to:
 * - View all appointments (with lawyer and customer details)
 * - Filter appointments by status (pending, confirmed, completed, cancelled)
 * - Delete any appointment
 * - Paginate through results (10 per page)
 */
$dashboard_page = true;
$page_title = 'Manage Appointments';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as admin
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// ============================================================
// 2. Handle Delete action (with PDO)
// ============================================================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delStmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $delStmt->execute([$id]);
    header("Location: manage-appointments.php");
    exit();
}

// ============================================================
// 3. Filter by status
// ============================================================
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where = "";
if ($status_filter == 'pending') {
    $where = "WHERE a.status = 'pending'";
} elseif ($status_filter == 'confirmed') {
    $where = "WHERE a.status = 'confirmed'";
} elseif ($status_filter == 'completed') {
    $where = "WHERE a.status = 'completed'";
} elseif ($status_filter == 'cancelled') {
    $where = "WHERE a.status = 'cancelled'";
}

// ============================================================
// 4. Pagination setup
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total rows (for pagination)
$countSql = "SELECT COUNT(*) as total FROM appointments a $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch appointments with JOINs (lawyer + customer)
$sql = "SELECT a.*, 
               l.name as lawyer_name, 
               l.specialization, 
               l.fees, 
               c.name as customer_name
        FROM appointments a
        JOIN lawyers l ON a.lawyer_id = l.id
        JOIN customers c ON a.customer_id = c.id
        $where
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 5. Include header (global, forms, dashboard CSS)
// ============================================================
include '../includes/header.php';
?>

<!-- No inline CSS – all styles come from dashboard.css -->
 <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
 <!---TABLES.CSS – reusable dashboard table styles
   (filter tabs, tables, status badges, action buttons, pagination) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <a href="<?php echo BASE_URL; ?>admin/index.php">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-lawyers.php">Manage Lawyers</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-appointments.php" class="active">Appointments</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-content.php">Homepage</a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Manage Appointments</h2>
            <p class="dashboard-subtitle">View all appointments across the system</p>
        </div>

        <!-- FILTER TABS -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=confirmed" class="filter-tab <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
            <a href="?status=completed" class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="?status=cancelled" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <!-- TABLE CARD -->
        <div class="dashboard-card">
            <?php if (count($appointments) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Lawyer</th>
                                <th>Specialization</th>
                                <th>Customer</th>
                                <th>Fees</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                <td>Adv. <?php echo htmlspecialchars($row['lawyer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo number_format($row['fees']); ?> PKR</td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-btn-group">
                                    <a href="?delete=<?php echo $row['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this appointment permanently?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION (manual, as the function hasn't been built yet) -->
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
                <p class="no-data">No appointments found.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>