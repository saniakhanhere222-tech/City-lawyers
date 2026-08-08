<?php
// ============================================================
// LAWYER - PAYMENT RECEIPTS
// ============================================================
// This page allows lawyers to view payment receipts uploaded by
// their customers for appointments.
// ============================================================

$page_title = 'Payment Receipts';
$page_layout = 'fluid';
$footer_css = 'dashboard';
require_once '../includes/config.php';

// ============================================================
// 1. Redirect if not logged in as lawyer
// ============================================================
if (!isset($_SESSION['lawyer_id']) || $_SESSION['user_type'] != 'lawyer') {
    header("Location: login.php");
    exit();
}

$user_type = 'lawyer';
$user_name = $_SESSION['lawyer_name'];
$dashboard_link = BASE_URL . 'lawyer/index.php';
$lawyer_id = $_SESSION['lawyer_id'];

// ============================================================
// 2. Handle Status Toggle (Verify/Reject)
// ============================================================
if (isset($_GET['verify']) && is_numeric($_GET['verify'])) {
    $payment_id = (int)$_GET['verify'];
    $updateStmt = $conn->prepare("
        UPDATE payments p
        JOIN appointments a ON p.appointment_id = a.id
        SET p.status = 'paid' 
        WHERE p.id = ? AND a.lawyer_id = ?
    ");
    if ($updateStmt->execute([$payment_id, $lawyer_id])) {
        $success = "Payment receipt verified successfully!";
    } else {
        $error = "Failed to verify receipt.";
    }
}

if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $payment_id = (int)$_GET['reject'];
    $updateStmt = $conn->prepare("
        UPDATE payments p
        JOIN appointments a ON p.appointment_id = a.id
        SET p.status = 'failed' 
        WHERE p.id = ? AND a.lawyer_id = ?
    ");
    if ($updateStmt->execute([$payment_id, $lawyer_id])) {
        $success = "Payment receipt rejected.";
    } else {
        $error = "Failed to reject receipt.";
    }
}

// ============================================================
// 3. Get Statistics
// ============================================================
$totalStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ?
");
$totalStmt->execute([$lawyer_id]);
$total_count = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];

$pendingStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ? AND p.status = 'pending'
");
$pendingStmt->execute([$lawyer_id]);
$pending_count = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];

$paidStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ? AND p.status = 'paid'
");
$paidStmt->execute([$lawyer_id]);
$paid_count = $paidStmt->fetch(PDO::FETCH_ASSOC)['count'];

$failedStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.lawyer_id = ? AND p.status = 'failed'
");
$failedStmt->execute([$lawyer_id]);
$failed_count = $failedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// ============================================================
// 4. Get Filters
// ============================================================
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$method_filter = isset($_GET['method']) ? $_GET['method'] : 'all';

// ============================================================
// 5. Build WHERE Clause
// ============================================================
$where = "a.lawyer_id = :lawyer_id";
$params = [':lawyer_id' => $lawyer_id];

if ($status_filter == 'pending') {
    $where .= " AND p.status = 'pending'";
} elseif ($status_filter == 'paid') {
    $where .= " AND p.status = 'paid'";
} elseif ($status_filter == 'failed') {
    $where .= " AND p.status = 'failed'";
}

if ($method_filter != 'all') {
    $where .= " AND p.payment_method = :method";
    $params[':method'] = $method_filter;
}

// ============================================================
// 6. Pagination
// ============================================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countSql = "
    SELECT COUNT(*) as total 
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE $where
";
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total_rows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "
    SELECT p.*, 
           c.name as customer_name, 
           c.email as customer_email,
           c.phone as customer_phone,
           a.appointment_date,
           a.appointment_time
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    JOIN appointments a ON p.appointment_id = a.id
    WHERE $where
    ORDER BY p.payment_date DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 7. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<style>
/* ========================================
   RECEIPT MANAGEMENT - CLEAN DESIGN
======================================== */

/* Stats Grid */
.receipt-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.receipt-stat-box {
    background: var(--white);
    border: 1px solid var(--border-color);
    padding: 20px;
    text-align: center;
    border-radius: 8px;
    transition: 0.3s;
}

.receipt-stat-box:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.receipt-stat-box h4 {
    font-size: 28px;
    margin: 0 0 2px;
    font-weight: 700;
}

.receipt-stat-box .stat-total {
    color: var(--primary-color);
}
.receipt-stat-box .stat-pending {
    color: #e65100;
}
.receipt-stat-box .stat-paid {
    color: #2e7d32;
}
.receipt-stat-box .stat-failed {
    color: #c62828;
}

.receipt-stat-box p {
    margin: 0;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text-dark);
}

/* ========================================
   RECEIPT CARD
======================================== */
.receipt-item {
    background: var(--surface-color);
    border: 1px solid var(--border-soft);
    padding: 20px 24px;
    margin-bottom: 14px;
    border-radius: 14px;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-lg);
}

.receipt-item:hover {
    border-color: #c4b8a8;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

/* Customer Row - Avatar + Name */
.receipt-customer-row {
    display: flex;
    align-items: center;
    gap: 14px;
}

.receipt-avatar {
    width: 40px;
    height: 40px;
    border-radius: 80%;
    background: var(--accent-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.receipt-customer-details {
    flex: 1;
}

.receipt-customer-name {
    font-weight: 600;
    color: var(--accent-color);
    font-size: 16px;
    margin: 0;
}

/* FIX: Email color - more visible */
.receipt-customer-email {
    font-size: 12px;
    color: var(--text-light);  
    margin: 0;
}

.receipt-customer-email i {
    color: var(--accent-color);
    margin-right: 4px;
}

/* Amount & Status Row */
.receipt-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border-light);
}

.receipt-amount {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
    font-family: 'Cormorant Garamond', serif;
}

.receipt-status-badge {
    padding: 4px 14px;
    font-size: 10px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}

.receipt-status-badge.pending {
    background: #fff3e0;
    color: #e65100;
}
.receipt-status-badge.paid {
    background: #dce9d7;
    color: #2e5b2e;
}
.receipt-status-badge.failed {
    background: #f0e0e0;
    color: #8b3a3a;
}
.receipt-status-badge.refunded {
    background: #e3e3e3;
    color: #6b6b6b;
}

/* Receipt Details Row */
.receipt-details-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 12px;
    color: var(--text-light);
    margin-top: 8px;
}

.receipt-details-row .detail-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.receipt-details-row .detail-item i {
    width: 14px;
    color: var(--text-light);
}

/* Actions Row */
.receipt-actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--border-light);
}

.btn-receipt-action {
    padding: 6px 16px;
    font-size: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.btn-receipt-view {
    background: var(--primary-color);
    color: white;
}
.btn-receipt-view:hover {
    background: var(--accent-color);
}

.btn-receipt-download {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-dark);
}
.btn-receipt-download:hover {
    background: #f0ebe4;
}

.btn-receipt-verify {
    background: #286d2c;
    color: white;
}
.btn-receipt-verify:hover {
    background: #1b5e20;
}

.btn-receipt-reject {
    background: #992929;
    color: white;
}
.btn-receipt-reject:hover {
    background: #b71c1c;
}

.btn-receipt-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.no-receipt-text {
    color: var(--text-light);
    font-size: 12px;
    font-style: italic;
}

/* ========================================
   VIEW APPOINTMENT LINK
======================================== */
.btn-view-appointment {
    background: var(--accent-color);
    color: white;
    padding: 6px 16px;
    font-size: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}
.btn-view-appointment:hover {
    background: var(--accent-color);
    color: white;
}

/* ========================================
   FILTERS
======================================== */
.filter-row-receipt {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.filter-row-receipt .filter-group {
    flex: 1;
    min-width: 120px;
}
.filter-row-receipt label {
    display: block;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 4px;
}
.filter-row-receipt select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    background: var(--white);
    font-size: 13px;
    border-radius: 6px;
}
.filter-row-receipt select:focus {
    outline: none;
    border-color: var(--primary-color);
}

/* ========================================
   RECEIPT MODAL
======================================== */
.receipt-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
}
.receipt-modal.active {
    display: flex;
}
.receipt-modal-content {
    max-width: 90%;
    max-height: 90%;
    background: white;
    padding: 20px;
    border-radius: 12px;
    position: relative;
}
.receipt-modal-content img {
    max-width: 100%;
    max-height: 80vh;
    display: block;
}
.receipt-modal-close {
    position: absolute;
    top: -40px;
    right: 0;
    background: none;
    border: none;
    color: white;
    font-size: 30px;
    cursor: pointer;
}

/* ========================================
   EMPTY STATE
======================================== */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 48px;
    color: var(--text-muted);
    margin-bottom: 15px;
}
.empty-state p {
    color: var(--text-muted);
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .receipt-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .receipt-item {
        padding: 16px 18px;
    }
    .receipt-customer-row {
        gap: 12px;
    }
    .receipt-avatar {
        width: 38px;
        height: 38px;
        font-size: 14px;
    }
    .receipt-meta-row {
        flex-direction: column;
        align-items: flex-start;
    }
    .filter-row-receipt {
        flex-direction: column;
    }
    .filter-row-receipt .filter-group {
        min-width: 100%;
    }
    .receipt-actions-row {
        flex-direction: column;
    }
    .receipt-actions-row .btn-receipt-action {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .receipt-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .receipt-stat-box {
        padding: 14px 12px;
    }
    .receipt-stat-box h4 {
        font-size: 22px;
    }
}
</style>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER CARD -->
        <div class="dashboard-card">
            <h2 class="dashboard-title">Payment Receipts</h2>
            <p class="dashboard-subtitle">View and manage payment receipts from customers</p>
        </div>

        <!-- MESSAGES -->
        <?php if (isset($success)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- STATISTICS -->
        <div class="receipt-stats-grid">
            <div class="receipt-stat-box">
                <h4 class="stat-total"><?php echo $total_count; ?></h4>
                <p>Total Receipts</p>
            </div>
            <div class="receipt-stat-box">
                <h4 class="stat-pending"><?php echo $pending_count; ?></h4>
                <p>Pending</p>
            </div>
            <div class="receipt-stat-box">
                <h4 class="stat-paid"><?php echo $paid_count; ?></h4>
                <p>Paid</p>
            </div>
            <div class="receipt-stat-box">
                <h4 class="stat-failed"><?php echo $failed_count; ?></h4>
                <p>Failed</p>
            </div>
        </div>

        <!-- FILTER TABS -->
        <div class="filter-tabs">
            <a href="?status=all<?php echo $method_filter != 'all' ? '&method=' . $method_filter : ''; ?>" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending<?php echo $method_filter != 'all' ? '&method=' . $method_filter : ''; ?>" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                Pending <?php if ($pending_count > 0): ?><span class="tab-badge"><?php echo $pending_count; ?></span><?php endif; ?>
            </a>
            <a href="?status=paid<?php echo $method_filter != 'all' ? '&method=' . $method_filter : ''; ?>" class="filter-tab <?php echo $status_filter == 'paid' ? 'active' : ''; ?>">
                Paid <?php if ($paid_count > 0): ?><span class="tab-badge"><?php echo $paid_count; ?></span><?php endif; ?>
            </a>
            <a href="?status=failed<?php echo $method_filter != 'all' ? '&method=' . $method_filter : ''; ?>" class="filter-tab <?php echo $status_filter == 'failed' ? 'active' : ''; ?>">
                Failed <?php if ($failed_count > 0): ?><span class="tab-badge"><?php echo $failed_count; ?></span><?php endif; ?>
            </a>
        </div>

        <!-- FILTER ROW -->
        <div class="dashboard-card" style="padding: 20px;">
            <form method="GET" action="">
                <div class="filter-row-receipt">
                    <div class="filter-group">
                        <label>Payment Method</label>
                        <select name="method">
                            <option value="all" <?php echo $method_filter == 'all' ? 'selected' : ''; ?>>All Methods</option>
                            <option value="cash" <?php echo $method_filter == 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="jazzcash" <?php echo $method_filter == 'jazzcash' ? 'selected' : ''; ?>>JazzCash</option>
                            <option value="easypaisa" <?php echo $method_filter == 'easypaisa' ? 'selected' : ''; ?>>EasyPaisa</option>
                            <option value="bank_transfer" <?php echo $method_filter == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 0 0 auto;">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                    <div class="filter-group" style="flex: 0 0 auto;">
                        <a href="receipts.php" class="reset-btn">Reset</a>
                    </div>
                </div>
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
            </form>
        </div>

        <!-- RECEIPTS LIST -->
        <div class="dashboard-card">
            <?php if (count($receipts) > 0): ?>
                <?php foreach ($receipts as $row): 
                    // Get initials
                    $initials = '';
                    $nameParts = explode(' ', $row['customer_name']);
                    foreach ($nameParts as $part) {
                        if (!empty($part)) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                    }
                    $initials = substr($initials, 0, 2);
                ?>
                    <div class="receipt-item">
                        <!-- Row 1: Customer -->
                        <div class="receipt-customer-row">
                            <div class="receipt-avatar">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                            <div class="receipt-customer-details">
                                <p class="receipt-customer-name">
                                    <?php echo htmlspecialchars($row['customer_name']); ?>
                                </p>
                                <!-- FIX: Email now visible with better color -->
                                <p class="receipt-customer-email">
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($row['customer_email']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Row 2: Amount + Status -->
                        <div class="receipt-meta-row">
                            <span class="receipt-amount">
                                <?php echo number_format($row['amount']); ?> PKR
                            </span>
                            <span class="receipt-status-badge <?php echo $row['status'] ?? 'pending'; ?>">
                                <?php echo ucfirst($row['status'] ?? 'pending'); ?>
                            </span>
                        </div>

                        <!-- Row 3: Details -->
                        <div class="receipt-details-row">
                            <span class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('d M Y', strtotime($row['appointment_date'])); ?>
                            </span>
                            <span class="detail-item">
                                <i class="fas fa-clock"></i>
                                <?php echo date('h:i A', strtotime($row['appointment_time'])); ?>
                            </span>
                            <span class="detail-item">
                                <i class="fas fa-credit-card"></i>
                                <?php echo ucfirst($row['payment_method']); ?>
                            </span>
                        </div>

                        <!-- Row 4: Actions -->
                        <div class="receipt-actions-row">
                            <?php if (!empty($row['receipt_image'])): ?>
                                <button class="btn-receipt-action btn-receipt-view" onclick="openReceiptModal('<?php echo BASE_URL; ?>uploads/receipts/<?php echo $row['receipt_image']; ?>')">
                                    <i class="fas fa-eye"></i> View Receipt
                                </button>
                                <a href="<?php echo BASE_URL; ?>uploads/receipts/<?php echo $row['receipt_image']; ?>" download class="btn-receipt-action btn-receipt-download">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                <span class="no-receipt-text">No receipt uploaded</span>
                            <?php endif; ?>

                            <?php if (($row['status'] == 'pending') && !empty($row['receipt_image'])): ?>
                                <a href="?verify=<?php echo $row['id']; ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $page; ?>" 
                                   class="btn-receipt-action btn-receipt-verify" 
                                   onclick="return confirm('Verify this payment receipt? This will confirm the payment.')">
                                    <i class="fas fa-check"></i> Verify
                                </a>
                                <a href="?reject=<?php echo $row['id']; ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $page; ?>" 
                                   class="btn-receipt-action btn-receipt-reject" 
                                   onclick="return confirm('Reject this payment receipt?')">
                                    <i class="fas fa-times"></i> Reject
                                </a>
                            <?php endif; ?>

                            <!-- NEW: View Appointment link for paid receipts -->
                            <?php if ($row['status'] == 'paid'): ?>
                                <a href="appointments.php?view=<?php echo $row['appointment_id']; ?>" 
                                   class="btn-view-appointment">
                                    <i class="fas fa-calendar-check"></i> View Appointment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrap">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $page - 1; ?>" class="page-link">← Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $page + 1; ?>" class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No receipts found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ============================================================
     RECEIPT MODAL
============================================================ -->
<div class="receipt-modal" id="receiptModal" onclick="closeReceiptModal(event)">
    <div class="receipt-modal-content">
        <button class="receipt-modal-close" onclick="closeReceiptModal(event)">×</button>
        <img id="receiptModalImage" src="" alt="Receipt">
    </div>
</div>

<!-- ============================================================
     JAVASCRIPT
============================================================ -->
<script>
function openReceiptModal(imageSrc) {
    document.getElementById('receiptModalImage').src = imageSrc;
    document.getElementById('receiptModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReceiptModal(event) {
    if (event.target === event.currentTarget || event.target.classList.contains('receipt-modal-close')) {
        document.getElementById('receiptModal').classList.remove('active');
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('receiptModal').classList.remove('active');
        document.body.style.overflow = '';
    }
});
</script>

<?php include '../includes/dashboard-footer.php'; ?>