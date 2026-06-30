<?php
/**
 * Customer - Lawyer Profile Page
 * 
 * Displays detailed information about a specific lawyer,
 * including bio, contact details, working hours, and client reviews.
 */

// ============================================================
// 1. Page setup and configuration
// ============================================================
$page_title = 'Lawyer Profile';
require_once '../includes/config.php';

// ============================================================
// 2. Get and validate lawyer ID from URL
// ============================================================
$lawyer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================================
// 3. Fetch lawyer details (only approved lawyers)
// ============================================================
$lawyerStmt = $conn->prepare("SELECT * FROM lawyers WHERE id = ? AND status = 'approved'");
$lawyerStmt->execute([$lawyer_id]);
$lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);

// If no lawyer found, redirect back to search page
if (!$lawyer) {
    header("Location: search.php");
    exit();
}

// ============================================================
// 4. Fetch recent reviews (limit 5) and total review count
// ============================================================
$reviewStmt = $conn->prepare("
    SELECT r.*, c.name as customer_name 
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.id 
    WHERE r.lawyer_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$reviewStmt->execute([$lawyer_id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE lawyer_id = ?");
$countStmt->execute([$lawyer_id]);
$review_count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

// ============================================================
// 5. Include the global header (loads global.css, etc.)
// ============================================================
include '../includes/header.php';
?>

<!-- Link to profile-specific CSS (no inline styles) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/lawyer-profile.css">

<!-- Return link (back to search results) -->
<div class="profile-return-link">
    <a href="search.php">← RETURN TO FIND LAWYERS</a>
</div>

<div class="profile-container">

    <!-- ========================================================
         TOP PANEL: Avatar, name, specialization, rating, fee, city, experience, gender
    ======================================================== -->
    <div class="profile-top-panel">
        <!-- Lawyer avatar or placeholder icon -->
        <div class="profile-avatar-large">
            <?php if (!empty($lawyer['profile_pic']) && file_exists("../uploads/lawyers/" . $lawyer['profile_pic'])): ?>
                <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($lawyer['profile_pic']); ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user-advocate"></i>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <h1><?php echo htmlspecialchars($lawyer['name']); ?></h1>
            <div class="profile-title"><?php echo strtoupper(htmlspecialchars($lawyer['specialization'])); ?> SPECIALIST</div>

            <!-- Rating stars and fee badge -->
            <div class="profile-rating-badge">
                <div class="profile-stars">
                    <?php
                    $rating = round($lawyer['avg_rating']);
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $rating) ? '<i class="fas fa-star"></i>' : '<i class="fas fa-star-o"></i>';
                    }
                    ?>
                    <span><?php echo $lawyer['avg_rating'] ?: 'New'; ?> / 5.0</span>
                </div>
                <div class="profile-fee-badge"><?php echo number_format($lawyer['fees']); ?> PKR / Consultation</div>
            </div>

            <!-- Contact info: city, experience, gender -->
            <div class="profile-contact-info">
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($lawyer['city']); ?></span>
                <span><i class="fas fa-briefcase"></i> <?php echo $lawyer['experience']; ?> Years</span>
                <span><i class="fas fa-venus-mars"></i> <?php echo ucfirst($lawyer['gender']); ?></span>
            </div>
        </div>
    </div>

    <!-- ========================================================
         TWO COLUMN LAYOUT: Left = Biography, Right = Sidebar
    ======================================================== -->
    <div class="profile-two-columns">

        <!-- LEFT COLUMN: Professional biography and specialisations -->
        <div class="profile-bio-card">
            <h2>Professional Dossier</h2>
            <div class="profile-bio-content">
                <p><?php echo nl2br(htmlspecialchars($lawyer['bio'] ?: 'Experienced legal professional dedicated to providing excellent legal services.')); ?></p>
                <p>With a tenure of over <?php echo $lawyer['experience']; ?> years within the <?php echo htmlspecialchars($lawyer['specialization']); ?> domain, Counsel has navigated complex legal architectures.</p>
            </div>

            <!-- Core specialisations (comma‑separated) -->
            <?php if (!empty($lawyer['core_specialization'])): ?>
                <div class="profile-specialization-list">
                    <h3>CORE SPECIALIZATION</h3>
                    <ul>
                        <?php foreach (explode(',', $lawyer['core_specialization']) as $item): ?>
                            <?php if (trim($item) !== ''): ?>
                                <li><?php echo htmlspecialchars(trim($item)); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Academic credentials (comma‑separated) -->
            <?php if (!empty($lawyer['academic_credentials'])): ?>
                <div class="profile-specialization-list">
                    <h3>ACADEMIC CREDENTIALS</h3>
                    <ul>
                        <?php foreach (explode(',', $lawyer['academic_credentials']) as $item): ?>
                            <?php if (trim($item) !== ''): ?>
                                <li><?php echo htmlspecialchars(trim($item)); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: Sidebar with working hours, contact, action buttons -->
        <div class="profile-sidebar">

           <!-- ADVISORY HOURS (dynamic from slots table) -->
<div class="profile-sidebar-card">
    <div class="profile-sidebar-header">ADVISORY HOURS</div>
    <div class="profile-sidebar-body">
        <?php
        // Fetch all available slots for this lawyer, ordered by day and start time
        $slotStmt = $conn->prepare("
            SELECT day_of_week, start_time, end_time 
            FROM slots 
            WHERE lawyer_id = ? AND is_available = 1 
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time
        ");
        $slotStmt->execute([$lawyer_id]);
        $slots = $slotStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($slots) > 0):
            foreach ($slots as $slot):
                $start = date('h:i A', strtotime($slot['start_time']));
                $end   = date('h:i A', strtotime($slot['end_time']));
        ?>
            <div class="profile-slot-item">
                <span class="profile-slot-day"><?php echo strtoupper($slot['day_of_week']); ?></span>
                <span class="profile-slot-time"><?php echo $start; ?> – <?php echo $end; ?></span>
            </div>
        <?php
            endforeach;
        else:
        ?>
            <div class="profile-slot-item">
                <span class="profile-slot-day">No slots</span>
                <span class="profile-slot-time">—</span>
            </div>
        <?php endif; ?>
    </div>
</div>

            <!-- Firm communications (phone, email, city) -->
            <div class="profile-sidebar-card">
                <div class="profile-sidebar-header">FIRM COMMUNICATIONS</div>
                <div class="profile-sidebar-body">
                    <div class="profile-contact-detail">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($lawyer['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="profile-contact-detail">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($lawyer['email']); ?></span>
                    </div>
                    <div class="profile-contact-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($lawyer['city']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Call‑to‑action buttons -->
            <div class="profile-sidebar-card">
                <div class="profile-sidebar-body">
                    <div class="profile-action-buttons">
                        <a href="book-appointment.php?id=<?php echo $lawyer['id']; ?>" class="profile-btn-book">BOOK APPOINTMENT →</a>
                        <a href="#" class="profile-btn-inquiry">CONFIDENTIAL INQUIRY</a>
                    </div>
                </div>
            </div>

        </div> <!-- end sidebar -->
    </div> <!-- end two-columns -->

    <!-- ========================================================
         REVIEWS SECTION (only shown if there are reviews)
    ======================================================== -->
    <?php if ($review_count > 0): ?>
    <div class="profile-reviews-card">
        <div class="profile-reviews-header">CLIENT ENDORSEMENTS (<?php echo $review_count; ?>)</div>
        <?php foreach ($reviews as $review): ?>
            <div class="profile-review-item">
                <div class="profile-review-header">
                    <span class="profile-review-name"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                    <span class="profile-review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></span>
                </div>
                <div class="profile-review-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php echo ($i <= $review['rating']) ? '<i class="fas fa-star"></i>' : '<i class="fas fa-star-o"></i>'; ?>
                    <?php endfor; ?>
                </div>
                <p class="profile-review-comment">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div> <!-- end profile-container -->

<?php include '../includes/footer.php'; ?>