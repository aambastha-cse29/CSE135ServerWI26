#!/usr/bin/php
<?php

    echo "Cache-Control: no-cache\n";
    echo "Content-Type: text/html\n\n";

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Echo - PHP</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1 align='center'>Echo - PHP</h1><hr/>\n";
    echo "<p>This Page Echoes Back The Data Sent To This CGI Script</p>\n";   

    // Print Key Headers
    echo "<h2>Key Headers:</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>REQUEST_ METHOD:</strong> " . htmlspecialchars($_SERVER['REQUEST_METHOD']) . "</li>\n";
    echo "<li><strong>IP_ADDRESS:</strong> " . htmlspecialchars($_SERVER['REMOTE_ADDR']) . "</li>\n";
    echo "<li><strong>USER_AGENT:</strong> " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "</li>\n";
    echo "<li><strong>HOST:</strong> " . htmlspecialchars($_SERVER['HTTP_HOST']) . "</li>\n";
    echo "</ul>\n";

    // Print Date and Time
    echo "<h2>Date and Time:</h2>\n";
    $today = new DateTime();
    echo "<p>Page Generated At (UTC): " . $today->format('Y-m-d H:i:s') . "</p>\n";

    // Query String Parameters
    echo "<h2>Query String:</h2>\n";
    $query_string = $_SERVER['QUERY_STRING'];
    if ($query_string) {
        $params = explode('&', $query_string);
        echo "<ul>\n";
        foreach ($params as $param) {
            $key_value = explode('=', $param);
            $key = htmlspecialchars($key_value[0]);
            $value = isset($key_value[1]) ? htmlspecialchars($key_value[1]) : '';
            echo "<li><strong>$key:</strong> $value</li>\n";
        }
        echo "</ul>\n";
    } 
    
    else {
        echo "<p>No Query String Received.</p>\n";
    }

    // Message Body
    echo "<h2>Message Body:</h2>\n";
    $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
    if ($content_length > 0) {
        $message_body = file_get_contents('php://input');
        echo "<pre>" . htmlspecialchars($message_body) . "</pre>\n";
    } 
    
    else {
        echo "<p>No Message Body Received.</p>\n";
    }

    echo "</body>\n";
    echo "</html>\n";
?>