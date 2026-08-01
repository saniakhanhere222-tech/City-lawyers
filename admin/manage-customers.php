<?php
/**
 * Admin - Manage Customers
 * 
 * Allows admin to:
 * - Search customers by name or email
 * - Filter by city
 * - Delete customer accounts
 * - Paginate through results
 */
$page_title = 'Manage Customers';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as admin
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// ============================================================
// 2. Handle Delete action
// ============================================================
if (isset($_GET['delete'])) {
    $customer_id = (int)$_GET['delete'];
    $delStmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $delStmt->execute([$customer_id]);
    header("Location: manage-customers.php");
    exit();
}

// ============================================================
// 3. Read filter/search values
// ============================================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city   = isset($_GET['city']) ? $_GET['city'] : '';

// ============================================================
// 4. Build WHERE clause
// ============================================================
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($city)) {
    $conditions[] = "city = :city";
    $params[':city'] = $city;
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// ============================================================
// 5. Get distinct cities for filter dropdown
// ============================================================
$cityStmt = $conn->prepare("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city");
$cityStmt->execute();
$cities = $cityStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 6. Pagination
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total rows
$countSql = "SELECT COUNT(*) as total FROM customers $where";
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch customers
$sql = "SELECT * FROM customers $where ORDER BY reg_date DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 7. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- ============================================================
     CSS FILES
     dashboard.css – layout + cards
     tables.css – table styles, badges, pagination
     sidebar.css – collapsible sidebar
     ============================================================ -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">



<div class="dashboard-wrapper">

    <!-- SIDEBAR – Reusable component -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- ============================================================
             HEADER CARD
             ============================================================ -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Manage Customers</h2>
            <p class="dashboard-subtitle">View, search, and delete customer accounts</p>
        </div>

        <!-- ============================================================
             FILTER / SEARCH BOX
             Uses .filter-row, .filter-group, .filter-btn, .reset-btn
             from tables.css
             ============================================================ -->
        <div class="dashboard-card">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search (name or email)</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Type to search...">
                    </div>
                    <div class="filter-group">
                        <label>City</label>
                        <select name="city">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['city']); ?>" <?php echo $city == $c['city'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                    <div class="filter-group">
                        <a href="manage-customers.php" class="reset-btn">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- ============================================================
             CUSTOMERS TABLE
             ============================================================ -->
        <div class="dashboard-card">
            <?php if (count($customers) > 0): ?>
                <div class="table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>City</th>
                                <th>Registered Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['city'] ?? '-'); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['reg_date'])); ?></td>
                                <td>
                                    <!-- ========================================
                                         ICON ACTION BUTTONS (Delete only)
                                         ======================================== -->
                                    <div class="action-icons">
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="action-icon delete" 
                                           data-tooltip="Delete"
                                           onclick="return confirm('Delete this customer permanently? This will also delete all their appointments.')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ============================================================
                     PAGINATION
                     ============================================================ -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-wrap">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>" class="page-link">← Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>" class="page-link">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p class="no-data">No customers found.</p>
            <?php endif; ?>
        </div>

    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<?php include '../includes/dashboard-footer.php'; ?>