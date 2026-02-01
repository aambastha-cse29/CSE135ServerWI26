#!/usr/bin/php
<?php
// Set session path
$session_path = '/tmp/php_sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0700, true);
}
session_save_path($session_path);

// Configure session cookie
session_set_cookie_params([
  'lifetime' => 86400,
  'path' => '/',
  'domain' => '',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

session_start();

// CGI-specific: Parse POST data manually
$_POST = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw POST data
    $raw_post = file_get_contents('php://stdin');
    error_log("SAVE SCRIPT - Raw POST: " . $raw_post);
    
    // Parse URL-encoded data
    parse_str($raw_post, $_POST);
}

// Debug logs
error_log("SAVE SCRIPT - Session ID: " . session_id());
error_log("SAVE SCRIPT - POST data: " . print_r($_POST, true));
error_log("SAVE SCRIPT - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("SAVE SCRIPT - CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

// Save POST data
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    error_log("SAVE SCRIPT - Session after save: " . print_r($_SESSION, true));
} else {
    error_log("SAVE SCRIPT - WARNING: POST is empty!");
}

// Send headers
echo "Content-Type: text/html\n\n";

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Save Data</title></head><body>\n";
echo "<p>Data saved to session!</p>\n";
echo "<p>Session ID: " . session_id() . "</p>\n";
echo "<p>POST data received: " . (empty($_POST) ? "NONE" : count($_POST) . " fields") . "</p>\n";
echo '<p>Access Data Here: <a href="/cgi-bin/hw2/php/state-view-php.php">state-view-php.php</a></p>';
echo "</body></html>\n";
?>