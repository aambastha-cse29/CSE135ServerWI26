#!/usr/bin/php
<?php
// This CGI must not emit any body or raw headers before calling header().
 echo "Cache-Control: no-cache\n";
 echo "Content-Type: text/html\n\n";   // <-- TWO newlines

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

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<title>Save Data</title>\n";
echo "</head>\n";
echo "<body>\n";
echo "Access Data Here: To <a href=\"/cgi-bin/hw2/php/state-view-php.php\">state-view-php.php</a>\n";
echo "</body>\n";
echo "</html>\n";

?>