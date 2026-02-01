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

// Print the raw POST contents for debugging
header('Content-Type: text/plain; charset=utf-8');
var_dump($_POST);

// Optionally persist POST data into the session
foreach ($_POST as $key => $value) {
  $_SESSION[$key] = $value;
}

exit;
?>      