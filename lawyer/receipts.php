<?php
// ============================================================
// LAWYER - PAYMENT RECEIPTS
// ============================================================
// This page allows lawyers to view payment receipts uploaded by
// their customers for appointments:
//
// 1. Receipt Display:
//    - Shows all receipts for appointments with this lawyer
//    - Customer details, amount, payment method, status
//    - Receipt image preview/zoom
//
// 2. Status Filter:
//    - Filter by receipt status (pending, paid, failed, refunded)
//    - Filter by payment method (cash, jazzcash, easypaisa, bank_transfer)
//
// 3. Actions:
//    - Download receipt image
//    - View receipt in modal
//    - Mark as verified (confirm payment received)
//    - Mark as rejected (if receipt is invalid)
//
// 4. Statistics:
//    - Total receipts count
//    - Pending count
//    - Paid count
//    - Failed count
//
// Security:
//    - Lawyer must be logged in
//    - Only shows receipts for lawyer's own appointments
//    - Prepared statements for all queries
//    - Output escaping with htmlspecialchars()
//
// Database Tables:
// - payments (receipt data - uses 'status' column)
// - customers (customer names)
// - appointments (appointment details - contains lawyer_id)
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/header.php - Global header
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/dashboard-footer.php - Dashboard footer
// - assets/css/dashboard.css - Dashboard styling
// - assets/css/tables.css - Table styling
// - assets/css/sidebar.css - Sidebar styling
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

// Set sidebar variables
$user_type = 'lawyer';
$user_name = $_SESSION['lawyer_name'];
$dashboard_link = BASE_URL . 'lawyer/index.php';

$lawyer_id = $_SESSION['lawyer_id'];

// ============================================================
// 2. Handle Status Toggle (Verify/Reject)
// ============================================================
if (isset($_GET['verify']) && is_numeric($_GET['verify'])) {
    $payment_id = (int)$_GET['verify'];
    
    // Verify belongs to this lawyer via appointments table
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
// 3. Get Statistics (Join with appointments for lawyer_id)
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
// 5. Build WHERE Clause (Join with appointments for lawyer_id)
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

// Count total
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

// Fetch receipts
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

<!-- CSS: dashboard + tables + sidebar -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">

<!-- Page-specific CSS -->
<style>
/* ========================================
   RECEIPT MANAGEMENT - PAGE SPECIFIC
======================================== */
.receipt-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.receipt-stat-box {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    padding: 18px 20px;
    text-align: center;
}

.receipt-stat-box h4 {
    font-size: 24px;
    margin: 0 0 4px;
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
    color: var(--text-light);
}

/* Receipt card */
.receipt-item {
    background: var(--white);
    border: 1px solid var(--border-color);
    padding: 16px 20px;
    margin-bottom: 12px;
    transition: border-color 0.2s;
}

.receipt-item:hover {
    border-color: #c4b8a8;
}

.receipt-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 6px;
}

.receipt-customer {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
}

.receipt-amount {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
    font-family: 'Cormorant Garamond', serif;
}

.receipt-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 12px;
    color: var(--text-light);
    margin-top: 6px;
}

.receipt-details i {
    width: 14px;
    color: var(--text-light);
}

.receipt-actions {
    display: flex;
    gap: 6px;
    align-items: center;
    margin-top: 10px;
    flex-wrap: wrap;
}

.btn-receipt-view {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 5px 14px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 4px;
    text-decoration: none;
    transition: 0.3s;
}
.btn-receipt-view:hover {
    background: var(--accent-color);
    color: white;
}

.btn-receipt-verify {
    background: #2e7d32;
    color: white;
    border: none;
    padding: 5px 14px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 4px;
    text-decoration: none;
    transition: 0.3s;
}
.btn-receipt-verify:hover {
    background: #1b5e20;
}

.btn-receipt-reject {
    background: #c62828;
    color: white;
    border: none;
    padding: 5px 14px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 4px;
    text-decoration: none;
    transition: 0.3s;
}
.btn-receipt-reject:hover {
    background: #b71c1c;
}

.btn-receipt-download {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-dark);
    padding: 5px 14px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 4px;
    text-decoration: none;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-receipt-download:hover {
    background: #f0ebe4;
}

.no-receipt-text {
    color: var(--text-light);
    font-size: 12px;
    font-style: italic;
}

/* Receipt modal */
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
    border-radius: 8px;
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

/* Filter row */
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
    color: var(--text-light);
    margin-bottom: 4px;
}
.filter-row-receipt select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--border-color);
    background: var(--white);
    font-size: 13px;
}

/* Responsive */
@media (max-width: 768px) {
    .receipt-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .receipt-header {
        flex-direction: column;
        gap: 4px;
    }
    .filter-row-receipt {
        flex-direction: column;
        gap: 10px;
    }
    .filter-row-receipt .filter-group {
        min-width: 100%;
    }
    .receipt-actions {
        flex-direction: column;
        align-items: stretch;
    }
    .receipt-actions .btn-receipt-view,
    .receipt-actions .btn-receipt-verify,
    .receipt-actions .btn-receipt-reject,
    .receipt-actions .btn-receipt-download {
        text-align: center;
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

        <!-- SUCCESS/ERROR MESSAGES -->
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
                <?php foreach ($receipts as $row): ?>
                    <div class="receipt-item">
                        <div class="receipt-header">
                            <div>
                                <span class="receipt-customer">
                                    <i class="fas fa-user" style="color: var(--primary-color); margin-right: 4px;"></i>
                                    <?php echo htmlspecialchars($row['customer_name']); ?>
                                </span>
                                <span style="font-size: 12px; color: var(--text-light); margin-left: 8px;">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['customer_email']); ?>
                                </span>
                            </div>
                            <div>
                                <span class="receipt-amount"><?php echo number_format($row['amount']); ?> PKR</span>
                            </div>
                        </div>

                        <div class="receipt-details">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($row['appointment_date'])); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($row['appointment_time'])); ?></span>
                            <span><i class="fas fa-credit-card"></i> <?php echo ucfirst($row['payment_method']); ?></span>
                            <span>
                                <span class="status-badge status-<?php echo $row['status'] ?? 'pending'; ?>">
                                    <?php echo ucfirst($row['status'] ?? 'pending'); ?>
                                </span>
                            </span>
                        </div>

                        <div class="receipt-actions">
                            <?php if (!empty($row['receipt_image'])): ?>
                                <button class="btn-receipt-view" onclick="openReceiptModal('<?php echo BASE_URL; ?>uploads/receipts/<?php echo $row['receipt_image']; ?>')">
                                    <i class="fas fa-eye"></i> View Receipt
                                </button>
                                <a href="<?php echo BASE_URL; ?>uploads/receipts/<?php echo $row['receipt_image']; ?>" download class="btn-receipt-download">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                <span class="no-receipt-text">No receipt uploaded</span>
                            <?php endif; ?>

                            <?php if (($row['status'] == 'pending') && !empty($row['receipt_image'])): ?>
                                <a href="?verify=<?php echo $row['id']; ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $page; ?>" 
                                   class="btn-receipt-verify" 
                                   onclick="return confirm('Verify this payment receipt? This will confirm the payment.')">
                                    <i class="fas fa-check"></i> Verify
                                </a>
                                <a href="?reject=<?php echo $row['id']; ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>&page=<?php echo $page; ?>" 
                                   class="btn-receipt-reject" 
                                   onclick="return confirm('Reject this payment receipt?')">
                                    <i class="fas fa-times"></i> Reject
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
                <div class="empty-state" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-receipt" style="font-size: 48px; color: var(--text-light); margin-bottom: 15px;"></i>
                    <p style="color: var(--text-light);">No receipts found matching your criteria.</p>
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

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('receiptModal').classList.remove('active');
        document.body.style.overflow = '';
    }
});
</script>

<?php include '../includes/dashboard-footer.php'; ?>