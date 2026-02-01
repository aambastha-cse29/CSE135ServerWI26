#!/usr/bin/php
<?php
    // CRITICAL: Must match the save script's session path
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

    session_start();

    // Manually send Set-Cookie header for CGI
    $session_name = session_name();
    $session_id = session_id();
    $cookie_params = session_get_cookie_params();

    // Build the Set-Cookie header manually
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

    echo "Set-Cookie: $cookie_header\r\n";  // ADDED SEMICOLON
    echo "Content-Type: text/html\r\n";
    echo "\r\n"

    
    if (!isset($_SESSION['Name'])) {
        // CGI-style redirect header
        echo "Status: 302 Found\r\n";
        echo "Location: /state-collect-php.html\r\n";
        echo "\r\n";
        exit;
    }

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