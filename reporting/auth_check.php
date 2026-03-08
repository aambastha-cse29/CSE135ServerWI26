<?php
// auth_check.php
// Include at the top of every protected page.
// Handles: unauthenticated access, session expiry, sliding window refresh.

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /login");
    exit;
}

// Check if session has expired
if (!isset($_SESSION['expires_at']) || time() > $_SESSION['expires_at']) {
    $_SESSION = [];
    session_destroy();
    header("Location: /login?status=expired");
    exit;
}

// Sliding window — refresh expiry on every page load
$_SESSION['expires_at'] = time() + (60 * 30);