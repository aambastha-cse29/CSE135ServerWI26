#!/usr/bin/php
<?php
// This CGI must not emit any body or raw headers before calling header().
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax'
]);

session_start();

// Save POST data to session
foreach ($_POST as $key => $value) {
  $_SESSION[$key] = $value;
}

// Send redirect headers (no prior output)
header('Cache-Control: no-cache');
header('Location: /cgi-bin/hw2/php/state-view-php.php', true, 302);
header('Content-Type: text/html; charset=utf-8');

// Fallback HTML for user-agents that don't follow Location header immediately
echo "<!DOCTYPE html>\n"
echo "<html>\n"
echo "<head>\n"
echo "<meta http-equiv=refresh content=0; url=/cgi-bin/hw2/php/state-view-php.php>\n";
echo "<title>Redirecting</title>\n"
echo "</head>\n"
echo "<body>\n";
echo "Redirecting To <a href=/cgi-bin/hw2/php/state-view-php.php>state-view-php.php</a>\n";
echo "</body>\n"
echo "</html>\n";

exit;

?>