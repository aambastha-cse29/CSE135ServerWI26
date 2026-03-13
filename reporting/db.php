<?php
// db.php
// Returns a shared PDO connection.
// Include on any page that needs DB access.

define('DB_HOST', 'localhost');
define('DB_NAME', 'cse135');
define('DB_USER', 'cse135user');
define('DB_PASS', 'MySQLAman123CSE135!');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}