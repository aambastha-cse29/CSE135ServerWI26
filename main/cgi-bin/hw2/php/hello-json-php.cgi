#!/usr/bin/php
<?php
   // Required CGI headers
    echo "Cache-Control: no-cache\n";
    echo "Content-Type: application/json\n\n";   // <-- TWO newlines

    // Now safe to output JSON
    $response = array(
        "GREETING" => "Hello JSON World -- Greetings From Aman Ambastha",
        "MESSAGE" => "This Page Was Generated With The PHP Programming Language",
        "DATE (UTC) " => gmdate('Y-m-d H:i:s'),
        "IP_ADDRESS" => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        "NOTE" => "Using The Common Gateway Interface (CGI) Protocol - CSE 135 Team Aman Ambastha"
    );

    echo json_encode($response, JSON_PRETTY_PRINT);
?>