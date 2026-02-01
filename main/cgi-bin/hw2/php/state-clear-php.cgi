#!/usr/lib/php
<?php
    echo  "Cache-Control: no-cache\n";
    echo "Content-Type: text/html\n\n";
    
    session_start();

    // Clear all session data
    $_SESSION = [];

    // Delete the session cookie
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

    // Destroy the session
    session_destroy();
    header('Location: /state-collect-php.html', true, 302);
    exit;
?>