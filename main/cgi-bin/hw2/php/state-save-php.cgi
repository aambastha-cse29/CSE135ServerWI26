#!/usr/bin/php
<?php
// Set session path
$session_path = '/tmp/php_sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0700, true);
}
session_save_path($session_path);

// Configure session cookie - make sure path is '/'
session_set_cookie_params([
  'lifetime' => 86400,
  'path' => '/',           // Cookie valid for entire site
  'domain' => '',          // Leave empty for current domain
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

session_start();

// Debug logs
error_log("SAVE SCRIPT - Session ID: " . session_id());
error_log("SAVE SCRIPT - POST data: " . print_r($_POST, true));
error_log("SAVE SCRIPT - Cookie params: " . print_r(session_get_cookie_params(), true));

// Save POST data
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    error_log("SAVE SCRIPT - Session after save: " . print_r($_SESSION, true));
} else {
    error_log("SAVE SCRIPT - WARNING: POST is empty!");
}

// IMPORTANT: Headers must come AFTER session_start() but session cookie is already sent
// Just send the content type
header("Content-Type: text/html");

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Save Data</title></head><body>\n";
echo "<p>Data saved to session!</p>\n";
echo "<p>Session ID: " . session_id() . "</p>\n";
echo '<p>Access Data Here: <a href="/cgi-bin/hw2/php/state-view-php.php">state-view-php.php</a></p>';
echo "</body></html>\n";
?>