<?php
// ============================================================
// ADMIN - MANAGE REVIEWS
// ============================================================
// This page allows administrators to moderate all customer
// reviews for quality control and spam prevention:
//
// 1. Review Display:
//    - Shows all reviews with checkbox selection
//    - Rating stars, comment, date, and status badge
//    - Pagination (10 per page)
//
// 2. Moderation Actions:
//    - Toggle review status (active ↔ inactive) via icon
//    - Bulk delete selected reviews via checkbox
//    - Select All checkbox for bulk operations
//    - Reset selection button
//
// 3. Filtering:
//    - Filter by status (all, active, inactive, flagged)
//    - Filter by rating (1-5 stars)
//    - Search by customer name or lawyer name
//
// 4. Security:
//    - Admin-only access
//    - Prepared statements for all queries
//    - Output escaping with htmlspecialchars()
//    - Session-based authentication
//
// Database Tables:
// - reviews (main review data)
// - customers (reviewer names via JOIN)
// - lawyers (lawyer names via JOIN)
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/dashboard-footer.php - Dashboard footer
// - assets/css/dashboard.css - Dashboard styling
// - assets/css/tables.css - Table styling
// - assets/css/sidebar.css - Sidebar styling
// ============================================================

$page_title = 'Manage Reviews';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. AUTHENTICATION
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// ============================================================
// 2. HANDLE STATUS TOGGLE
// ============================================================
if (isset($_GET['toggle'])) {
    $review_id = (int)$_GET['toggle'];
    
    $checkStmt = $conn->prepare("SELECT status FROM reviews WHERE id = ?");
    $checkStmt->execute([$review_id]);
    $current = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        if ($current['status'] == 'active') {
            $new_status = 'inactive';
        } elseif ($current['status'] == 'inactive') {
            $new_status = 'active';
        } elseif ($current['status'] == 'flagged') {
            $new_status = 'active';
        }
        
        $updateStmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        if ($updateStmt->execute([$new_status, $review_id])) {
            $success = "Review status updated successfully!";
        } else {
            $error = "Failed to update review status.";
        }
    }
}

// ============================================================
// 3. HANDLE BULK DELETE
// ============================================================
if (isset($_POST['bulk_delete']) && isset($_POST['review_ids'])) {
    $review_ids = array_map('intval', $_POST['review_ids']);
    $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
    
    $delStmt = $conn->prepare("DELETE FROM reviews WHERE id IN ($placeholders)");
    if ($delStmt->execute($review_ids)) {
        $success = count($review_ids) . " review(s) deleted successfully!";
    } else {
        $error = "Failed to delete reviews.";
    }
}

// ============================================================
// 4. GET FILTERS
// ============================================================
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ============================================================
// 5. BUILD WHERE CLAUSE - USING NAMED PARAMETERS
// ============================================================
$conditions = [];
$params = [];

if ($status_filter != 'all') {
    $conditions[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($rating_filter > 0 && $rating_filter <= 5) {
    $conditions[] = "r.rating = :rating";
    $params[':rating'] = $rating_filter;
}

if (!empty($search)) {
    $conditions[] = "(c.name LIKE :search1 OR l.name LIKE :search2)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// ============================================================
// 6. PAGINATION
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total
$countSql = "SELECT COUNT(*) as total FROM reviews r $where";
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch reviews with JOINs - USING ONLY NAMED PARAMETERS
$sql = "
    SELECT r.*, 
           c.name as customer_name, 
           c.email as customer_email,
           l.name as lawyer_name,
           l.specialization as lawyer_specialization
    FROM reviews r
    JOIN customers c ON r.customer_id = c.id
    JOIN lawyers l ON r.lawyer_id = l.id
    $where
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);

// Bind limit and offset (named parameters)
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Bind filter parameters (named parameters)
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 7. GET STATISTICS
// ============================================================
$totalStmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews");
$totalStmt->execute();
$total_count = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];

$activeStmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE status = 'active'");
$activeStmt->execute();
$active_count = $activeStmt->fetch(PDO::FETCH_ASSOC)['count'];

$inactiveStmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE status = 'inactive'");
$inactiveStmt->execute();
$inactive_count = $inactiveStmt->fetch(PDO::FETCH_ASSOC)['count'];

$flaggedStmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE status = 'flagged'");
$flaggedStmt->execute();
$flagged_count = $flaggedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// ============================================================
// 8. INCLUDE HEADER
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">
<!-- page specific style  -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/manage-reviews.css">



<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Manage Reviews</h2>
            <p class="dashboard-subtitle">Moderate customer reviews for quality control</p>
        </div>

        <!-- STATISTICS -->
        <div class="review-stats-grid">
            <div class="review-stat-box">
                <h4 class="stat-total"><?php echo $total_count; ?></h4>
                <p>Total Reviews</p>
            </div>
            <div class="review-stat-box">
                <h4 class="stat-active"><?php echo $active_count; ?></h4>
                <p>Active</p>
            </div>
            <div class="review-stat-box">
                <h4 class="stat-inactive"><?php echo $inactive_count; ?></h4>
                <p>Inactive</p>
            </div>
            <div class="review-stat-box">
                <h4 class="stat-flagged"><?php echo $flagged_count; ?></h4>
                <p>Flagged</p>
            </div>
        </div>

        <!-- SUCCESS/ERROR MESSAGES -->
        <?php if (isset($success)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- FILTER TABS - Using existing .filter-tabs from tables.css -->
        <div class="filter-tabs">
            <a href="?status=all<?php echo $rating_filter ? '&rating=' . $rating_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=active<?php echo $rating_filter ? '&rating=' . $rating_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'active' ? 'active' : ''; ?>">
                Active <?php if ($active_count > 0): ?><span class="tab-badge"><?php echo $active_count; ?></span><?php endif; ?>
            </a>
            <a href="?status=inactive<?php echo $rating_filter ? '&rating=' . $rating_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'inactive' ? 'active' : ''; ?>">
                Inactive <?php if ($inactive_count > 0): ?><span class="tab-badge"><?php echo $inactive_count; ?></span><?php endif; ?>
            </a>
            <a href="?status=flagged<?php echo $rating_filter ? '&rating=' . $rating_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'flagged' ? 'active' : ''; ?>">
                Flagged <?php if ($flagged_count > 0): ?><span class="tab-badge"><?php echo $flagged_count; ?></span><?php endif; ?>
            </a>
        </div>

        <!-- FILTER ROW - Using existing .filter-row from tables.css -->
        <div class="dashboard-card" style="padding: 20px;">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Rating</label>
                        <select name="rating">
                            <option value="0" <?php echo $rating_filter == 0 ? 'selected' : ''; ?>>All Ratings</option>
                            <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>★★★★★ (5)</option>
                            <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>★★★★☆ (4)</option>
                            <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>★★★☆☆ (3)</option>
                            <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>★★☆☆☆ (2)</option>
                            <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>★☆☆☆☆ (1)</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Customer or Lawyer name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                    <div class="filter-group">
                        <a href="manage-reviews.php" class="reset-btn">Reset</a>
                    </div>
                </div>
                <!-- Preserve status filter when submitting rating/search -->
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
            </form>
        </div>

        <!-- BULK ACTIONS BAR -->
        <div class="bulk-actions" id="bulkActions">
            <div class="selected-count">
                <span id="selectedCount">0</span> review(s) selected
            </div>
            <form method="POST" action="" id="bulkDeleteForm" style="display: inline;">
                <div id="selectedIdsContainer"></div>
                <button type="submit" name="bulk_delete" class="btn-bulk-delete" id="bulkDeleteBtn" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
                <button type="button" class="btn-reset-selection" id="resetSelectionBtn">
                    <i class="fas fa-undo-alt"></i> Reset
                </button>
            </form>
        </div>

        <!-- REVIEWS LIST -->
        <div class="dashboard-card">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $row): ?>
                    <div class="review-item">
                        <div class="review-item-header">
                            <div style="display: flex; align-items: flex-start; gap: 6px;">
                                <input type="checkbox" class="review-checkbox" value="<?php echo $row['id']; ?>" onchange="updateSelection()">
                                <div>
                                    <div>
                                        <span class="review-item-customer">
                                            <i class="fas fa-user" style="color: var(--primary-color); margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($row['customer_name']); ?>
                                        </span>
                                        <span class="review-item-lawyer">
                                            <i class="fas fa-gavel"></i>
                                            Adv. <?php echo htmlspecialchars($row['lawyer_name']); ?>
                                            <span style="color: var(--text-light);">(<?php echo htmlspecialchars($row['lawyer_specialization']); ?>)</span>
                                        </span>
                                    </div>
                                    <div class="review-item-rating" style="margin-top: 2px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $row['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="fas fa-star-o empty-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-item-actions">
                                <?php if ($row['status'] == 'active'): ?>
                                    <!-- Active = Visible on profile → Show eye icon → Click to hide -->
                                    <a href="?toggle=<?php echo $row['id']; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" 
                                       class="action-icon-review active-status" 
                                       data-tooltip="Hide Review"
                                       onclick="return confirm('Hide this review? It will no longer be visible on the lawyer profile.')">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php elseif ($row['status'] == 'inactive' || $row['status'] == 'flagged'): ?>
                                    <!-- Inactive = Hidden from profile → Show eye-slash icon → Click to show -->
                                    <a href="?toggle=<?php echo $row['id']; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" 
                                       class="action-icon-review inactive-status" 
                                       data-tooltip="Show Review"
                                       onclick="return confirm('Show this review on the lawyer profile?')">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="review-item-comment">
                            <?php echo htmlspecialchars($row['comment']); ?>
                        </div>

                        <div class="review-item-footer">
                            <div class="review-item-meta">
                                <span>
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($row['customer_email']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                                </span>
                                <span>
                                    <span class="status-badge-review <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrap">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="page-link">← Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="review-empty">
                    <i class="fas fa-comment-slash"></i>
                    <p>No reviews found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<!-- JavaScript for selection management -->
<script>
function updateSelection() {
    const checkboxes = document.querySelectorAll('.review-checkbox');
    const selected = document.querySelectorAll('.review-checkbox:checked');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedIdsContainer = document.getElementById('selectedIdsContainer');
    
    // Update count
    selectedCount.textContent = selected.length;
    
    // Enable/disable delete button
    bulkDeleteBtn.disabled = selected.length === 0;
    
    // Update hidden inputs for selected IDs
    selectedIdsContainer.innerHTML = '';
    selected.forEach(function(cb) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'review_ids[]';
        input.value = cb.value;
        selectedIdsContainer.appendChild(input);
    });
}

// Reset selection
document.getElementById('resetSelectionBtn').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.review-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    updateSelection();
});
</script>

<?php include '../includes/dashboard-footer.php'; ?>