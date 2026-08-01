<?php
// ============================================================
// Find Lawyers Page – Search & Filter
// ============================================================
$page_title = 'Find Lawyers';
require_once '../includes/config.php';

// ============================================================
// 1. Get filter values (single values from radio buttons)
// ============================================================
$selected_city         = $_GET['city']         ?? '';
$selected_rating       = $_GET['rating']       ?? '';
$selected_specialization = $_GET['specialization'] ?? '';
$selected_gender       = $_GET['gender']       ?? '';
$selected_exp          = $_GET['experience']   ?? '';
$selected_fees         = $_GET['fees']         ?? '';

// ============================================================
// 2. Build WHERE clause and parameters array (PDO safe)
// ============================================================
$where = "status = 'approved'";
$params = [];

// City filter
if (!empty($selected_city)) {
    $where .= " AND city = :city";
    $params[':city'] = $selected_city;
}

// Specialization filter
if (!empty($selected_specialization)) {
    $where .= " AND specialization = :spec";
    $params[':spec'] = $selected_specialization;
}

// Gender filter
if (!empty($selected_gender)) {
    $where .= " AND gender = :gender";
    $params[':gender'] = $selected_gender;
}

// Rating filter
if (!empty($selected_rating)) {
    if ($selected_rating == '4') {
        $where .= " AND avg_rating >= 4";
    } elseif ($selected_rating == '3') {
        $where .= " AND avg_rating >= 3 AND avg_rating < 4";
    }
}

// Experience filter
if (!empty($selected_exp)) {
    if ($selected_exp == '0-5') {
        $where .= " AND experience BETWEEN 0 AND 5";
    } elseif ($selected_exp == '5-10') {
        $where .= " AND experience BETWEEN 5 AND 10";
    } elseif ($selected_exp == '10+') {
        $where .= " AND experience >= 10";
    }
}

// Fees filter
if (!empty($selected_fees)) {
    if ($selected_fees == 'under3000') {
        $where .= " AND fees < 3000";
    } elseif ($selected_fees == '3000-6000') {
        $where .= " AND fees BETWEEN 3000 AND 6000";
    } elseif ($selected_fees == '6000-10000') {
        $where .= " AND fees BETWEEN 6000 AND 10000";
    } elseif ($selected_fees == '10000+') {
        $where .= " AND fees >= 10000";
    }
}

// ============================================================
// 3. Pagination
// ============================================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// ============================================================
// 4. Get total count of lawyers (for pagination)
// ============================================================
$countSql = "SELECT COUNT(*) as total FROM lawyers WHERE $where";
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// ============================================================
// 5. Get lawyers for current page (with pagination)
//    Using safe integer injection for LIMIT/OFFSET
// ============================================================
$sql = "SELECT * FROM lawyers WHERE $where ORDER BY avg_rating DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);

// Bind only the filter parameters (named placeholders)
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$lawyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 6. Get data for filter dropdowns (distinct cities, etc.)
// ============================================================
// Cities (distinct, approved lawyers only)
$cityStmt = $conn->prepare("SELECT DISTINCT city FROM lawyers WHERE status = 'approved' ORDER BY city");
$cityStmt->execute();
$cities = $cityStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 6b. DYNAMIC SPECIALIZATIONS - Fetch from categories table
// ============================================================
$specStmt = $conn->prepare("SELECT name FROM categories WHERE status = 'active' ORDER BY order_by ASC, name ASC");
$specStmt->execute();
$specializations = $specStmt->fetchAll(PDO::FETCH_COLUMN);

// If no categories found, fallback to hardcoded list
if (empty($specializations)) {
    $specializations = ['Criminal', 'Divorce', 'Affidavit', 'Civil'];
}

// ============================================================
// 7. Genders
// ============================================================
$genders = ['male', 'female'];

// ============================================================
// 8. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- ============================================================
     External CSS for search page
     ============================================================ -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/search.css">

<div class="search-container">
    <!-- STICKY SIDEBAR (FILTERS) -->
    <div class="search-sidebar">
        <div class="filter-card">
            <div class="card-header">
                <h5><i class="fas fa-sliders-h"></i> Filter Counsel</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    
                    <!-- City Filter -->
                    <div class="filter-section">
                        <div class="filter-header" data-filter="city">
                            <strong><i class="fas fa-city"></i> City</strong>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="filter-content" id="filter-city">
                            <?php foreach ($cities as $city): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="city" value="<?php echo htmlspecialchars($city['city']); ?>" 
                                        <?php echo ($selected_city == $city['city']) ? 'checked' : ''; ?>
                                        onchange="this.form.submit()">
                                    <label class="form-check-label"><?php echo htmlspecialchars($city['city']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Specialization Filter - DYNAMIC FROM CATEGORIES TABLE -->
                    <div class="filter-section">
                        <div class="filter-header" data-filter="spec">
                            <strong><i class="fas fa-briefcase"></i> Specialization</strong>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="filter-content" id="filter-spec">
                            <?php foreach ($specializations as $spec): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="specialization" value="<?php echo htmlspecialchars($spec); ?>" 
                                        <?php echo ($selected_specialization == $spec) ? 'checked' : ''; ?>
                                        onchange="this.form.submit()">
                                    <label class="form-check-label"><?php echo htmlspecialchars($spec); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Rating Filter -->
                    <div class="filter-section">
                        <div class="filter-header" data-filter="rating">
                            <strong><i class="fas fa-star"></i> Rating</strong>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="filter-content" id="filter-rating">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" value="4" 
                                    <?php echo ($selected_rating == '4') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">★★★★☆ (4★ & above)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" value="3" 
                                    <?php echo ($selected_rating == '3') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">★★★☆☆ (3★ & above)</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gender Filter -->
                    <div class="filter-section">
                        <div class="filter-header" data-filter="gender">
                            <strong><i class="fas fa-venus-mars"></i> Gender</strong>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="filter-content" id="filter-gender">
                            <?php foreach ($genders as $gender): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" value="<?php echo $gender; ?>" 
                                        <?php echo ($selected_gender == $gender) ? 'checked' : ''; ?>
                                        onchange="this.form.submit()">
                                    <label class="form-check-label"><?php echo ucfirst($gender); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Experience Filter -->
                    <div class="filter-section">
                        <div class="filter-header" data-filter="exp">
                            <strong><i class="fas fa-clock"></i> Experience</strong>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="filter-content" id="filter-exp">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="experience" value="0-5" 
                                    <?php echo ($selected_exp == '0-5') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">0 - 5 years</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="experience" value="5-10" 
                                    <?php echo ($selected_exp == '5-10') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">5 - 10 years</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="experience" value="10+" 
                                    <?php echo ($selected_exp == '10+') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">10+ years</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fees Filter -->
                    <div class="filter-section">
                        <div class="filter-header" data-filter="fees">
                            <strong><i class="fas fa-money-bill"></i> Fees (PKR)</strong>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="filter-content" id="filter-fees">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fees" value="under3000" 
                                    <?php echo ($selected_fees == 'under3000') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">Under 3,000</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fees" value="3000-6000" 
                                    <?php echo ($selected_fees == '3000-6000') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">3,000 - 6,000</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fees" value="6000-10000" 
                                    <?php echo ($selected_fees == '6000-10000') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">6,000 - 10,000</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fees" value="10000+" 
                                    <?php echo ($selected_fees == '10000+') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label class="form-check-label">10,000+</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidenav-btn">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                        <a href="search.php" class="reset-btn">Reset All</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SCROLLABLE RESULTS -->
    <div class="search-results">
        <div class="results-header">
            <div class="results-count">
                <i class="fas fa-gavel"></i> <?php echo $total_rows; ?> Counsel Found
            </div>
        </div>
        
        <?php if (count($lawyers) > 0): ?>
            <?php foreach ($lawyers as $row): ?>
                <div class="lawyer-card">
                    <div class="lawyer-card-inner">
                        <!-- IMAGE -->
                        <div>
                            <div class="lawyer-img-wrapper">
                                <?php if (!empty($row['profile_pic']) && file_exists("../uploads/lawyers/" . $row['profile_pic'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo $row['profile_pic']; ?>" class="lawyer-img">
                                <?php else: ?>
                                    <div class="lawyer-img-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- CONTENT -->
                        <div class="lawyer-content">
                            <div class="lawyer-top">
                                <div>
                                    <h3 class="lawyer-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <p class="lawyer-role">
                                        <?php
                                        if ($row['experience'] >= 10) {
                                            echo "SENIOR LEGAL PARTNER";
                                        } elseif ($row['experience'] >= 5) {
                                            echo "ASSOCIATE PARTNER";
                                        } else {
                                            echo "LEGAL CONSULTANT";
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div class="lawyer-fee">
                                    <h5><?php echo number_format($row['fees']); ?> PKR</h5>
                                    <span>Consultation Fee</span>
                                </div>
                            </div>
                            
                            <p class="lawyer-bio">
                                "<?php echo $row['bio'] ? htmlspecialchars(substr($row['bio'], 0, 140)) : 'Experienced legal professional dedicated to providing trusted legal counsel.'; ?>"
                            </p>
                            
                            <div class="lawyer-meta">
                                <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($row['city']); ?></span>
                                <span><i class="fas fa-gavel"></i> <?php echo htmlspecialchars($row['specialization']); ?></span> 
                                <span><i class="fas fa-briefcase"></i> <?php echo $row['experience']; ?> Years</span>
                                <span>★ <?php echo $row['avg_rating'] ?: 'New'; ?> Rated</span>
                                <span><i class="fas fa-venus-mars"></i> <?php echo ucfirst($row['gender']); ?></span>
                            </div>
                        </div>
                        
                        <!-- BUTTONS -->
                        <div class="lawyer-actions">
                            <a href="lawyer-profile.php?id=<?php echo $row['id']; ?>" class="details-btn">Show Profile</a>
                            <a href="book-appointment.php?id=<?php echo $row['id']; ?>" class="book-btn">Book appointment</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrap">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => 0])); ?>" class="page-link">← Previous</a>
                <?php endif; ?>
                
                <span class="mx-3" style="font-size: 12px; color: #8a8479;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => 0])); ?>" class="page-link">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <p class="text-muted text-center" style="padding: 60px 0;">No lawyers found matching your criteria.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.filter-header').click(function() {
        var filterId = $(this).data('filter');
        $('#filter-' + filterId).toggleClass('show');
        $(this).find('.arrow').toggleClass('rotate');
    });
    
    $('.filter-content').each(function() {
        if($(this).find('input:checked').length > 0) {
            $(this).addClass('show');
            $(this).prev('.filter-header').find('.arrow').addClass('rotate');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>