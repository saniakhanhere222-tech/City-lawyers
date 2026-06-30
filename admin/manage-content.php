<?php
/**
 * Admin - Manage Homepage Content (Featured Lawyers)
 * 
 * This page allows the admin to:
 * - Filter approved lawyers by experience, rating, or minimum appointments
 * - Select/unselect featured lawyers (displayed on homepage)
 * - Save featured selection to the database
 * - Paginate through the lawyer list (10 per page)
 */
$page_title = 'Manage Homepage Content';
$dashboard_page = true;
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as admin
// ============================================================
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// ============================================================
// 2. Handle saving featured lawyers
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
// 3. Read filter values (sanitized)
// ============================================================
$experience = isset($_GET['experience']) ? $_GET['experience'] : '';
$rating     = isset($_GET['rating']) ? $_GET['rating'] : '';
$min_appointments = isset($_GET['min_appointments']) ? (int)$_GET['min_appointments'] : 0;

// ============================================================
// 4. Build WHERE clause (simple string concatenation, safe because values are hardcoded or cast to int)
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
    // Directly embed the integer value (safe)
    $conditions[] = "(SELECT COUNT(*) FROM appointments WHERE lawyer_id = lawyers.id) >= $min_appointments";
}

$where = implode(" AND ", $conditions);

// ============================================================
// 5. Pagination
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total rows
$countSql = "SELECT COUNT(*) as total FROM lawyers WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Main query with appointment count
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
// 6. Include header
// ============================================================
include '../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">

<div class="dashboard-wrapper">
    <div class="sidebar">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <a href="<?php echo BASE_URL; ?>admin/index.php">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-lawyers.php">Manage Lawyers</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-appointments.php">Appointments</a>
        <a href="<?php echo BASE_URL; ?>admin/manage-content.php" class="active">Homepage</a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <div class="dashboard-card">
            <h2 class="dashboard-title">Manage Homepage Content</h2>
            <p class="dashboard-subtitle">Select featured lawyers to display on homepage</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- FILTER BOX -->
       <!-- Wrapper to isolate manage content styles -->
<div class="manage-content-wrapper">

    <!-- FILTER BOX -->
    <div class="dashboard-card">
        <form method="GET" action="">
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
                    <label>Min. Appointments Completed</label>
                    <input type="number" name="min_appointments" value="<?php echo $min_appointments; ?>" placeholder="e.g., 5" min="0">
                </div>
                <div class="filter-group">
                    <button type="submit" class="filter-btn">Apply Filters</button>
                </div>
                <div class="filter-group">
                    <a href="manage-content.php" class="reset-btn">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- SELECT FEATURED LAWYERS FORM -->
    <form method="POST" action="">
        <div class="dashboard-card">
            <!-- ... table and pagination ... -->
            <button type="submit" name="save_featured" class="save-btn">Save Featured Lawyers</button>
        </div>
    </form>
</div><!-- end manage-content-wrapper -->

        <!-- SELECT FEATURED LAWYERS FORM -->
        <form method="POST" action="">
            <div class="dashboard-card">
                <h3 class="dashboard-title" style="font-size:28px;">Select Featured Lawyers</h3>
                <p class="dashboard-subtitle">Check the box next to lawyers you want to feature on homepage</p>

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
                                    <td>Adv. <?php echo htmlspecialchars($row['name']); ?></td>
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

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrap">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&experience=<?php echo urlencode($experience); ?>&rating=<?php echo urlencode($rating); ?>&min_appointments=<?php echo $min_appointments; ?>" class="page-link">← Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&experience=<?php echo urlencode($experience); ?>&rating=<?php echo urlencode($rating); ?>&min_appointments=<?php echo $min_appointments; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&experience=<?php echo urlencode($experience); ?>&rating=<?php echo urlencode($rating); ?>&min_appointments=<?php echo $min_appointments; ?>" class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <button type="submit" name="save_featured" class="save-btn">Save Featured Lawyers</button>
                <?php else: ?>
                    <p class="no-data">No lawyers found matching your criteria.</p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>