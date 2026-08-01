<?php
/**
 * Admin - Manage Homepage Content (Featured Lawyers + Categories)
 * 
 * Allows admin to:
 * - Select featured lawyers for homepage
 * - Manage categories (add, edit, delete, reorder)
 */
$page_title = 'Manage Homepage Content';
$page_layout= 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as admin
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Set sidebar variables
$user_type = 'admin';
$user_name = $_SESSION['admin_name'];
$dashboard_link = BASE_URL . 'admin/index.php';

// ============================================================
// 2. Handle Categories CRUD
// ============================================================

// --- Add Category ---
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $icon_class = trim($_POST['icon_class']);
    $status = $_POST['status'] ?? 'active';
    $order_by = (int)$_POST['order_by'];

    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, icon_class, status, order_by) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $icon_class, $status, $order_by])) {
            $success = "Category '{$name}' added successfully!";
        } else {
            $error = "Failed to add category. Please try again.";
        }
    } else {
        $error = "Category name is required.";
    }
}

// --- Edit Category ---
if (isset($_POST['edit_category'])) {
    $id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $icon_class = trim($_POST['icon_class']);
    $status = $_POST['status'] ?? 'active';
    $order_by = (int)$_POST['order_by'];

    if (!empty($name) && $id > 0) {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, icon_class = ?, status = ?, order_by = ? WHERE id = ?");
        if ($stmt->execute([$name, $icon_class, $status, $order_by, $id])) {
            $success = "Category updated successfully!";
        } else {
            $error = "Failed to update category.";
        }
    } else {
        $error = "Category name is required.";
    }
}

// --- Delete Category ---
if (isset($_GET['delete_category']) && is_numeric($_GET['delete_category'])) {
    $id = (int)$_GET['delete_category'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Category deleted successfully!";
    } else {
        $error = "Failed to delete category.";
    }
}

// --- Toggle Status ---
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $stmt = $conn->prepare("SELECT status FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($current) {
        $new_status = ($current['status'] == 'active') ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $id])) {
            $success = "Category status updated!";
        } else {
            $error = "Failed to update category status.";
        }
    }
}

// ============================================================
// 3. Fetch Categories
// ============================================================
$categories = [];
$catStmt = $conn->prepare("SELECT * FROM categories ORDER BY order_by ASC, name ASC");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Get max order for new category
$maxOrderStmt = $conn->prepare("SELECT MAX(order_by) as max_order FROM categories");
$maxOrderStmt->execute();
$maxOrder = $maxOrderStmt->fetch(PDO::FETCH_ASSOC)['max_order'] ?? 0;

// ============================================================
// 4. Handle saving featured lawyers
// ============================================================
if (isset($_POST['save_featured'])) {
    $resetStmt = $conn->prepare("UPDATE lawyers SET is_featured = 0");
    $resetStmt->execute();

    if (isset($_POST['featured_lawyers']) && is_array($_POST['featured_lawyers'])) {
        $updateStmt = $conn->prepare("UPDATE lawyers SET is_featured = 1 WHERE id = ?");
        foreach ($_POST['featured_lawyers'] as $lawyer_id) {
            $updateStmt->execute([(int)$lawyer_id]);
        }
    }
    $success = "Featured lawyers updated successfully!";
}

// ============================================================
// 5. Read filter values (sanitized)
// ============================================================
$experience = isset($_GET['experience']) ? $_GET['experience'] : '';
$rating     = isset($_GET['rating']) ? $_GET['rating'] : '';
$min_appointments = isset($_GET['min_appointments']) ? (int)$_GET['min_appointments'] : 0;

// ============================================================
// 6. Build WHERE clause
// ============================================================
$conditions = ["status = 'approved'"];

if ($experience == '0-5') {
    $conditions[] = "experience BETWEEN 0 AND 5";
} elseif ($experience == '5-10') {
    $conditions[] = "experience BETWEEN 5 AND 10";
} elseif ($experience == '10+') {
    $conditions[] = "experience >= 10";
}

if ($rating == '4') {
    $conditions[] = "avg_rating >= 4";
} elseif ($rating == '3') {
    $conditions[] = "avg_rating >= 3 AND avg_rating < 4";
}

if ($min_appointments > 0) {
    $conditions[] = "(SELECT COUNT(*) FROM appointments WHERE lawyer_id = lawyers.id) >= $min_appointments";
}

$where = implode(" AND ", $conditions);

// ============================================================
// 7. Pagination
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) as total FROM lawyers WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT *, 
               (SELECT COUNT(*) FROM appointments WHERE lawyer_id = lawyers.id) as appointment_count
        FROM lawyers 
        WHERE $where 
        ORDER BY avg_rating DESC, appointment_count DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$lawyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 8. Get active tab from URL
// ============================================================
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'featured';

// ============================================================
// 9. Include header
// ============================================================
include '../includes/header.php';
?>

<style>
/* ========================================
   MANAGE CONTENT – Page-Specific Styles
======================================== */

/* Tab Navigation */
.content-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 25px;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
}
.content-tab {
    padding: 12px 25px;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-light);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: 0.3s;
    font-weight: 500;
}
.content-tab:hover {
    color: var(--primary-color);
}
.content-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}
.content-tab i {
    margin-right: 8px;
}
.tab-badge {
    background: var(--primary-color);
    color: white;
    padding: 1px 10px;
    font-size: 11px;
    border-radius: 0;
    margin-left: 8px;
}

/* Tab content */
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}

/* Checkbox */
.featured-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary-color);
}

/* Lawyer image thumbnail */
.lawyer-img-small {
    width: 40px;
    height: 40px;
    object-fit: cover;
    background: #ebe5db;
}

/* Save button */
.save-btn-wrapper {
    text-align: center;
}
.save-btn {
    background: #2c7a2c;
    color: white;
    border: none;
    padding: 12px 25px;
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    margin-top: 20px;
    display: inline-block;
}
.save-btn:hover {
    background: #1f5a1f;
}

/* Alert messages */
.alert-success {
    background: #dce9d7;
    color: #2e5b2e;
    padding: 12px 20px;
    margin-bottom: 20px;
    border: 1px solid #c5d4c5;
}
.alert-error {
    background: #f0e0e0;
    color: #8b3a3a;
    padding: 12px 20px;
    margin-bottom: 20px;
    border: 1px solid #d4c5c5;
}

/* No data */
.no-data {
    color: var(--text-light);
    text-align: center;
    padding: 40px 20px;
    margin: 0;
}

/* ========================================
   CATEGORIES - Simple & Centered
======================================== */
.categories-wrapper {
    max-width: 1000px;
    margin: 0 auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    margin-top: 20px;
}

/* Category Form */
.category-form-card {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    padding: 25px;
}

.category-form-card h4 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    color: var(--primary-color);
    margin: 0 0 20px;
    text-align: center;
}

.category-form-group {
    margin-bottom: 15px;
}

.category-form-group label {
    display: block;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text-light);
    margin-bottom: 5px;
}

.category-form-group input,
.category-form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    font-size: 14px;
    background: var(--white);
    border-radius: 0;
}

.category-form-group input:focus,
.category-form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.category-form-group small {
    color: var(--text-light);
    font-size: 11px;
    display: block;
    margin-top: 4px;
}
.category-form-group small a {
    color: var(--primary-color);
}

.btn-add-category {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 10px 25px;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    width: 100%;
}
.btn-add-category:hover {
    background: #1f291f;
}

/* Categories Table */
.category-table-wrap {
    overflow-x: auto;
}

.category-table {
    width: 100%;
    border-collapse: collapse;
}

.category-table th {
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-light);
    padding: 12px 10px;
    border-bottom: 1px solid var(--border-color);
    text-align: left;
    font-weight: 600;
}

.category-table td {
    padding: 14px 10px;
    border-bottom: 1px solid #ece4d8;
    font-size: 13px;
    color: var(--text-dark);
    vertical-align: middle;
}

.category-table .cat-icon {
    font-size: 20px;
    color: var(--primary-color);
    width: 30px;
    text-align: center;
}

.category-table .action-icons-cat {
    display: flex;
    gap: 8px;
    align-items: center;
}

.category-table .action-icon-cat {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: transparent;
    color: var(--text-muted);
    text-decoration: none;
    transition: all .25s ease;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.category-table .action-icon-cat:hover {
    background: rgba(0,0,0,0.05);
    transform: scale(1.1);
}

.category-table .action-icon-cat.edit {
    color: #1e4a6b;
}
.category-table .action-icon-cat.edit:hover {
    background: rgba(30, 74, 107, 0.12);
}

.category-table .action-icon-cat.delete {
    color: #c62828;
}
.category-table .action-icon-cat.delete:hover {
    background: rgba(198, 40, 40, 0.12);
}

.category-table .action-icon-cat.toggle {
    color: #2e7d32;
}
.category-table .action-icon-cat.toggle.inactive {
    color: #6E766F;
}

/* Status badge */
.cat-status-badge {
    padding: 4px 10px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-block;
    border-radius: 20px;
}
.cat-status-badge.active {
    background: #dce9d7;
    color: #2e5b2e;
}
.cat-status-badge.inactive {
    background: #f0e0e0;
    color: #8b3a3a;
}

/* Category note */
.category-note {
    margin-top: 20px;
    color: var(--text-light);
    font-size: 12px;
    border-top: 1px solid var(--border-color);
    padding-top: 15px;
    text-align: center;
}
.category-note i {
    margin-right: 6px;
}

/* Responsive */
@media (max-width: 991px) {
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 576px) {
    .category-table th,
    .category-table td {
        font-size: 12px;
        padding: 10px 6px;
    }
    .category-table .cat-icon {
        font-size: 16px;
        width: 24px;
    }
    .content-tab {
        padding: 8px 15px;
        font-size: 10px;
    }
    .content-tab i {
        margin-right: 4px;
    }
}
</style>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Header Card -->
        <div class="dashboard-card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 class="dashboard-title" style="margin:0;">Manage Homepage Content</h2>
                    <p class="dashboard-subtitle">Manage featured lawyers and categories displayed on the homepage</p>
                </div>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="content-tabs">
            <a href="?tab=featured" class="content-tab <?php echo $active_tab == 'featured' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Featured Lawyers
            </a>
            <a href="?tab=categories" class="content-tab <?php echo $active_tab == 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Categories 
                <?php if (count($categories) > 0): ?>
                    <span class="tab-badge"><?php echo count($categories); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- ============================================================
        TAB 1: FEATURED LAWYERS
        ============================================================ -->
        <div class="tab-content <?php echo $active_tab == 'featured' ? 'active' : ''; ?>" id="tab-featured">
            <div class="dashboard-card">
                <h3 class="dashboard-title" style="font-size:28px;">Select Featured Lawyers</h3>
                <p class="dashboard-subtitle">Check the box next to lawyers you want to feature on the homepage</p>

                <!-- FILTER -->
                <div style="margin: 20px 0;">
                    <form method="GET" action="">
                        <input type="hidden" name="tab" value="featured">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Experience</label>
                                <select name="experience">
                                    <option value="">All</option>
                                    <option value="0-5" <?php echo $experience == '0-5' ? 'selected' : ''; ?>>0 - 5 years</option>
                                    <option value="5-10" <?php echo $experience == '5-10' ? 'selected' : ''; ?>>5 - 10 years</option>
                                    <option value="10+" <?php echo $experience == '10+' ? 'selected' : ''; ?>>10+ years</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Rating</label>
                                <select name="rating">
                                    <option value="">All</option>
                                    <option value="4" <?php echo $rating == '4' ? 'selected' : ''; ?>>4★ & above</option>
                                    <option value="3" <?php echo $rating == '3' ? 'selected' : ''; ?>>3★ & above</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Min. Appointments</label>
                                <input type="number" name="min_appointments" value="<?php echo $min_appointments; ?>" placeholder="e.g., 5" min="0">
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="filter-btn">Apply Filters</button>
                            </div>
                            <div class="filter-group">
                                <a href="?tab=featured" class="reset-btn">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="tab" value="featured">
                    <?php if (count($lawyers) > 0): ?>
                        <div class="table-wrap">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px">Select</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Experience</th>
                                        <th>Rating</th>
                                        <th>Appointments</th>
                                        <th>Fees</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lawyers as $row): ?>
                                    <tr>
                                        <td style="text-align:center">
                                            <input type="checkbox" name="featured_lawyers[]" value="<?php echo $row['id']; ?>" class="featured-checkbox" <?php echo $row['is_featured'] == 1 ? 'checked' : ''; ?>>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['profile_pic']) && file_exists("../uploads/lawyers/" . $row['profile_pic'])): ?>
                                                <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($row['profile_pic']); ?>" class="lawyer-img-small" alt="Profile">
                                            <?php else: ?>
                                                <i class="fas fa-user-advocate fa-2x" style="color: var(--primary-color);"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                        <td><?php echo $row['experience']; ?> yrs</td>
                                        <td>★ <?php echo $row['avg_rating'] ?: 'New'; ?></td>
                                        <td><?php echo $row['appointment_count']; ?></td>
                                        <td><?php echo number_format($row['fees']); ?> PKR</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrap">
                            <?php if ($page > 1): ?>
                                <a href="?tab=featured&page=<?php echo $page-1; ?>&experience=<?php echo urlencode($experience); ?>&rating=<?php echo urlencode($rating); ?>&min_appointments=<?php echo $min_appointments; ?>" class="page-link">← Previous</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?tab=featured&page=<?php echo $i; ?>&experience=<?php echo urlencode($experience); ?>&rating=<?php echo urlencode($rating); ?>&min_appointments=<?php echo $min_appointments; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?tab=featured&page=<?php echo $page+1; ?>&experience=<?php echo urlencode($experience); ?>&rating=<?php echo urlencode($rating); ?>&min_appointments=<?php echo $min_appointments; ?>" class="page-link">Next →</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="save-btn-wrapper">
                            <button type="submit" name="save_featured" class="save-btn">Save Featured Lawyers</button>
                        </div>

                    <?php else: ?>
                        <p class="no-data">No lawyers found matching your criteria.</p>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- ============================================================
        TAB 2: CATEGORIES - Simple Add & Display
        ============================================================ -->
        <div class="tab-content <?php echo $active_tab == 'categories' ? 'active' : ''; ?>" id="tab-categories">
            <div class="categories-wrapper">
                <div class="dashboard-card">
                    <h3 class="dashboard-title" style="font-size:28px; text-align:center;">Manage Categories</h3>
                    <p class="dashboard-subtitle" style="text-align:center;">Add categories to display on the homepage</p>

                    <div class="categories-grid">
                        <!-- Add Category Form -->
                        <div class="category-form-card">
                            <h4><i class="fas fa-plus-circle" style="margin-right:10px;color:var(--primary-color);"></i>Add Category</h4>
                            <form method="POST" action="">
                                <input type="hidden" name="tab" value="categories">
                                <div class="category-form-group">
                                    <label>Category Name *</label>
                                    <input type="text" name="name" placeholder="e.g., Criminal Law" required>
                                </div>
                                <div class="category-form-group">
                                    <label>Icon Class</label>
                                    <input type="text" name="icon_class" placeholder="fas fa-gavel" value="fas fa-gavel">
                                </div>
                                <div class="category-form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="category-form-group">
                                    <label>Display Order</label>
                                    <input type="number" name="order_by" value="<?php echo $maxOrder + 1; ?>" min="0">
                                </div>
                                <button type="submit" name="add_category" class="btn-add-category">
                                    <i class="fas fa-plus" style="margin-right:8px;"></i> Add Category
                                </button>
                            </form>
                        </div>

                        <!-- Categories List -->
                        <div>
                            <?php if (count($categories) > 0): ?>
                                <div class="category-table-wrap">
                                    <table class="category-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40px">#</th>
                                                <th style="width:50px">Icon</th>
                                                <th>Name</th>
                                                <th style="width:90px">Status</th>
                                                <th style="width:70px">Order</th>
                                                <th style="width:110px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td><?php echo $cat['id']; ?></td>
                                                <td><i class="<?php echo htmlspecialchars($cat['icon_class']); ?> cat-icon"></i></td>
                                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                                <td>
                                                    <span class="cat-status-badge <?php echo $cat['status']; ?>">
                                                        <?php echo $cat['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $cat['order_by']; ?></td>
                                                <td>
                                                    <div class="action-icons-cat">
                                                        <a href="?tab=categories&edit_cat=<?php echo $cat['id']; ?>" class="action-icon-cat edit" data-tooltip="Edit">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        <a href="?tab=categories&toggle_status=<?php echo $cat['id']; ?>" class="action-icon-cat toggle <?php echo $cat['status'] == 'active' ? '' : 'inactive'; ?>" data-tooltip="Toggle Status" onclick="return confirm('Change status?')">
                                                            <i class="fas <?php echo $cat['status'] == 'active' ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                                        </a>
                                                        <a href="?tab=categories&delete_category=<?php echo $cat['id']; ?>" class="action-icon-cat delete" data-tooltip="Delete" onclick="return confirm('Delete \'<?php echo htmlspecialchars($cat['name']); ?>\'?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div style="text-align:center;padding:40px 20px;background:#faf8f5;border:1px solid var(--border-color);">
                                    <i class="fas fa-tags" style="font-size:48px;color:var(--text-light);margin-bottom:15px;"></i>
                                    <p style="color:var(--text-light);margin:0;">No categories added yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="category-note">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Active</strong> categories appear on the homepage. 
                        Lower <strong>Display Order</strong> numbers appear first.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>