<?php
// auth.php
// Handles login and logout actions.
 
session_start();
 
// --------------------
// DB CONNECTION
// --------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'cse135');
define('DB_USER', 'cse135user');
define('DB_PASS', 'MySQLAman123CSE135!');
 
// Session duration: 30 minutes sliding window
define('SESSION_DURATION', 60 * 30);
 
$action = $_POST['action'] ?? null;


// Authentication Logic
 
if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
 
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['logged_in']  = true;
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']  = $user['role'];
            $_SESSION['sections']   = json_decode($user['sections'], true) ?? [];
            $_SESSION['expires_at'] = time() + SESSION_DURATION;
            header("Location: /dashboard");
            exit;
        } else {
            header("Location: /login?error=invalid");
            exit;
        }
 
    } catch (PDOException $e) {
        header("Location: /login?error=invalid");
        exit;
    }
}
 
elseif ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header("Location: /login?status=logged_out");
    exit;
}
 
else {
    header("Location: /login");
    exit;
}