<?php
$page_title = 'Find Best Lawyers';
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
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <div class="hero-content">
                    <p class="hero-tag">DIRECT ACCESS TO LEGAL EXCELLENCE</p>
                    <h1 class="hero-title">Counsel in <br><span>Every Motion.</span></h1>
                    <p class="hero-text">LegalFlow represents the interaction of tradition and digital innovation. Connect with trusted legal professionals across the nation for consultations, representation, and legal advisory.</p>
                    
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
                    <img src="assets/images/home1.jpg" alt="Lawyer" class="hero-image">
                    <div class="quote-box">
                        <p>“Integrity is the bedrock of justice.”</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section (unchanged) -->
<div class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h2>100+</h2>
                    <p>Verified Lawyers</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h2>500+</h2>
                    <p>Happy Clients</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h2>50+</h2>
                    <p>Cities Covered</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h2>98%</h2>
                    <p>Success Rate</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Lawyers Section -->
<div class="featured-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Featured lawyers</h2>
            <p class="section-subtitle">our top rated attorneys</p>
        </div>
        
        <div class="row g-4">
            <?php if (count($featuredLawyers) > 0): ?>
                <?php foreach ($featuredLawyers as $row): ?>
                    <div class="col-md-3 col-6">
                        <div class="lawyer-card-home">
                            <div class="lawyer-img-home">
                                <?php if (!empty($row['profile_pic']) && file_exists("uploads/lawyers/" . $row['profile_pic'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo $row['profile_pic']; ?>" alt="<?php echo $row['name']; ?>">
                               <?php else: ?>
                                <i class="fas fa-user-advocate"></i>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo $row['name']; ?></h4>
                            <p class="specialization"><?php echo strtoupper($row['specialization']); ?></p>
                            <p class="fees"><?php echo number_format($row['fees']); ?> PKR</p>
                            <a href="customer/lawyer-profile.php?id=<?php echo $row['id']; ?>" class="btn-view-profile">View Profile</a>
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
            <a href="customer/search.php" class="btn-view-all">VIEW ALL PARTNERS →</a>
        </div>
    </div>
</div>

<!-- Practice Domains Section (Dynamic Categories) -->
<div class="categories-section">
    <div class="container">
        <h2 class="section-title text-center mb-5">Practice Domains</h2>
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