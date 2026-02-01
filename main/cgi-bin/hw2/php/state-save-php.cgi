#!/usr/bin/php
<?php   
  
  echo "Cache-Control: no-cache\n";
  echo "Content-Type: text/html\n\n";
  
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


  header("Location: /cgi-bin/hw2/php/state-view-php.php", true, 302);

  exit;
?>