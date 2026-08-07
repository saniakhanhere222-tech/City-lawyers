<?php
/**
 * Admin - Manage Homepage Content
 * 
 * This page controls all content displayed on the public homepage with two main tabs:
 * 
 * TAB 1: Featured Lawyers
 * - Select featured lawyers to display on homepage
 * - Filter lawyers by experience, rating, and minimum appointments
 * - Paginated lawyer list (10 per page)
 * - Checkbox selection with save functionality
 * - Lawyer details: name, specialization, experience, rating, appointments, fees
 * 
 * TAB 2: Categories (Practice Areas)
 * - Full CRUD operations for categories:
 *   - Add: Create new categories with name, icon, status, order
 *   - Edit: Modify existing categories
 *   - Delete: Remove categories (permanent)
 *   - Toggle: Activate/deactivate categories
 * - Display order management (lower numbers appear first)
 * - Category count badge on tab
 * 
 * Features:
 * - Tabbed interface with URL-based state management
 * - Dynamic WHERE clause builder for filtering
 * - Subquery for counting lawyer appointments
 * - File existence check for profile images
 * - Form validation for category operations
 * 
 * Database Tables Used:
 * - lawyers (is_featured flag, profile_pic, status)
 * - categories (name, icon_class, status, order_by)
 * - appointments (for appointment counts)
 * 
 * Related Files:
 * - ../includes/config.php - Database connection
 * - ../includes/header.php - Global header
 * - ../includes/dashboard-sidebar.php - Navigation
 * - ../includes/dashboard-footer.php - Footer
 * - assets/css/dashboard.css - Dashboard styling
 * - assets/css/tables.css - Table styling
 * - assets/css/sidebar.css - Sidebar styling
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



<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">
<!-- page-specific styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/manage-content.css">

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