#!/usr/bin/php
<?php
// 1) Configure session cookie params BEFORE session_start AND before any output
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  // 'domain' => 'cse135wi2026.site', // optional; usually omit to use host-only cookie
  'secure' => true,      // requires https
  'httponly' => true,
  'samesite' => 'Lax'
]);

// 2) Start / resume session BEFORE any output
session_start();

// 3) Now it’s safe to emit CGI headers
echo "Cache-Control: no-cache\r\n";
echo "Content-Type: text/html\r\n\r\n";

// 4) Save POST data to session
foreach ($_POST as $key => $value) {
  $_SESSION[$key] = $value;
}

// 5) Body
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Save Data</title></head><body>\n";
echo 'Access Data Here: <a href="/cgi-bin/hw2/php/state-view-php.php">state-view-php.php</a>';
echo "</body></html>\n";

?>
