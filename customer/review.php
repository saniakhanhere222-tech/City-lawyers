<?php
// ============================================================
// CUSTOMER - REVIEW LAWYER
// ============================================================
// This page allows customers to submit reviews for completed appointments:
//
// 1. Review Form: Star rating (1-5) + comment textarea
// 2. Validation: Appointment ownership, status = 'completed', no duplicates
// 3. Processing: Insert review, calculate new average, update lawyer rating
// 4. Success: Thank you page with navigation to profile or appointments
//
// Features:
// - Authentication required (customer only)
// - One review per appointment
// - Automatic average rating update
// - Interactive star rating UI
// - Session-based error handling
//
// Database Tables: appointments, reviews, lawyers
//
// Security:
// - Prepared statements
// - Appointment ownership verification
// - Duplicate prevention
// - Input validation (rating 1-5)
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/footer.php - Global footer
// - customer/my-appointments.php - Source page
// - customer/lawyer-profile.php - Destination page
// ============================================================
$page_title = 'Review Lawyer';

require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as customer
// ============================================================
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// ============================================================
// 2. Get and validate appointment ID from URL
// ============================================================
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointment_id <= 0) {
    header("Location: my-appointments.php");
    exit();
}

// ============================================================
// 3. Fetch appointment details with lawyer info
// ============================================================
$apptStmt = $conn->prepare("
    SELECT a.*, l.id as lawyer_id, l.name as lawyer_name, l.specialization 
    FROM appointments a 
    JOIN lawyers l ON a.lawyer_id = l.id 
    WHERE a.id = ? AND a.customer_id = ?
");
$apptStmt->execute([$appointment_id, $customer_id]);
$appointment = $apptStmt->fetch(PDO::FETCH_ASSOC);

// If appointment not found or doesn't belong to this customer
if (!$appointment) {
    header("Location: my-appointments.php");
    exit();
}

// ============================================================
// 4. Verify appointment is completed
// ============================================================
if ($appointment['status'] != 'completed') {
    $_SESSION['review_error'] = "You can only review appointments that have been completed.";
    header("Location: my-appointments.php");
    exit();
}

// ============================================================
// 5. Check if review already exists for this appointment
// ============================================================
$checkStmt = $conn->prepare("SELECT id FROM reviews WHERE appointment_id = ? AND customer_id = ?");
$checkStmt->execute([$appointment_id, $customer_id]);
if ($checkStmt->fetch()) {
    $_SESSION['review_error'] = "You have already reviewed this appointment.";
    header("Location: my-appointments.php");
    exit();
}

// ============================================================
// 6. Process form submission
// ============================================================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');

    // Validate rating (1-5)
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating from 1 to 5 stars.";
    } else {
        try {
            // Insert review
            $insertStmt = $conn->prepare("
                INSERT INTO reviews (lawyer_id, customer_id, appointment_id, rating, comment, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insertStmt->execute([
                $appointment['lawyer_id'],
                $customer_id,
                $appointment_id,
                $rating,
                $comment
            ]);

            // Update lawyer's average rating
            $avgStmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE lawyer_id = ?");
            $avgStmt->execute([$appointment['lawyer_id']]);
            $avg = $avgStmt->fetch(PDO::FETCH_ASSOC)['avg_rating'];
            $avg = round($avg, 1);

            $updateStmt = $conn->prepare("UPDATE lawyers SET avg_rating = ? WHERE id = ?");
            $updateStmt->execute([$avg, $appointment['lawyer_id']]);

            $success = "Thank you for your review! Your feedback helps others make informed decisions.";
            
            // Redirect to lawyer profile after 2 seconds (or show success message)
            // We'll show success page with a button to go to profile

        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// ============================================================
// 7. Include header
// ============================================================
include '../includes/header.php';
?>

<style>
/* ========================================
   REVIEW PAGE STYLES
======================================== */
.review-container {
    max-width: 700px;
    margin: 50px auto;
    padding: 0 20px;
}
.review-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    padding: 35px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.04);
}
.review-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    color: var(--primary-color);
    margin-bottom: 5px;
}
.review-subtitle {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 25px;
}
.lawyer-info {
    background: var(--secondary-color);
    padding: 15px 20px;
    margin-bottom: 25px;
    border-left: 3px solid var(--primary-color);
}
.lawyer-info h4 {
    margin: 0;
    color: var(--primary-color);
}
.lawyer-info p {
    margin: 0;
    color: var(--text-light);
    font-size: 12px;
}
/* Star rating */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 8px;
    font-size: 40px;
    margin-bottom: 20px;
}
.star-rating input {
    display: none;
}
.star-rating label {
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #f5b301;
}
/* Comment textarea */
.review-comment textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border-color);
    font-size: 14px;
    min-height: 120px;
    resize: vertical;
}
.review-comment textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}
/* Submit button */
.btn-submit-review {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 30px;
    font-size: 13px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: 0.3s;
}
.btn-submit-review:hover {
    background: #1f291f;
}
/* Error / Success messages */
.alert-error {
    background: #f8f0f0;
    color: #8b3a3a;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #e0c5c5;
}
.alert-success {
    background: #dce9d7;
    color: #2e5b2e;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #c5d4c5;
}
/* Success page */
.success-page {
    text-align: center;
    padding: 40px 20px;
}
.success-page i {
    font-size: 64px;
    color: #2e5b2e;
    margin-bottom: 20px;
}
.success-page h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    color: var(--primary-color);
    margin-bottom: 10px;
}
.success-page p {
    color: var(--text-light);
    margin-bottom: 25px;
}
.btn-profile {
    background: var(--primary-color);
    color: white;
    padding: 10px 25px;
    text-decoration: none;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-block;
}
.btn-profile:hover {
    background: #1f291f;
}
.btn-back {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-dark);
    padding: 10px 25px;
    text-decoration: none;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-block;
    margin-left: 10px;
}
.btn-back:hover {
    background: #f0ebe4;
}
</style>

<div class="review-container">

    <?php if ($success): ?>
        <!-- SUCCESS PAGE -->
        <div class="review-card success-page">
            <i class="fas fa-check-circle"></i>
            <h3>Thank You for Your Review!</h3>
            <p>Your feedback helps Adv. <?php echo htmlspecialchars($appointment['lawyer_name']); ?> improve and assists other clients in making informed decisions.</p>
            <div>
                <a href="lawyer-profile.php?id=<?php echo $appointment['lawyer_id']; ?>" class="btn-profile">View Lawyer Profile</a>
                <a href="my-appointments.php" class="btn-back">My Appointments</a>
            </div>
        </div>

    <?php else: ?>
        <!-- REVIEW FORM -->
        <div class="review-card">
            <h2 class="review-title">Write a Review</h2>
            <p class="review-subtitle">Share your experience with this lawyer</p>

            <!-- Lawyer Info -->
            <div class="lawyer-info">
                <h4>Adv. <?php echo htmlspecialchars($appointment['lawyer_name']); ?></h4>
                <p><?php echo htmlspecialchars($appointment['specialization']); ?> Specialist</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="reviewForm">
                <!-- Star Rating -->
                <div class="star-rating">
                    <input type="radio" name="rating" id="star5" value="5">
                    <label for="star5" title="5 stars">★</label>
                    <input type="radio" name="rating" id="star4" value="4">
                    <label for="star4" title="4 stars">★</label>
                    <input type="radio" name="rating" id="star3" value="3">
                    <label for="star3" title="3 stars">★</label>
                    <input type="radio" name="rating" id="star2" value="2">
                    <label for="star2" title="2 stars">★</label>
                    <input type="radio" name="rating" id="star1" value="1">
                    <label for="star1" title="1 star">★</label>
                </div>

                <!-- Comment -->
                <div class="review-comment">
                    <label for="comment" style="font-weight:500; display:block; margin-bottom:8px;">Your Review</label>
                    <textarea name="comment" id="comment" placeholder="Describe your experience with this lawyer. What went well? What could be improved?"><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-submit-review" style="margin-top:20px;">Submit Review</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>