<?php
/**
 * Customer - My Appointments
 * 
 * Displays all appointments for the logged‑in customer,
 * allows cancelling (pending/confirmed) and deleting cancelled appointments.
 * For completed appointments, shows "Write Review" or "✔ Reviewed" badge.
 */
$page_title = 'My Appointments';
$page_layout= 'fluid'; //set in header.php 
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as customer
// ============================================================
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Set sidebar variables
$user_type = 'customer';
$user_name = $_SESSION['customer_name'];
$dashboard_link = BASE_URL . 'customer/index.php';

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// ============================================================
// 2. Handle Cancel (pending/confirmed) – deletes appointment and notifies lawyer
// ============================================================
if (isset($_GET['cancel'])) {
    $appointment_id = (int)$_GET['cancel'];

    // Get lawyer_id before deleting
    $lawyerStmt = $conn->prepare("SELECT lawyer_id FROM appointments WHERE id = ? AND customer_id = ?");
    $lawyerStmt->execute([$appointment_id, $customer_id]);
    $lawyer = $lawyerStmt->fetch(PDO::FETCH_ASSOC);
    if ($lawyer) {
        // Delete appointment
        $delStmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND customer_id = ?");
        $delStmt->execute([$appointment_id, $customer_id]);

        // Notify lawyer using new notification system
        addNotification(
            $lawyer['lawyer_id'],
            'lawyer',
            'cancelled',
            'Appointment Cancelled',
            "Customer $customer_name has cancelled their appointment.",
            'appointments.php',  // ✅ Removed 'lawyer/'
            'fa-times-circle'
        );
    }
    header("Location: my-appointments.php");
    exit();
}

// ============================================================
// 3. Handle Delete (only for cancelled appointments)
// ============================================================
if (isset($_GET['delete'])) {
    $appointment_id = (int)$_GET['delete'];
    $delStmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND customer_id = ? AND status = 'cancelled'");
    $delStmt->execute([$appointment_id, $customer_id]);
    header("Location: my-appointments.php");
    exit();
}

// ============================================================
// 4. Pagination setup
// ============================================================
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total appointments
$totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE customer_id = ?");
$totalStmt->execute([$customer_id]);
$total_rows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch appointments with lawyer details
$sql = "
    SELECT a.*, 
           l.name as lawyer_name, 
           l.specialization, 
           l.city, 
           l.fees, 
           l.profile_pic 
    FROM appointments a 
    JOIN lawyers l ON a.lawyer_id = l.id 
    WHERE a.customer_id = :customer_id 
    ORDER BY a.appointment_date DESC 
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 5. Pre-fetch review status for all appointments (to avoid N+1 queries)
// ============================================================
$appointment_ids = array_column($appointments, 'id');
$reviewed_map = [];

if (!empty($appointment_ids)) {
    $placeholders = implode(',', array_fill(0, count($appointment_ids), '?'));
    $reviewStmt = $conn->prepare("
        SELECT appointment_id 
        FROM reviews 
        WHERE appointment_id IN ($placeholders) AND customer_id = ?
    ");
    $params = array_merge($appointment_ids, [$customer_id]);
    $reviewStmt->execute($params);
    $reviewed = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
    $reviewed_map = array_flip($reviewed);
}

// ============================================================
// 6. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<!-- Custom CSS for appointment cards (page-specific) -->
<style>
/* Appointment card specific styles */
.appointment-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    padding: 22px;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}
.appointment-card:hover {
    border-color: #c4b8a8;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.appointment-card-inner {
    display: grid;
    grid-template-columns: 100px 1fr 140px;
    gap: 20px;
    align-items: flex-start;
}
.appointment-img-wrapper {
    width: 100px;
    height: 120px;
    background: #ebe5db;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.appointment-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.appointment-img-wrapper i {
    font-size: 40px;
    color: var(--primary-color);
}
.appointment-lawyer-name {
    font-size: 22px;
    color: var(--primary-color);
    margin-bottom: 4px;
    line-height: 1.2;
}
.appointment-lawyer-spec {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #8a8479;
}
.appointment-fee {
    text-align: right;
    min-width: 100px;
}
.fee-badge {
    background: var(--light-badge);
    color: var(--dark-badge);
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 4px;
}
.appointment-fee span {
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #8a8479;
    display: block;
    text-align: right;
}
.appointment-meta {
    margin-top: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    border-top: 2px solid var(--secondary-color);
    padding-top: 8px;
}
.appointment-meta span {
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #7d766b;
}
.appointment-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.action-btn {
    display: block;
    text-align: center;
    padding: 8px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-decoration: none;
    transition: 0.3s;
}
.btn-cancel {
    border: 1px solid #ddd4c7;
    background: transparent;
    color: var(--text-dark);
}
.btn-cancel:hover {
    background: #f2ece3;
}
.btn-edit {
    background: var(--primary-color);
    color: white;
}
.btn-edit:hover {
    background: #1f291f;
}
.btn-delete {
    background: #7f8c8d;
    color: white;
}
.btn-profile {
    border: 1px solid #ddd4c7;
    background: transparent;
    color: var(--text-dark);
}
.btn-profile:hover {
    background: #f2ece3;
}
/* Review button */
.btn-review {
    background: #c6a43f;
    color: white;
}
.btn-review:hover {
    background: #a8872e;
}
/* Reviewed badge */
.reviewed-badge {
    color: #2e5b2e;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-block;
    padding: 8px;
    text-align: center;
    background: #dce9d7;
    border: 1px solid #c5d4c5;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 48px;
    color: var(--text-light);
    margin-bottom: 15px;
}
.empty-state p {
    color: var(--text-light);
    margin-bottom: 20px;
}
.btn-find {
    background: var(--primary-color);
    color: white;
    padding: 12px 24px;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    display: inline-block;
    text-decoration: none;
}
.page-link-custom {
    border: 1px solid var(--border-color);
    background: transparent;
    color: var(--text-dark);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 8px 16px;
    margin: 0 4px;
    display: inline-block;
    text-decoration: none;
}
.page-link-custom:hover {
    background: var(--primary-color);
    color: white;
}
@media (max-width: 768px) {
    .appointment-card-inner {
        grid-template-columns: 1fr;
    }
    .appointment-img-wrapper {
        width: 100%;
        height: 200px;
    }
    .appointment-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }
    .appointment-actions .action-btn,
    .appointment-actions .reviewed-badge {
        flex: 1;
        min-width: 80px;
    }
    .appointment-fee {
        text-align: left;
    }
    .appointment-fee span {
        text-align: left;
    }
}
</style>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Header card -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">My Appointments</h2>
            <p class="dashboard-subtitle"><?php echo $total_rows; ?> total appointments found</p>
        </div>

        <?php if ($total_rows == 0): ?>
            <!-- Empty state -->
            <div class="dashboard-card empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>You haven't booked any appointments yet.</p>
                <a href="search.php" class="btn-find">Find a Lawyer</a>
            </div>
        <?php else: ?>
            <!-- Appointment cards -->
            <?php foreach ($appointments as $row): ?>
                <div class="dashboard-card appointment-card">
                    <div class="appointment-card-inner">
                        <!-- Lawyer image -->
                        <div class="appointment-img-wrapper">
                            <?php if (!empty($row['profile_pic']) && file_exists("../uploads/lawyers/" . $row['profile_pic'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user-advocate"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Appointment details -->
                        <div class="appointment-details">
                            <div class="appointment-header-row">
                                <div>
                                    <h3 class="appointment-lawyer-name"><?php echo htmlspecialchars($row['lawyer_name']); ?></h3>
                                    <p class="appointment-lawyer-spec"><?php echo htmlspecialchars($row['specialization']); ?> | <?php echo htmlspecialchars($row['city']); ?></p>
                                </div>
                                <div class="appointment-fee">
                                    <div class="fee-badge"><?php echo number_format($row['fees']); ?> PKR</div>
                                    <span>Consultation Fee</span>
                                </div>
                            </div>

                            <div class="appointment-meta">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($row['appointment_date'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($row['appointment_time'])); ?></span>
                                <span class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="appointment-actions">
                            <?php if ($row['status'] != 'cancelled' && $row['status'] != 'completed'): ?>
                                <!-- Pending / Confirmed: Cancel + Edit -->
                                <a href="?cancel=<?php echo $row['id']; ?>" class="action-btn btn-cancel" onclick="return confirm('⚠️ WARNING: This action will permanently delete your appointment and notify the lawyer. Cannot be undone. Cancel appointment?')">Cancel</a>
                                <a href="book-appointment.php?id=<?php echo $row['lawyer_id']; ?>&edit=<?php echo $row['id']; ?>" class="action-btn btn-edit">Edit</a>
                            
                            <?php elseif ($row['status'] == 'cancelled'): ?>
                                <!-- Cancelled: Delete only -->
                                <a href="?delete=<?php echo $row['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Permanently delete this cancelled appointment?')">Delete</a>
                            
                            <?php elseif ($row['status'] == 'completed'): ?>
                                <!-- Completed: Write Review OR Reviewed badge -->
                                <?php if (isset($reviewed_map[$row['id']])): ?>
                                    <span class="reviewed-badge">✔ Reviewed</span>
                                <?php else: ?>
                                    <a href="review.php?appointment_id=<?php echo $row['id']; ?>" class="action-btn btn-review">Write Review</a>
                                <?php endif; ?>
                            <?php endif; ?>

                            
                            
                            <!-- View Profile (always visible for all statuses) -->
                            <a href="lawyer-profile.php?id=<?php echo $row['lawyer_id']; ?>" class="action-btn btn-profile">View Profile →</a>

                            <!-- Chat button (visible for all statuses except cancelled) -->
                           <?php if ($row['status'] != 'cancelled'): ?>
                              <a href="chat.php?appointment_id=<?php echo $row['id']; ?>" class="action-btn btn-chat">
                                    <i class="fas fa-comment"></i>  <span class="chat-text">Chat</span></a>
<?php endif; ?>
                            
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrap">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link-custom">← Previous</a>
                <?php endif; ?>
                <span style="font-size: 12px; color: #8a8479; margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link-custom">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include '../includes/dashboard-footer.php'; ?>