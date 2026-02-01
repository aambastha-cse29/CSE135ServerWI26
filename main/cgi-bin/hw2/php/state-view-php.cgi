#!/usr/bin/php
<?php
    
    // Must match the save script's session path
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

    // Manually parse and set session ID from HTTP_COOKIE
    if (isset($_SERVER['HTTP_COOKIE'])) {
        error_log("VIEW SCRIPT - HTTP_COOKIE: " . $_SERVER['HTTP_COOKIE']);
        
        // Parse the cookie manually
        $cookies = array();
        $cookie_parts = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach ($cookie_parts as $cookie) {
            $cookie = trim($cookie);
            if (strpos($cookie, '=') !== false) {
                list($name, $value) = explode('=', $cookie, 2);
                $cookies[trim($name)] = trim($value);
            }
        }
        
        // Set the session ID from the cookie if it exists
        if (isset($cookies['PHPSESSID'])) {
            session_id($cookies['PHPSESSID']);
        }
    }

    session_start();
    
    if (!isset($_SESSION['Name']) || !isset($_SESSION['Favorite_CSE_Class']) || !isset($_SESSION['Graduation_Year'])) {
        error_log("VIEW SCRIPT - Name not set, redirecting");
        
        // CGI-style redirect header
        echo "Status: 302 Found\r\n";
        echo "Location: /state-collect-php.html\r\n";
        echo "\r\n";
        exit;
    }
    

    // Send CGI headers
    echo "Content-Type: text/html\r\n";
    echo "\r\n";

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>View Saved State - PHP</title>\n";
    echo "</head>\n";

    echo "<body>\n";
    echo "<h1 align='center'>View Saved State - PHP</h1><hr/>\n";
    echo "<p>This Page Displays The Data Saved In The Session By The State Save PHP CGI Script</p>\n";
    echo "<h2>Saved Data:</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>Name:</strong> " . $_SESSION['Name'] . "</li>\n";
    echo "<li><strong>Favorite CSE Class:</strong> " . $_SESSION['Favorite_CSE_Class'] . "</li>\n";
    echo "<li><strong>Graduation Year:</strong> " . $_SESSION['Graduation_Year'] . "</li>\n";
    echo "</ul>\n";

    echo "<form method='POST' action='/cgi-bin/hw2/php/state-clear-php.cgi'>\n";
    echo "<button type='submit'>Clear Saved State</button>\n";
    echo "</form>\n";

    echo "</body>\n";
    echo "</html>\n";
?>