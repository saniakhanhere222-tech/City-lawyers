<?php
/**
 * Logout Script
 * 
 * Redirects to the appropriate login page based on user type
 * before destroying the session.
 */
session_start();

// ============================================================
// 1. Store user type BEFORE destroying session
// ============================================================
$user_type = $_SESSION['user_type'] ?? null;

// ============================================================
// 2. Unset all session variables
// ============================================================
$_SESSION = [];

// ============================================================
// 3. Delete the session cookie from the browser
// ============================================================
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ============================================================
// 4. Destroy the session on the server
// ============================================================
session_unset();  // Clear all session variables
session_destroy();

// ============================================================
// 5. Redirect based on user type
// ============================================================
switch ($user_type) {
    case 'customer':
        header("Location: customer/login.php?logout=success");
        break;
    case 'lawyer':
        header("Location: lawyer/login.php?logout=success");
        break;
    case 'admin':
        header("Location: admin/login.php?logout=success");
        break;
    default:
        // Fallback to root login page
        header("Location: login.php?logout=success");
        break;
}
exit();
?>