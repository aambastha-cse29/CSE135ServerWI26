#!/usr/bin/php
<?php
   // Required CGI headers
    echo "Cache-Control: no-cache\n";
    echo "Content-Type: text/html\n\n";   // <-- TWO newlines

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Environment Variables - PHP</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1 align='center'>Environment Variables - PHP</h1><hr/>\n";
    echo "<p>This Page Displays The Environment Variables Available To This CGI Script</p>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>Variable Name</th><th>Value</th></tr>\n";

   foreach ($_SERVER as $key => $value) {
         echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>\n";
   }

   echo "</table>\n";
   echo "<p>Using The Common Gateway Interface (CGI) Protocol - CSE 135 Team Aman Ambastha</p>\n";
   echo "</body>\n";
   echo "</html>\n";
    
?>




