<?php
// session.php
// - No db.php, no helper.php
// - Always returns 204 (NO body)
// - Returns sid to client via response header: X-CSE135-SID
// - 30-minute timeout: if provided sid is expired/not found, rotate a new one

header("Access-Control-Allow-Origin: https://test.cse135wi2026.site");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(204);
  exit;
}

// ----------------------------
// DB CONNECT (inline)
// ----------------------------
$DB_HOST = 'localhost';
$DB_NAME = 'cse135';
$DB_USER = 'cse135user';
$DB_PASS = 'MySQLAman123CSE135!'; // Use env vars or config files in production, not hardcoding

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  // silent per spec
  http_response_code(204);
  exit;
}

// ----------------------------
// Helpers (inline)
// ----------------------------
function now_dt3_utc(): string {
  $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
  return $dt->format('Y-m-d H:i:s.v'); // DATETIME(3)
}

function get_client_ip_bin(): ?string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  if (!is_string($ip) || $ip === '') return null;
  $bin = @inet_pton($ip);
  return ($bin === false) ? null : $bin;
}

function get_ua(): ?string {
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
  if (!is_string($ua) || $ua === '') return null;
  return (strlen($ua) > 2000) ? substr($ua, 0, 2000) : $ua;
}

function is_valid_sid($sid): bool {
  if (!is_string($sid)) return false;
  $len = strlen($sid);
  if ($len < 8 || $len > 64) return false;
  return (bool)preg_match('/^[A-Za-z0-9-]+$/', $sid);
}

function gen_sid(): string {
  try {
    // 32 hex chars, safe and <= 64
    return bin2hex(random_bytes(16));
  } catch (Throwable $e) {
    // fallback
    return substr(preg_replace('/[^A-Za-z0-9]/', '', uniqid('sid', true)), 0, 64);
  }
}

// ----------------------------
// Parse input JSON: { "sid": "..." } (optional)
// ----------------------------
$raw = file_get_contents('php://input');
$in = json_decode($raw, true);

$client_sid = null;
if (is_array($in) && isset($in['sid']) && is_valid_sid($in['sid'])) {
  $client_sid = $in['sid'];
}

$ts = now_dt3_utc();
$ip_bin = get_client_ip_bin();
$ua = get_ua();

// ----------------------------
// Decide sid to use
// - If client sid exists and not expired (last_seen within 30m): keep it
// - Else: rotate new sid
// ----------------------------
$sid_to_use = null;

try {
  if ($client_sid !== null) {
    $stmt = $pdo->prepare("
      SELECT sid
      FROM sessions
      WHERE sid = :sid
        AND last_seen >= (UTC_TIMESTAMP(3) - INTERVAL 30 MINUTE)
      LIMIT 1
    ");
    $stmt->execute([':sid' => $client_sid]);
    $row = $stmt->fetch();

    if ($row && isset($row['sid'])) {
      $sid_to_use = $client_sid;
    }
  }

  if ($sid_to_use === null) {
    $sid_to_use = gen_sid();
  }

  // Ensure row exists + refresh last_seen
  // NOTE: UNIQUE(sid) required in table for this to work as intended.
  $stmt2 = $pdo->prepare("
    INSERT INTO sessions (sid, first_seen, last_seen, ip, user_agent)
    VALUES (:sid, :first_seen, :last_seen, :ip, :ua)
    ON DUPLICATE KEY UPDATE
      last_seen = VALUES(last_seen),
      ip = COALESCE(VALUES(ip), ip),
      user_agent = COALESCE(VALUES(user_agent), user_agent)
  ");

  $stmt2->bindValue(':sid', $sid_to_use, PDO::PARAM_STR);
  $stmt2->bindValue(':first_seen', $ts, PDO::PARAM_STR);
  $stmt2->bindValue(':last_seen', $ts, PDO::PARAM_STR);

  if ($ip_bin !== null) $stmt2->bindValue(':ip', $ip_bin, PDO::PARAM_LOB);
  else $stmt2->bindValue(':ip', null, PDO::PARAM_NULL);

  if ($ua !== null) $stmt2->bindValue(':ua', $ua, PDO::PARAM_STR);
  else $stmt2->bindValue(':ua', null, PDO::PARAM_NULL);

  $stmt2->execute();

  // Return sid via header (still 204)
  header('X-CSE135-SID: ' . $sid_to_use);
  http_response_code(204);
  exit;

} 

catch (Throwable $e) {
  http_response_code(204);
  exit;

}

?>
