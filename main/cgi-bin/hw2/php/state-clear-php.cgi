#!/usr/bin/php
<?php
    // Set session path to match other scripts
    $session_path = '/tmp/php_sessions';
    if (!is_dir($session_path)) {
        mkdir($session_path, 0700, true);
    }
    session_save_path($session_path);

    session_set_cookie_params([
      'lifetime' => 86400,
      'path' => '/',
      'domain' => '',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);

    // CRITICAL: Parse session ID from cookie (same as view script)
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = array();
        $cookie_parts = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach ($cookie_parts as $cookie) {
            $cookie = trim($cookie);
            if (strpos($cookie, '=') !== false) {
                list($name, $value) = explode('=', $cookie, 2);
                $cookies[trim($name)] = trim($value);
            }
        }
        
        if (isset($cookies['PHPSESSID'])) {
            session_id($cookies['PHPSESSID']);
        }
    }

    session_start();

    // Clear all session data
    $_SESSION = [];

    // Destroy the session
    session_destroy();

    // Build expired cookie header to delete the cookie
    $session_name = session_name();
    $cookie_params = session_get_cookie_params();
    
    $cookie_header = "$session_name=deleted";
    $cookie_header .= "; Path=" . $cookie_params['path'];
    $cookie_header .= "; Expires=Thu, 01 Jan 1970 00:00:00 GMT";  // Expired date
    if ($cookie_params['secure']) {
        $cookie_header .= "; Secure";
    }
    if ($cookie_params['httponly']) {
        $cookie_header .= "; HttpOnly";
    }
    if ($cookie_params['samesite']) {
        $cookie_header .= "; SameSite=" . $cookie_params['samesite'];
    }

    // Send CGI redirect with expired cookie
    echo "Set-Cookie: $cookie_header\r\n";
    echo "Status: 302 Found\r\n";
    echo "Location: /state-collect-php.html\r\n";
    echo "\r\n";
    exit;
?>