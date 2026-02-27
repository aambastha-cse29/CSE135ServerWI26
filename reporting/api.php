<?php

// --------------------
// DB CONNECTION
// --------------------
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=cse135;charset=utf8mb4",
        "cse135user",
        "MySQLAman123CSE135!",
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (Throwable $e) {
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// --------------------
// ROUTING
// --------------------
$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
// /api/sessions/1       → ['api', 'sessions', '1']
// /api/events/static    → ['api', 'events', 'static']
// /api/events/1         → ['api', 'events', '1']

$resource = $segments[1] ?? null;
$segment2 = $segments[2] ?? null;
$method   = $_SERVER['REQUEST_METHOD'];

// Distinguish numeric id from string type
$id   = is_numeric($segment2) ? (int)$segment2 : null;
$type = (!is_numeric($segment2) && $segment2 !== null) ? $segment2 : null;

header("Content-Type: application/json");

if ($resource === "sessions") {
    sessionsFunction($pdo, $method, $id);
} elseif ($resource === "events") {
    eventsFunction($pdo, $method, $id, $type);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Resource not found"]);
}


// --------------------
// SESSIONS
// --------------------
function sessionsFunction(PDO $pdo, string $method, ?int $id): void {

    if ($method === "GET" && !$id) {
        // GET /api/sessions — get all sessions
        $stmt = $pdo->query("
            SELECT id, sid, first_seen, last_seen, INET6_NTOA(ip) as ip, user_agent
            FROM sessions
            ORDER BY id DESC
        ");
        echo json_encode($stmt->fetchAll());
    }

    elseif ($method === "GET" && $id) {
        // GET /api/sessions/{id} — get specific session
        $stmt = $pdo->prepare("
            SELECT id, sid, first_seen, last_seen, INET6_NTOA(ip) as ip, user_agent
            FROM sessions
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(["error" => "Session not found"]);
            return;
        }
        echo json_encode($row);
    }

    elseif ($method === "POST" && !$id) {
        // POST /api/sessions — create a new session
        $body = json_decode(file_get_contents("php://input"), true);
        if (!is_array($body) || empty($body['sid'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required field: sid"]);
            return;
        }

        $sid        = substr(preg_replace('/[^A-Za-z0-9\-]/', '', $body['sid']), 0, 64);
        $now        = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
        $user_agent = isset($body['user_agent']) ? substr($body['user_agent'], 0, 512) : null;

        // IP from server, never from client body
        $ip_str = $_SERVER['REMOTE_ADDR'] ?? null;
        $ip_bin = null;
        if (is_string($ip_str) && $ip_str !== '') {
            $packed = @inet_pton($ip_str);
            if ($packed !== false) $ip_bin = $packed;
        }

        $stmt = $pdo->prepare("
            INSERT INTO sessions (sid, first_seen, last_seen, ip, user_agent)
            VALUES (:sid, :first_seen, :last_seen, :ip, :ua)
        ");
        $stmt->bindValue(':sid',        $sid,        PDO::PARAM_STR);
        $stmt->bindValue(':first_seen', $now,        PDO::PARAM_STR);
        $stmt->bindValue(':last_seen',  $now,        PDO::PARAM_STR);
        $stmt->bindValue(':ua',         $user_agent, PDO::PARAM_STR);

        if ($ip_bin !== null) $stmt->bindValue(':ip', $ip_bin, PDO::PARAM_LOB);
        else                  $stmt->bindValue(':ip', null,    PDO::PARAM_NULL);

        try {
          $stmt->execute();
        } 
       catch (PDOException $e) {
            if ($e->getCode() === '23000') {
               http_response_code(409);
               echo json_encode(["error" => "Session with this sid already exists"]);
               return;
            }

           throw $e;
       }

        http_response_code(201);
        echo json_encode(["id" => (int)$pdo->lastInsertId(), "sid" => $sid]);
    }

    elseif ($method === "PUT" && $id) {
        // PUT /api/sessions/{id} — update last_seen and user_agent only
        // ip and first_seen are immutable — they represent historical facts
        $body = json_decode(file_get_contents("php://input"), true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON body"]);
            return;
        }

        $now        = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
        $user_agent = isset($body['user_agent']) ? substr($body['user_agent'], 0, 512) : null;

        $stmt = $pdo->prepare("
            UPDATE sessions
            SET last_seen  = :last_seen,
                user_agent = COALESCE(:ua, user_agent)
            WHERE id = :id
        ");
        $stmt->execute([
            ':last_seen' => $now,
            ':ua'        => $user_agent,
            ':id'        => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Session not found"]);
            return;
        }
        echo json_encode(["message" => "Session updated", "id" => $id]);
    }

    elseif ($method === "DELETE" && $id) {
        // DELETE /api/sessions/{id} — delete session (cascades to events)
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Session not found"]);
            return;
        }
        echo json_encode(["message" => "Session deleted", "id" => $id]);
    }

    else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}


// --------------------
// EVENTS
// --------------------
function eventsFunction(PDO $pdo, string $method, ?int $id, ?string $type): void {

    $allowedTypes = ['static', 'performance', 'activity', 'noscript'];

    if ($method === "GET" && !$id && !$type) {
        // GET /api/events — get all events (payload excluded for performance)
        $stmt = $pdo->query("
            SELECT id, session_id, event_type, ts_client, ts_server, page
            FROM events
            ORDER BY id DESC
        ");
        echo json_encode($stmt->fetchAll());
    }

    elseif ($method === "GET" && $type && !$id) {
        // GET /api/events/static|performance|activity|noscript
        if (!in_array($type, $allowedTypes, true)) {
            http_response_code(404);
            echo json_encode(["error" => "Invalid event type"]);
            return;
        }
        $stmt = $pdo->prepare("
            SELECT id, session_id, event_type, ts_client, ts_server, page
            FROM events
            WHERE event_type = :type
            ORDER BY id DESC
        ");
        $stmt->execute([':type' => $type]);
        echo json_encode($stmt->fetchAll());
    }

    elseif ($method === "GET" && $id) {
        // GET /api/events/{id} — get specific event with full payload
        $stmt = $pdo->prepare("
            SELECT id, session_id, event_type, ts_client, ts_server, page, payload
            FROM events
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(["error" => "Event not found"]);
            return;
        }

        // Decode JSON payload so it's not double-encoded in response
        $row['payload'] = json_decode($row['payload'], true);
        echo json_encode($row);
    }

    elseif ($method === "POST" && !$id && !$type) {
        // POST /api/events — create a new event
        $body = json_decode(file_get_contents("php://input"), true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON body"]);
            return;
        }

        $session_id = isset($body['session_id']) ? (int)$body['session_id'] : null;
        $event_type = $body['event_type'] ?? null;
        $page       = $body['page'] ?? null;
        $payload    = $body['payload'] ?? null;

        if (!$session_id || !$event_type || !in_array($event_type, $allowedTypes, true) || !$payload) {
            http_response_code(400);
            echo json_encode(["error" => "Missing or invalid required fields: session_id, event_type, payload"]);
            return;
        }

        $now          = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
        $payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare("
            INSERT INTO events (session_id, event_type, ts_client, ts_server, page, payload)
            VALUES (:session_id, :event_type, :ts_client, :ts_server, :page, CAST(:payload AS JSON))
        ");
        $stmt->execute([
            ':session_id' => $session_id,
            ':event_type' => $event_type,
            ':ts_client'  => $body['ts_client'] ?? null,
            ':ts_server'  => $now,
            ':page'       => $page,
            ':payload'    => $payload_json,
        ]);

        http_response_code(201);
        echo json_encode(["id" => (int)$pdo->lastInsertId()]);
    }

    elseif ($method === "PUT" && $id) {
        // PUT /api/events/{id} — update event payload only
        $body = json_decode(file_get_contents("php://input"), true);
        if (!is_array($body) || !isset($body['payload'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required field: payload"]);
            return;
        }

        $payload_json = json_encode($body['payload'], JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare("
            UPDATE events
            SET payload = CAST(:payload AS JSON)
            WHERE id = :id
        ");
        $stmt->execute([
            ':payload' => $payload_json,
            ':id'      => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Event not found"]);
            return;
        }
        echo json_encode(["message" => "Event updated", "id" => $id]);
    }

    elseif ($method === "DELETE" && $id) {
        // DELETE /api/events/{id} — delete a specific event
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Event not found"]);
            return;
        }
        echo json_encode(["message" => "Event deleted", "id" => $id]);
    }

    else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}
?>