#!/usr/bin/php
<?php
// 1) Set a custom session save path with proper permissions
$session_path = '/tmp/php_sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0700, true);
}
session_save_path($session_path);

// 2) Configure session cookie params BEFORE session_start
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

// 3) Start session BEFORE any output
session_start();

// 4) Now emit CGI headers (session_start already sent Set-Cookie header)
echo "Cache-Control: no-cache\r\n";
echo "Content-Type: text/html\r\n\r\n";

// 5) Save POST data to session
foreach ($_POST as $key => $value) {
  $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 6) Body
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Save Data</title></head><body>\n";
echo "<p>Data saved to session!</p>\n";
echo '<p>Access Data Here: <a href="/cgi-bin/hw2/php/state-view-php.php">state-view-php.php</a></p>';
echo "</body></html>\n";
?>
