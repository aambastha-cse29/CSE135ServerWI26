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
    var_dump($_POST)
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = $value;
    }

    var_dump($_SESSION);
    exit;


   // header("Location: /cgi-bin/hw2/php/state-view-php.php", true, 302);
    exit;
?>