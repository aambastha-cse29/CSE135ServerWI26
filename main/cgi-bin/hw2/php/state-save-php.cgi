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

// Parse POST data manually
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
echo "<head>\n";
echo "  <meta charset='UTF-8'>\n";
echo "  <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "  <title>Data Saved - PHP CGI</title>\n";
echo "  <style>\n";
echo "    body {\n";
echo "      font-family: Arial, sans-serif;\n";
echo "      max-width: 600px;\n";
echo "      margin: 50px auto;\n";
echo "      padding: 20px;\n";
echo "      background-color: #f5f5f5;\n";
echo "    }\n";
echo "    .container {\n";
echo "      background-color: white;\n";
echo "      border-radius: 8px;\n";
echo "      padding: 30px;\n";
echo "      box-shadow: 0 2px 10px rgba(0,0,0,0.1);\n";
echo "    }\n";
echo "    .success {\n";
echo "      color: #28a745;\n";
echo "      font-size: 24px;\n";
echo "      margin-bottom: 20px;\n";
echo "    }\n";
echo "    .session-info {\n";
echo "      background-color: #f8f9fa;\n";
echo "      padding: 15px;\n";
echo "      border-left: 4px solid #007bff;\n";
echo "      margin: 20px 0;\n";
echo "      font-family: monospace;\n";
echo "      font-size: 12px;\n";
echo "      color: #666;\n";
echo "    }\n";
echo "    .btn {\n";
echo "      display: inline-block;\n";
echo "      background-color: #007bff;\n";
echo "      color: white;\n";
echo "      padding: 12px 24px;\n";
echo "      text-decoration: none;\n";
echo "      border-radius: 4px;\n";
echo "      transition: background-color 0.3s;\n";
echo "    }\n";
echo "    .btn:hover {\n";
echo "      background-color: #0056b3;\n";
echo "    }\n";
echo "  </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "  <div class='container'>\n";
echo "    <div class='success'>✓ Data Saved Successfully!</div>\n";
echo "    <p>Your information has been stored in the session.</p>\n";
echo "    <div class='session-info'>Session ID: " . session_id() . "</div>\n";
echo "    <a href='/cgi-bin/hw2/php/state-view-php.cgi' class='btn'>View Saved Data</a>\n";
echo "  </div>\n";
echo "</body>\n";
echo "</html>\n";
?>