<?php
// ============================================================
// HOMEPAGE – CityLawyers Public Landing Page
// ============================================================
// This page displays:
// 1. Hero section with search bar (city + specialization)
// 2. Statistics section with animated progress rings (Lawyers, Clients, Cities, Success Rate)
// 3. How It Works section with toggle between Client/Lawyer views
//    - Dynamic step-by-step guide with preview panel
//    - Uses JavaScript to switch between roles and steps
// 4. Featured Lawyers section (top 4 featured or fallback to top rated)
// 5. Practice Domains/Categories section (dynamic from database or hardcoded fallback)
// Uses: homepage.css, header.php, footer.php
// Features:
// - Responsive hero with search functionality
// - Animated SVG progress rings (static values)
// - Interactive "How It Works" with role toggle and step preview
// - Dynamic lawyer cards with profile images
// - Dynamic categories from database with lawyer counts
// - Fallback system for featured lawyers and categories
// ============================================================

$page_layout= 'fluid'; //set in header.php 
$page_title = 'CityLawyers-Hompepage'; 
require_once 'includes/config.php';

// ==============================================
// 1. FEATURED LAWYERS (or fallback to top rated)
// ==============================================

// Step 1: Prepare and execute query for featured lawyers
$featuredQuery = $conn->prepare("SELECT * FROM lawyers WHERE status = 'approved' AND is_featured = 1 ORDER BY id DESC LIMIT 4");
$featuredQuery->execute();

// Step 2: Fetch all rows (as associative array)
$featuredLawyers = $featuredQuery->fetchAll(PDO::FETCH_ASSOC);

// Step 3: If no featured lawyers, get top rated as fallback
if (count($featuredLawyers) == 0) {
    $fallbackQuery = $conn->prepare("SELECT * FROM lawyers WHERE status = 'approved' ORDER BY avg_rating DESC LIMIT 4");
    $fallbackQuery->execute();
    $featuredLawyers = $fallbackQuery->fetchAll(PDO::FETCH_ASSOC);
}

// ==============================================
// 2. CATEGORIES (for Practice Domains section)
// ==============================================

$catQuery = $conn->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY order_by ASC, id ASC");
$catQuery->execute();
$categories = $catQuery->fetchAll(PDO::FETCH_ASSOC);

// ==============================================
// 3. FALLBACK COUNTS FOR HARDCODED CATEGORIES (if categories table empty)
// ==============================================

// These are only used if no categories exist in DB
$criminal_count = 0;
$divorce_count = 0;
$affidavit_count = 0;
$civil_count = 0;

// Only fetch counts if we need fallback later (optional, keep for backward compatibility)
$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyers WHERE status = 'approved' AND specialization = ?");

$countStmt->execute(['Criminal']);
$criminal_count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$countStmt->execute(['Divorce']);
$divorce_count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$countStmt->execute(['Affidavit']);
$affidavit_count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$countStmt->execute(['Civil']);
$civil_count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// ==============================================
// 4. INCLUDE HEADER
// ==============================================
include 'includes/header.php';
?>

<!-- Link homepage-specific CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/homepage.css">

<!-- Hero Section (unchanged) -->
<div class="hero-section">
    <div class="container ">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <div class="hero-content">
                    <p class="hero-tag">Your City's Trusted Legal Network</p>
                    <h1 class="hero-title">Search Lawyers<br><span>Compare. Book</span></h1>
                    <p class="hero-text">Connect with experienced legal professionals across your city. Search by expertise, qualifications, fees, and availability—all in one place.</p>
                    
                    <form action="customer/search.php" method="GET">
                        <div class="hero-buttons">
                            <input type="text" name="city" class="form-control luxury-input" placeholder="City / Location">
                            <select name="specialization" class="form-select luxury-input">
                                <option value="">Practice Area</option>
                                <?php 
                                // Populate dropdown from dynamic categories (if any)
                                if (count($categories) > 0) {
                                    foreach ($categories as $cat) {
                                        echo '<option value="'.htmlspecialchars($cat['name']).'">'.htmlspecialchars($cat['name']).' Law</option>';
                                    }
                                } else {
                                    // Fallback hardcoded options
                                    ?>
                                    <option value="Criminal">Criminal Law</option>
                                    <option value="Divorce">Divorce Law</option>
                                    <option value="Affidavit">Affidavit Law</option>
                                    <option value="Civil">Civil Law</option>
                                    <?php
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-luxury">Discover</button>
                        </div>
                    </form>
                </div>
            </div>
            
           <div class="col-lg-6">
    <div class="hero-image-wrapper">

        <img src="assets/images/hero-img.png"
             alt="Lawyer"
             class="hero-image">

        <!-- image fade overlay -->
        <div class="hero-image-fade"></div>

        <div class="quote-box">
    <p class="quote-text">"Integrity is the bedrock of justice."</p>
    <div class="quote-author">
        <div class="quote-avatar">JM</div>
        <div class="quote-author-info">
            <span class="quote-author-name">John Marshall</span>
            <span class="quote-author-role">Former Chief Justice</span>
        </div>
    </div>
</div>
    </div>
</div>
        </div>
    </div>
</div>

<!-- ===========================
     STATS SECTION
=========================== -->
<section class="stats-section">

    <div class="container">

        <!-- <div class="stats-heading">
            <h2>Trusted Across Pakistan</h2>
            <p>
                Connecting clients with experienced legal professionals through
                a modern, secure and transparent platform.
            </p>
        </div> -->

        <div class="row g-4">

            <!-- Lawyers -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">

                    <div class="progress-ring">

                        <svg width="130" height="130" viewBox="0 0 120 120">

                            <circle
                                class="ring-bg"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                            <circle
                                class="ring-progress progress-90"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                        </svg>

                        <div class="ring-value">
                            100+
                        </div>

                    </div>

                    <h4>Verified Lawyers</h4>

                </div>
            </div>

            <!-- Clients -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">

                    <div class="progress-ring">

                        <svg width="130" height="130" viewBox="0 0 120 120">

                            <circle
                                class="ring-bg"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                            <circle
                                class="ring-progress progress-98"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                        </svg>

                        <div class="ring-value">
                            500+
                        </div>

                    </div>

                    <h4>Happy Clients</h4>

                </div>
            </div>

            <!-- Cities -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">

                    <div class="progress-ring">

                        <svg width="130" height="130" viewBox="0 0 120 120">

                            <circle
                                class="ring-bg"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                            <circle
                                class="ring-progress progress-70"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                        </svg>

                        <div class="ring-value">
                            50+
                        </div>

                    </div>

                    <h4>Cities Covered</h4>

                </div>
            </div>

            <!-- Success -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">

                    <div class="progress-ring">

                        <svg width="130" height="130" viewBox="0 0 120 120">

                            <circle
                                class="ring-bg"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                            <circle
                                class="ring-progress progress-98"
                                cx="60"
                                cy="60"
                                r="52">
                            </circle>

                        </svg>

                        <div class="ring-value">
                            98%
                        </div>

                    </div>

                    <h4>Success Rate</h4>

                </div>
            </div>

        </div>

    </div>

</section>

<!-- =========================================
    // HOW IT WORKS – Interactive Step Guide
// ============================================================
// This script:
// 1. Defines step data for both roles (client/lawyer)
// 2. Toggles between roles when buttons are clicked
// 3. Updates the step list and preview panel dynamically
// 4. Allows users to click on steps to view details
========================================= -->
<section class="how-it-works-section">
    <div class="container">

        <div class="text-center section-header">
            <p class="section-subtitle">Simple From Start To Finish</p>
            <h2 class="section-title">How It Works</h2>
        </div>

        <div class="text-center">
            <div class="hiw-toggle" role="tablist" aria-label="Choose a role to see the flow">
                <button type="button" class="hiw-toggle-btn active" data-role="client">For Clients</button>
                <button type="button" class="hiw-toggle-btn" data-role="lawyer">For Lawyers</button>
            </div>
        </div>

        <div class="hiw-body">

            <!-- Step list (populated per role by JS) -->
            <div class="hiw-steps" id="hiwSteps"></div>

            <!-- Preview panel (populated per step by JS) -->
            <div class="hiw-preview" id="hiwPreview"></div>

        </div>

    </div>
</section>


<!-- Featured Lawyers Section -->
<div class="featured-section">
    <div class="container">

        <div class="text-center mb-5">
            <h2 class="section-title">Featured Lawyers</h2>
            <p class="section-subtitle">Our Top Rated Attorneys</p>
        </div>

        <div class="row g-4">

            <?php if(count($featuredLawyers) > 0): ?>

                <?php foreach($featuredLawyers as $row): ?>

                    <div class="col-lg-3 col-md-6 col-sm-6">

                        <div class="lawyer-card-home h-100">

                            <div class="lawyer-img-home">

                                <?php if(!empty($row['profile_pic']) && file_exists("uploads/lawyers/".$row['profile_pic'])): ?>

                                    <img
                                        src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo $row['profile_pic']; ?>"
                                        alt="<?php echo htmlspecialchars($row['name']); ?>">

                                <?php else: ?>

                                    <div class="lawyer-placeholder">
                                        <i class="fas fa-user-advocate"></i>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="lawyer-card-body">

                                <h4>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </h4>

                                <p class="specialization">
                                    <?php echo strtoupper($row['specialization']); ?>
                                </p>

                                <p class="fees">
                                    <?php echo number_format($row['fees']); ?> PKR
                                </p>

                                <a
                                    href="customer/lawyer-profile.php?id=<?php echo $row['id']; ?>"
                                    class="btn-view-profile">

                                    View Profile

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center">

                    <p>No featured lawyers yet.</p>

                </div>

            <?php endif; ?>

        </div>

        <div class="text-center mt-5">

            <a href="customer/search.php" class="btn-view-all">
                View All Lawyers →
            </a>

        </div>

    </div>
</div>

<!-- Practice Domains Section (Dynamic Categories) -->
<div class="categories-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Areas We Cover</h2>
            <p class="section-subtitle">Explore our specialized legal services </p>
        </div>
       
        <div class="row g-4">
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    // Count lawyers for this category
                    $lawyerCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyers WHERE status = 'approved' AND specialization = ?");
                    $lawyerCountStmt->execute([$cat['name']]);
                    $lawyerCount = $lawyerCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="col-md-3 col-6">
                        <a href="customer/search.php?specialization=<?php echo urlencode($cat['name']); ?>" class="category-card">
                            <div class="category-icon"><i class="<?php echo htmlspecialchars($cat['icon_class']); ?>"></i></div>
                            <h4><?php echo htmlspecialchars($cat['name']); ?></h4>
                            <p><?php echo $lawyerCount; ?> REGISTERED LAWYERS</p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback hardcoded categories (if no categories in DB) -->
                <div class="col-md-3 col-6">
                    <a href="customer/search.php?specialization=Criminal" class="category-card">
                        <div class="category-icon"><i class="fas fa-scale-balanced"></i></div>
                        <h4>Criminal</h4>
                        <p><?php echo $criminal_count; ?> REGISTERED LAWYERS</p>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="customer/search.php?specialization=Divorce" class="category-card">
                        <div class="category-icon"><i class="fas fa-heart-crack"></i></div>
                        <h4>Divorce</h4>
                        <p><?php echo $divorce_count; ?> REGISTERED LAWYERS</p>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="customer/search.php?specialization=Affidavit" class="category-card">
                        <div class="category-icon"><i class="fas fa-file-signature"></i></div>
                        <h4>Affidavit</h4>
                        <p><?php echo $affidavit_count; ?> REGISTERED LAWYERS</p>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="customer/search.php?specialization=Civil" class="category-card">
                        <div class="category-icon"><i class="fas fa-landmark"></i></div>
                        <h4>Civil</h4>
                        <p><?php echo $civil_count; ?> REGISTERED LAWYERS</p>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>