<?php
// ===============================================================================
// FUNCTIONS.PHP – Reusable helper functions includes functions for notificatons and chat module functions 
// ================================================================================

// ============================================================
// NOTIFICATION FUNCTIONS 
// ============================================================

/**
 * Insert a new notification for a user
 * 
 * @param int    $user_id   ID of the recipient
 * @param string $user_type Role of the recipient ('customer', 'lawyer', 'admin')
 * @param string $type      Notification type (e.g., 'confirmed', 'cancelled', 'new_request')
 * @param string $title     Short title
 * @param string $message   Full message
 * @param string $link      URL to redirect when clicked (optional)
 * @param string $icon      Font Awesome icon class (optional)
 * @return bool True on success, false on failure
 */
function addNotification($user_id, $user_type, $type, $title, $message, $link = null, $icon = null)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, user_type, type, title, message, link, icon, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        return $stmt->execute([$user_id, $user_type, $type, $title, $message, $link, $icon]);
    } catch (PDOException $e) {
        // Log error silently (optional)
        return false;
    }
}

/**
 * Get unread notification count for a user
 * 
 * @param int    $user_id   ID of the user
 * @param string $user_type Role of the user
 * @return int Number of unread notifications
 */
function getUnreadCount($user_id, $user_type)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND user_type = ? AND is_read = 0
        ");
        $stmt->execute([$user_id, $user_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get latest notifications for the dropdown (bell icon)
 * 
 * @param int    $user_id   ID of the user
 * @param string $user_type Role of the user
 * @param int    $limit     Maximum number of notifications to fetch (default: 10)
 * @return array List of notifications
 */
function getLatestNotifications($user_id, $user_type, $limit = 10)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND user_type = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $user_type, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Mark a single notification as read
 * 
 * @param int $notification_id ID of the notification
 * @return bool True on success
 */
function markNotificationRead($notification_id)
{
    global $conn;

    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int    $user_id   ID of the user
 * @param string $user_type Role of the user
 * @return bool True on success
 */
function markAllRead($user_id, $user_type)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            UPDATE notifications SET is_read = 1 
            WHERE user_id = ? AND user_type = ? AND is_read = 0
        ");
        return $stmt->execute([$user_id, $user_type]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get paginated notifications with filtering
 * 
 * @param int    $user_id   ID of the user
 * @param string $user_type Role of the user
 * @param string $filter    Notification type filter ('all', 'unread', 'read', or specific type like 'confirmed')
 * @param int    $page      Current page number
 * @param int    $perPage   Items per page (default: 15)
 * @return array Associative array with 'notifications' list and 'total' count
 */
function getNotifications($user_id, $user_type, $filter = 'all', $page = 1, $perPage = 15)
{
    global $conn;

    $offset = ($page - 1) * $perPage;
    $conditions = ['user_id = ?', 'user_type = ?'];
    $params = [$user_id, $user_type];

    // Apply filter
    if ($filter === 'unread') {
        $conditions[] = 'is_read = 0';
    } elseif ($filter === 'read') {
        $conditions[] = 'is_read = 1';
    } elseif ($filter !== 'all') {
        // Filter by specific type (e.g., 'confirmed', 'cancelled', etc.)
        $conditions[] = 'type = ?';
        $params[] = $filter;
    }

    $whereClause = implode(' AND ', $conditions);
    $paramsCount = count($params);

    try {
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM notifications WHERE $whereClause";
        $countStmt = $conn->prepare($countSql);
        // Bind all parameters dynamically
        for ($i = 0; $i < $paramsCount; $i++) {
            $countStmt->bindValue($i + 1, $params[$i]);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $sql = "SELECT * FROM notifications WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        // Bind the first n parameters
        for ($i = 0; $i < $paramsCount; $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        // Bind limit and offset
        $stmt->bindValue($paramsCount + 1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramsCount + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'notifications' => $notifications,
            'total' => (int)$total,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage)
        ];
    } catch (PDOException $e) {
        return [
            'notifications' => [],
            'total' => 0,
            'current_page' => 1,
            'total_pages' => 1
        ];
    }
}

/**
 * Get all available notification types for the current user (for tabs)
 * 
 * @param int    $user_id   ID of the user
 * @param string $user_type Role of the user
 * @return array List of unique notification types
 */
function getNotificationTypes($user_id, $user_type)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT type FROM notifications 
            WHERE user_id = ? AND user_type = ? 
            ORDER BY type
        ");
        $stmt->execute([$user_id, $user_type]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get icon class for notification type
 * 
 * @param string $type Notification type
 * @return string Font Awesome icon class
 */
function getNotificationIcon($type)
{
    $icons = [
        'new_request' => 'fa-clock',
        'confirmed' => 'fa-check-circle',
        'completed' => 'fa-check-double',
        'cancelled' => 'fa-times-circle',
        'rescheduled' => 'fa-calendar-alt',
        'approved' => 'fa-check-circle',
        'rejected' => 'fa-times-circle',
        'new_lawyer' => 'fa-user-plus',
        'review_request' => 'fa-star',
        'reminder' => 'fa-bell',
    ];
    return $icons[$type] ?? 'fa-bell';
}

/**
 * Get CSS class for notification type (for visual color coding)
 * 
 * @param string $type Notification type
 * @return string CSS class
 */
function getNotificationTypeClass($type)
{
    $classes = [
        'new_request' => 'pending',
        'confirmed' => 'confirmed',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        'rescheduled' => 'warning',
        'approved' => 'confirmed',
        'rejected' => 'cancelled',
        'new_lawyer' => 'info',
        'review_request' => 'info',
        'reminder' => 'warning',
    ];
    return $classes[$type] ?? '';
}

/**
 * Format time ago (e.g., "2 hours ago")
 * 
 * @param string $timestamp MySQL datetime
 * @return string Human-readable time difference
 */
function timeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', $time);
}  

// ============================================================
// CHAT FUNCTIONS – Simplified
// ============================================================

/**
 * Get all messages for an appointment
 */
function getChatMessages($appointment_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM messages
        WHERE appointment_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$appointment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Send a new message
 */
function sendMessage($appointment_id, $sender_id, $sender_type, $receiver_id, $receiver_type, $message) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO messages (appointment_id, sender_id, sender_type, receiver_id, receiver_type, message, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    return $stmt->execute([$appointment_id, $sender_id, $sender_type, $receiver_id, $receiver_type, $message]);
}

/**
 * Mark messages as read for a specific appointment and receiver
 */
function markMessagesAsRead($appointment_id, $receiver_id, $receiver_type) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE appointment_id = ? 
        AND receiver_id = ? 
        AND receiver_type = ? 
        AND is_read = 0
    ");
    return $stmt->execute([$appointment_id, $receiver_id, $receiver_type]); 
}

/**
 * Get unread message count for a user (for notification badge)
 */
function getUnreadMessageCount($user_id, $user_type) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE receiver_id = ? 
        AND receiver_type = ? 
        AND is_read = 0
    ");
    $stmt->execute([$user_id, $user_type]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 * Get unread message count per appointment (for chat button badges)
 */
function getUnreadMessagesPerAppointment($user_id, $user_type) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT appointment_id, COUNT(*) as count 
        FROM messages 
        WHERE receiver_id = ? 
        AND receiver_type = ? 
        AND is_read = 0
        GROUP BY appointment_id
    ");
    $stmt->execute([$user_id, $user_type]);
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['appointment_id']] = $row['count'];
    }
    return $result;
}
?>