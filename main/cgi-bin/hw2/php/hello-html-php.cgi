#!/usr/bin/php
<?php
    // Required CGI headers
    echo "Cache-Control: no-cache\n";
    echo "Content-Type: text/html\n\n";   // <-- TWO newlines

    // Now safe to output HTML
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Hello HTML World - PHP</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1 align='center'>Hello HTML World -- Greetings From Aman Ambastha</h1><hr/>\n";
    echo "<p>Hello World</p>\n";
    echo "<p>PHP_SAPI: " . php_sapi_name() . "</p>\n";
    echo "<p>This Page Was Generated With The PHP Programming Language</p>\n";

    $today = new DateTime();
    $IP = $_SERVER['HTTP_USER_AGENT'];

    echo "<p>Page Generated At (UTC): " . $today->format('Y-m-d H:i:s') . "</p>\n";
    echo "<p>Your IP Address Is: " . $IP . "</p>\n";
    echo "<p>Using The Common Gateway Interface (CGI) Protocol - CSE 135 Team Aman Ambastha</p>\n";
    echo "</body>\n";
    echo "</html>\n";
?>


