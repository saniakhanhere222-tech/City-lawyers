<?php
// ============================================================
// LAWYER - CHAT WITH CUSTOMER
// ============================================================
// This page enables real-time communication with customers:
//
// 1. Features: Message history, auto-refresh (3 sec), read receipts
// 2. Authentication: Lawyer only, appointment ownership required
// 3. Message Flow: Booking message seeded, lawyer messages on right
// 4. Header: Shows customer name, date, lawyer's profile picture
//
// AJAX Endpoints: send-messages.php, get-messages.php, mark-messages-read.php
//
// Features:
// - Authentication required (lawyer only)
// - Appointment ownership verification
// - Real-time messaging with polling
// - Read status tracking
// - Enter key support
//
// Database Tables: appointments, messages, customers, lawyers
//
// Related Files:
// - ../includes/config.php - Database connection
// - ../includes/dashboard-sidebar.php - Navigation
// - ../includes/functions.php - Helper functions
// - ../includes/send-messages.php - AJAX endpoint
// - ../includes/get-messages.php - AJAX endpoint
// - assets/css/chat.css - Chat styling
// ============================================================
$page_title = 'Chat';
$page_layout= 'fluid'; //set in header.php 
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
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
$user_id = $_SESSION['lawyer_id'];
$user_name = $_SESSION['lawyer_name'];
$dashboard_link = BASE_URL . 'lawyer/index.php';

// ============================================================
// 2. Get appointment ID from URL
// ============================================================
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit();
}

// ============================================================
// 3. Fetch appointment details (verify it belongs to this lawyer)
// ============================================================
$apptStmt = $conn->prepare("
    SELECT a.*, 
           c.id as customer_id, 
           c.name as customer_name,
           l.id as lawyer_id,
           l.name as lawyer_name,
           l.profile_pic
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN lawyers l ON a.lawyer_id = l.id
    WHERE a.id = ? AND a.lawyer_id = ?
");
$apptStmt->execute([$appointment_id, $user_id]);
$appointment = $apptStmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

$customer_id = $appointment['customer_id'];
$customer_name = $appointment['customer_name'];

// ============================================================
// 4. Seed the chat with booking_message (if no messages exist yet)
// ============================================================
$checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE appointment_id = ?");
$checkStmt->execute([$appointment_id]);
$msgCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($msgCount == 0 && !empty($appointment['booking_message'])) {
    // Insert the booking message as the first chat message
    // The sender is the customer (since they sent it when booking)
    $seedStmt = $conn->prepare("
        INSERT INTO messages (appointment_id, sender_id, sender_type, receiver_id, receiver_type, message, is_read, created_at)
        VALUES (?, ?, 'customer', ?, 'lawyer', ?, 1, NOW())
    ");
    $seedStmt->execute([
        $appointment_id,
        $customer_id,
        $user_id,
        $appointment['booking_message']
    ]);
}

// ============================================================
// 5. Mark all messages as read (when chat is opened)
// ============================================================
markMessagesAsRead($appointment_id, $user_id, 'lawyer');

// ============================================================
// 6. Fetch all messages for this appointment
// ============================================================
$messages = getChatMessages($appointment_id);

// ============================================================
// 7. Include header
// ============================================================
include '../includes/header.php';
?>

<!-- CSS: dashboard + sidebar + chat styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/chat.css">



<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/dashboard-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <div class="dashboard-card" style="padding:0; overflow:hidden;">

            <!-- Chat Container -->
            <div class="chat-container">

                <!-- Chat Header -->
                <div class="chat-header">
                    <?php if (!empty($appointment['profile_pic']) && file_exists("../uploads/lawyers/" . $appointment['profile_pic'])): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/lawyers/<?php echo htmlspecialchars($appointment['profile_pic']); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-advocate fa-2x" style="color: var(--text-light);"></i>
                    <?php endif; ?>
                    <div>
                        <h5><?php echo htmlspecialchars($customer_name); ?></h5>
                        <span class="status">Customer</span>
                    </div>
                    <div style="margin-left: auto; font-size:12px; color:var(--text-light);">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                            $is_sent = ($msg['sender_id'] == $user_id && $msg['sender_type'] == 'lawyer');
                            $class = $is_sent ? 'sent' : 'received';
                            ?>
                            <div class="message <?php echo $class; ?>">
                                <div class="bubble"><?php echo htmlspecialchars($msg['message']); ?></div>
                                <div class="time">
                                    <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                    <?php if ($is_sent && $msg['is_read'] == 1): ?>
                                        <span class="read-status">✓ Read</span>
                                    <?php elseif ($is_sent): ?>
                                        <span class="read-status">✓ Sent</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-chat">
                            <i class="fas fa-comment-dots"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Input -->
                <div class="chat-input">
                    <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off">
                    <button id="sendBtn"><i class="fas fa-paper-plane"></i> Send</button>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
// ============================================================
// CHAT JAVASCRIPT – Simplified
// ============================================================

// 1. Variables
const appointmentId = <?php echo $appointment_id; ?>;
const userId = <?php echo $user_id; ?>;
const customerId = <?php echo $customer_id; ?>;
const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');

// 2. Scroll to bottom
function scrollBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
scrollBottom();

// 3. Send message
function sendMessage() {
     console.log("Send button clicked");

    let message = messageInput.value.trim();
    if (message == "") return;

    fetch("../includes/send-messages.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "appointment_id=" + appointmentId +
              "&receiver_id=" + customerId +
              "&receiver_type=customer" +
              "&message=" + encodeURIComponent(message)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            messageInput.value = "";
            loadMessages();
        }
    });
}

// 4. Load messages
function loadMessages() {
    fetch("../includes/get-messages.php?appointment_id=" + appointmentId)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            displayMessages(data.messages);
        }
    });
}

// 5. Display messages
function displayMessages(messages) {
    let html = "";
    
    if (messages.length === 0) {
        html = `
            <div class="empty-chat">
                <i class="fas fa-comment-dots"></i>
                <p>No messages yet. Start the conversation!</p>
            </div>
        `;
    } else {
        messages.forEach(function(msg) {
            let side = (msg.sender_id == userId && msg.sender_type == 'lawyer') ? 'sent' : 'received';
            
            let time = new Date(msg.created_at).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
            
            let readStatus = '';
            if (side == 'sent' && msg.is_read == 1) {
                readStatus = '<span class="read-status">✓ Read</span>';
            } else if (side == 'sent') {
                readStatus = '<span class="read-status">✓ Sent</span>';
            }
            
            html += `
                <div class="message ${side}">
                    <div class="bubble">${msg.message}</div>
                    <div class="time">${time} ${readStatus}</div>
                </div>
            `;
        });
    }
    
    chatMessages.innerHTML = html;
    if (document.activeElement === messageInput) {
    scrollBottom();
   }
}

// 6. Mark messages as read
function markRead() {
    fetch("../includes/mark-messages-read.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "appointment_id=" + appointmentId
    });
 };

// 7. Events
sendBtn.onclick = sendMessage;
messageInput.onkeypress = function(e) {
    if (e.key == "Enter") {
        sendMessage();
    }
};

// 8. Auto-refresh
loadMessages();
markRead();
setInterval(loadMessages, 3000);
</script>

<?php include '../includes/dashboard-footer.php'; ?>