#!/usr/bin/php
<?php   
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
        $_SESSION[$key] = htmlspecialchars($value);
    }

    header("Location: /state-view-php.cgi", true, 302);
    exit;
?>