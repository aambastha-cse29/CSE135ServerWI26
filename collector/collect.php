<?php

require_once __DIR__ . "/validate.php";

header("Access-Control-Allow-Origin: https://test.cse135wi2026.site");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Expose-Headers: X-CSE135-SID");
header("Access-Control-Allow-Credentials: true");
header("Vary: Origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(204);
    exit;
}

$raw = file_get_contents("php://input");

if ($raw === false || $raw === '' || strlen($raw) > 26244) {
    http_response_code(204);
    exit;
}


$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(204);
    exit;
}

$sid = $data['sid'] ?? ($data['ensuredSid'] ?? null);
$ts = $data['ts'] ?? null;
$page = $data['page'] ?? null;

if (!is_string($sid) || $sid === '' || strlen($sid) > 64) {
    http_response_code(204);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sid)) {
    http_response_code(204);
    exit;
}

if (!is_int($ts) && !(is_string($ts) && preg_match('/^\d+$/'))) {
    http_response_code(204);
    exit;
}

$ts_client = (int) $ts;

if (!is_string($page) || $page === '' || strlen($page) > 2048) {
    http_response_code(204);
    exit;
}

if(!preg_match('#^https?://#i', $page)) {
    http_response_code(204);
    exit;
}

// Check For Type To Match Performance, Activity, Or Static
$allowedTypes = ['performance', 'activity', 'static'];
$typeFound = null;
$typecount = 0;
foreach ($allowedTypes as $type) {
    if (isset($data[$type])) {
        $typeFound = $type;
        $typecount++;
    }
}

if ($typeFound === null || $typecount !== 1) {
    http_response_code(204);
    exit;
}

$payload = $data[$typeFound];
if (!is_array($payload)) {
    http_response_code(204);
    exit;
}

$payloadForDb = null;
if ($typeFound === 'static') {
    $clean = validate_static_payload($payload);
    if ($clean === null) {
        http_response_code(204);
        exit;
    }
    $payloadForDb = $clean;
}

else if ($typeFound === 'activity') {
    $clean = validate_activity_payload($payload);
    if ($clean === null) {
        http_response_code(204);
        exit;
    }
    $payloadForDb = $clean;
}

else if ($typeFound === 'performance') {
    $clean = validate_performance_payload($payload);
    if ($clean === null) {
        http_response_code(204);
        exit;
    }
    $payloadForDb = $clean;
}

else{
    http_response_code(204);
    exit;
}

// At this point, we have a valid $payloadForDb array ready to be inserted into the DB.
// The DB insertion code is below

// --------------------
// Server-side enrichments (recommended by prof)
// --------------------
$ts_server_dt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
  ->format('Y-m-d H:i:s.v'); // DATETIME(3) compatible

// Client IP (best-effort; don't trust XFF unless you control proxy)
$ip_str = $_SERVER['REMOTE_ADDR'] ?? null;
$ip_bin = null;
if (is_string($ip_str) && $ip_str !== '') {
  $packed = @inet_pton($ip_str);
  if ($packed !== false) $ip_bin = $packed; // VARBINARY(16)
}

$ua_server = $_SERVER['HTTP_USER_AGENT'] ?? null;
if (is_string($ua_server) && strlen($ua_server) > 512) {
  $ua_server = mb_substr($ua_server, 0, 512, 'UTF-8');
}

// --------------------
// DB: sessions upsert + events insert
// --------------------
// Put credentials in a separate config.php ideally.
$dbHost = 'localhost';
$dbName = 'cse135';
$dbUser = 'cse135user';
$dbPass = 'MySQLAman123CSE135!'; // Use env vars or config files in production, not hardcoding

try {
  $pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );

  // 1) Upsert session and get numeric session_id reliably
  // Trick: ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id) so lastInsertId() works for existing rows too.
  $sqlSession = "
    INSERT INTO sessions (sid, first_seen, last_seen, ip, user_agent)
    VALUES (:sid, :first_seen, :last_seen, :ip, :ua)
    ON DUPLICATE KEY UPDATE
      id = LAST_INSERT_ID(id),
      last_seen = VALUES(last_seen),
      ip = COALESCE(VALUES(ip), ip),
      user_agent = COALESCE(VALUES(user_agent), user_agent)
  ";

  $stmt = $pdo->prepare($sqlSession);
  $stmt->bindValue(':sid', $sid, PDO::PARAM_STR);
  $stmt->bindValue(':first_seen', $ts_server_dt, PDO::PARAM_STR);
  $stmt->bindValue(':last_seen',  $ts_server_dt, PDO::PARAM_STR);

  if ($ip_bin !== null) $stmt->bindValue(':ip', $ip_bin, PDO::PARAM_LOB);
  else $stmt->bindValue(':ip', null, PDO::PARAM_NULL);

  if (is_string($ua_server) && $ua_server !== '') $stmt->bindValue(':ua', $ua_server, PDO::PARAM_STR);
  else $stmt->bindValue(':ua', null, PDO::PARAM_NULL);

  $stmt->execute();
  $session_id = (int)$pdo->lastInsertId();
  if ($session_id <= 0) {
    http_response_code(204);
    exit;
  }

  // 2) Insert event row
  $payload_json = json_encode($payloadForDb, JSON_UNESCAPED_SLASHES);
  if ($payload_json === false) {
    http_response_code(204);
    exit;
  }

  $sqlEvent = "
    INSERT INTO events (session_id, event_type, ts_client, ts_server, page, payload)
    VALUES (:session_id, :event_type, :ts_client, :ts_server, :page, CAST(:payload AS JSON))
  ";

  $stmt2 = $pdo->prepare($sqlEvent);
  $stmt2->bindValue(':session_id', $session_id, PDO::PARAM_INT);
  $stmt2->bindValue(':event_type', $typeFound, PDO::PARAM_STR);
  $stmt2->bindValue(':ts_client', $ts_client, PDO::PARAM_INT);
  $stmt2->bindValue(':ts_server', $ts_server_dt, PDO::PARAM_STR);
  $stmt2->bindValue(':page', $page, PDO::PARAM_STR);
  $stmt2->bindValue(':payload', $payload_json, PDO::PARAM_STR);
  $stmt2->execute();

}

catch (Throwable $e) {
  // Don’t leak details to client; prof wants 204 always.
  // You can still log server-side for debugging:
  error_log('collect.php error: ' . $e->getMessage());
  http_response_code(204);
  exit;
}

http_response_code(204);
exit;

?>