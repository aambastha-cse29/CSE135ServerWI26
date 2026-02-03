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


$cookie = array();
$http_cookie = $_SERVER['HTTP_COOKIE'];
if ($http_cookie) {
     $cookie_parts = explode(';', $http_cookie);
     foreach ($cookie_parts as $cookie) {
        $cookie = trim($cookie);
        if (strpos($cookie, '=') !== false) {
               list($name, $value) = explode('=', $cookie, 2);
               $cookies[trim($name)] = trim($value);
           }
     }
}

// Parse POST data manually
$_POST = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_post = file_get_contents('php://stdin');
    parse_str($raw_post, $_POST);
}

if (empty($_POST)) {
    if (isset($cookies['PHPSESSID']) && !empty($cookies['PHPSESSID'])) {
        // Has valid cookie but no POST data → redirect to view
        echo "Status: 302 Found\r\n";
        echo "Location: /cgi-bin/hw2/php/state-view-php.cgi\r\n";
        echo "\r\n";
    } 
    
    else {
        // No cookie and no POST data → redirect to form
        echo "Status: 302 Found\r\n";
        echo "Location: /state-collect-php.html\r\n";
        echo "\r\n";
    }
    exit;
}


if (isset($cookies['PHPSESSID']) && !empty($cookies['PHPSESSID'])) {
   session_id($cookies['PHPSESSID']);
}

session_start();

// Save POST data
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// Manually send Set-Cookie header for CGI
$session_name = session_name();
$session_id = session_id();
$cookie_params = session_get_cookie_params();

// Build the Set-Cookie header 
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
echo "<html>\n";
echo "<head><title>Data Saved</title></head>\n";
echo "<body>\n";
echo "  <h2>Data saved successfully!</h2>\n";
echo "  <p><a href='/cgi-bin/hw2/php/state-view-php.cgi'>View Saved Data</a></p>\n";
echo "</body>\n";
echo "</html>\n";
?>