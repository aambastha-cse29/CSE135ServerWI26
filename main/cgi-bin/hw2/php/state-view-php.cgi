#!/usr/bin/php
<?php
   echo  "Cache-Control: no-cache\n";
   echo  "Content-Type: text/html\n\n";

   session_set_cookie_params([
      'lifetime' => 86400,
      'path' => '/',
      'domain' => '',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);

    session_start();
    
    /*
    if (!isset($_SESSION['Name'])) {
        header('Location: /state-collect-php.html', true, 302);
        exit;
    }
    **/

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
    echo "<li><strong>Favorite CSE Class:</strong> " . $_SESSION['Favorite CSE Class'] . "</li>\n";
    echo "<li><strong>Graduation Year:</strong> " . $_SESSION['Graduation Year'] . "</li>\n";
    echo "</ul>\n";


    echo "<form method='POST' action='/cgi-bin/hw2/state/state-clear-php.cgi'>\n";
    echo "<button type='submit'>Clear Saved State</button>\n";
    echo "</form>\n";

    echo "</body>\n";
    echo "</html>\n";
?>