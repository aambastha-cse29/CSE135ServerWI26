#!/usr/bin/php
<?php
// CRITICAL: Set session path BEFORE session_set_cookie_params
$session_path = '/tmp/php_sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0700, true);
}
session_save_path($session_path);

session_set_cookie_params([
  'lifetime' => 86400,
  'path' => '/',
  'domain' => '',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

session_start();

// Debug: Log session info
error_log("SAVE SCRIPT - Session ID: " . session_id());
error_log("SAVE SCRIPT - Session path: " . session_save_path());

// Emit CGI headers
echo "Cache-Control: no-cache\r\n";
echo "Content-Type: text/html\r\n\r\n";

// Save POST data
foreach ($_POST as $key => $value) {
  $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

error_log("SAVE SCRIPT - Session data: " . print_r($_SESSION, true));

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Save Data</title></head><body>\n";
echo "<p>Data saved to session!</p>\n";
echo '<p>Access Data Here: <a href="/cgi-bin/hw2/php/state-view-php.php">state-view-php.php</a></p>';
echo "</body></html>\n";
?>
