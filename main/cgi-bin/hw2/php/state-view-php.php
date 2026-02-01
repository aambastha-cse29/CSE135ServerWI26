<?php
    // Set custom session save path for CGI compatibility
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
    
    if (!isset($_SESSION['Name'])) {
        header('Location: /state-collect-php.html', true, 302);
        exit;
    }
    
    // Helper function to safely output session data
    function safe_output($key) {
        return isset($_SESSION[$key]) ? htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8') : 'N/A';
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
    echo "<li><strong>Name:</strong> " . safe_output('Name') . "</li>\n";
    echo "<li><strong>Favorite CSE Class:</strong> " . safe_output('Favorite_CSE_Class') . "</li>\n";
    echo "<li><strong>Graduation Year:</strong> " . safe_output('Graduation_Year') . "</li>\n";
    echo "</ul>\n";

    echo "<form method='POST' action='/cgi-bin/hw2/php/state-clear-php.php'>\n";
    echo "<button type='submit'>Clear Saved State</button>\n";
    echo "</form>\n";

    echo "</body>\n";
    echo "</html>\n";
?>