<?php
// auth.php
// Handles login and logout actions.

session_start();

// --------------------
// CREDENTIALS
// --------------------
define('AUTH_USER', 'cse135aman');
define('AUTH_PASS', 'analyticsProjCSE135');

// Session duration: 30 minutes sliding window
define('SESSION_DURATION', 60 * 30);

$action = $_POST['action'] ?? null;

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === AUTH_USER && $password === AUTH_PASS) {
        session_regenerate_id(true); // prevent session fixation
        $_SESSION['logged_in']  = true;
        $_SESSION['username']   = $username;
        $_SESSION['expires_at'] = time() + SESSION_DURATION;
        header("Location: /dashboard.php");
        exit;
    } else {
        header("Location: /login.php?error=invalid");
        exit;
    }
}

elseif ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header("Location: /login.php?status=logged_out");
    exit;
}

else {
    header("Location: /login.php");
    exit;
}