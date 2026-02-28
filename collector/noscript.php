<?php
// noscript.php
// Fired when JS is disabled via <noscript> img pixel.
// Logs a minimal noscript event into the events table.
// Always responds with a 1x1 transparent GIF.

header("Access-Control-Allow-Origin: https://test.cse135wi2026.site");
header("Content-Type: image/gif");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

// Always send the 1x1 transparent GIF regardless of what happens below
$gif = base64_decode("R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==");

// --------------------
// DB CONNECTION
// --------------------
$DB_HOST = 'localhost';
$DB_NAME = 'cse135';
$DB_USER = 'cse135user';
$DB_PASS = 'MySQLAman123CSE135!';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    echo $gif;
    exit;
}

// --------------------
// COLLECT DATA
// --------------------
$page = $_GET['page'] ?? null;

// Validate page — must be a simple string, no URL injection
if (!is_string($page) || $page === '' || strlen($page) > 128) {
    echo $gif;
    exit;
}
// Only allow alphanumeric, hyphens, underscores
if (!preg_match('/^[A-Za-z0-9_\-]+$/', $page)) {
    echo $gif;
    exit;
}

// Server-side enrichments
$ts_server = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
$ts_client = round(microtime(true) * 1000); // ms since epoch, server-side

$ip_str = $_SERVER['REMOTE_ADDR'] ?? null;
$ip_bin = null;
if (is_string($ip_str) && $ip_str !== '') {
    $packed = @inet_pton($ip_str);
    if ($packed !== false) $ip_bin = $packed;
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
if (is_string($ua) && strlen($ua) > 512) {
    $ua = substr($ua, 0, 512);
}

$referrer = $_SERVER['HTTP_REFERER'] ?? null;
if (is_string($referrer) && strlen($referrer) > 2048) {
    $referrer = substr($referrer, 0, 2048);
}

// --------------------
// SESSION UPSERT
// --------------------
// No sid available (no JS = no localStorage) so we generate one server-side.
// Each noscript hit gets its own sid since we can't track across pages.
try {
    $sid = bin2hex(random_bytes(16));
} catch (Throwable $e) {
    echo $gif;
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO sessions (sid, first_seen, last_seen, ip, user_agent)
        VALUES (:sid, :first_seen, :last_seen, :ip, :ua)
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            last_seen = VALUES(last_seen)
    ");

    $stmt->bindValue(':sid',        $sid, PDO::PARAM_STR);
    $stmt->bindValue(':first_seen', $ts_server, PDO::PARAM_STR);
    $stmt->bindValue(':last_seen',  $ts_server, PDO::PARAM_STR);

    if ($ip_bin !== null) $stmt->bindValue(':ip', $ip_bin, PDO::PARAM_LOB);
    else                  $stmt->bindValue(':ip', null,    PDO::PARAM_NULL);

    if ($ua !== null) $stmt->bindValue(':ua', $ua, PDO::PARAM_STR);
    else              $stmt->bindValue(':ua', null, PDO::PARAM_NULL);

    $stmt->execute();
    $session_id = (int)$pdo->lastInsertId();

    if ($session_id <= 0) {
        echo $gif;
        exit;
    }
} catch (Throwable $e) {
    echo $gif;
    exit;
}

// --------------------
// EVENT INSERT
// --------------------
$payload = [
    'page'      => $page,           // page name from query string
    'referrer'  => $referrer,       // HTTP referrer
    'userAgent' => $ua,             // browser user agent
    'jsEnabled' => false,           // the whole point — JS is disabled
    'ts'        => $ts_client,      // server-side timestamp in ms
];

$payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES);

try {
    $stmt2 = $pdo->prepare("
        INSERT INTO events (session_id, event_type, ts_client, ts_server, page, payload)
        VALUES (:session_id, 'noscript', :ts_client, :ts_server, :page, CAST(:payload AS JSON))
    ");
    $stmt2->execute([
        ':session_id' => $session_id,
        ':ts_client'  => $ts_client,
        ':ts_server'  => $ts_server,
        ':page'       => $page,
        ':payload'    => $payload_json,
    ]);
} catch (Throwable $e) {
    // Silent fail — still send the GIF
}

// Always send the GIF
echo $gif;
exit;