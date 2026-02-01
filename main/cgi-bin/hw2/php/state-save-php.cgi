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
    $raw_post = file_get_contents('php://stdin');
    parse_str($raw_post, $_POST);
}

// Save POST data
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// Debug
error_log("SAVE SCRIPT - Session ID: " . session_id());
error_log("SAVE SCRIPT - Session data after save: " . print_r($_SESSION, true));

// CRITICAL: Manually send Set-Cookie header for CGI
$session_name = session_name();
$session_id = session_id();
$cookie_params = session_get_cookie_params();

// Build the Set-Cookie header manually
$cookie_header = "$session_name=$session_id";
$cookie_header .= "; Path=" . $cookie_params['path'];
$cookie_header .= "; Max-Age=" . $cookie_params['lifetime'];
if ($cookie_params['secure']) {
    $cookie_header .= "; Secure";
}
if ($cookie_params['httponly']) {
    $cookie_header .= "; HttpOnly";
}
if ($cookie_params['samesite']) {
    $cookie_header .= "; SameSite=" . $cookie_params['samesite'];
}

// Send CGI headers
echo "Set-Cookie: $cookie_header\r\n";
echo "Content-Type: text/html\r\n";
echo "\r\n";

// HTML output
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Save Data</title></head><body>\n";
echo "<p>Data saved to session!</p>\n";
echo "<p>Session ID: " . session_id() . "</p>\n";
echo '<p>Access Data Here: <a href="/cgi-bin/hw2/php/state-view-php.cgi">state-view-php.cgi</a></p>';
echo "</body></html>\n";
?>