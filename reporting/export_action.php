<?php
// export_action.php
// Queries the DB server-side, builds a clean HTML report, and generates a PDF using dompdf.
// Accepts: title, category, comments (optional) via POST.
// Returns JSON response.

require_once 'auth_check.php';
require_once 'auth_helpers.php';
require_once 'db.php';
require_once '/var/lib/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

if (!canExport()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$title    = trim($_POST['title']    ?? '');
$category = trim($_POST['category'] ?? '');
$comments = trim($_POST['comments'] ?? '');

$allowedCategories = ['sessions', 'events', 'charts'];

if (empty($title) || !in_array($category, $allowedCategories, true)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$escapedTitle    = htmlspecialchars($title);
$escapedCategory = htmlspecialchars($category);
$escapedUser     = htmlspecialchars($_SESSION['username']);
$escapedDate     = date('D, d M Y');
$escapedComments = htmlspecialchars($comments);

// ── Query data ────────────────────────────────────────────────────────────────
try {
    $pdo = getDB();

    if ($category === 'sessions') {
        $rows = $pdo->query(
            "SELECT sid, first_seen, last_seen, user_agent
             FROM sessions
             ORDER BY last_seen DESC
             LIMIT 200"
        )->fetchAll(PDO::FETCH_ASSOC);

        $tableHead = '<tr>
            <th>#</th>
            <th>Session ID</th>
            <th>First Seen</th>
            <th>Last Seen</th>
            <th>User Agent</th>
        </tr>';

        $tableBody = '';
        foreach ($rows as $i => $r) {
            $sid       = htmlspecialchars($r['sid']);
            $first     = htmlspecialchars($r['first_seen']);
            $last      = htmlspecialchars($r['last_seen']);
            $ua        = htmlspecialchars($r['user_agent'] ?? '—');
            $tableBody .= "<tr>
                <td>" . ($i + 1) . "</td>
                <td class=\"mono\">{$sid}</td>
                <td>{$first}</td>
                <td>{$last}</td>
                <td class=\"ua\">{$ua}</td>
            </tr>";
        }

        $summary = count($rows) . ' session' . (count($rows) !== 1 ? 's' : '');

    } elseif ($category === 'events') {
        $rows = $pdo->query(
            "SELECT e.id, e.event_type, e.ts_server, e.page, s.sid
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             ORDER BY e.ts_server DESC
             LIMIT 200"
        )->fetchAll(PDO::FETCH_ASSOC);

        $tableHead = '<tr>
            <th>#</th>
            <th>ID</th>
            <th>Type</th>
            <th>Timestamp</th>
            <th>Page</th>
            <th>Session</th>
        </tr>';

        $tableBody = '';
        foreach ($rows as $i => $r) {
            $id    = htmlspecialchars($r['id']);
            $type  = htmlspecialchars($r['event_type']);
            $ts    = htmlspecialchars($r['ts_server']);
            $page  = htmlspecialchars($r['page'] ?? '—');
            $sid   = htmlspecialchars(substr($r['sid'], 0, 12) . '…');
            $tableBody .= "<tr>
                <td>" . ($i + 1) . "</td>
                <td>{$id}</td>
                <td><span class=\"badge badge-{$type}\">{$type}</span></td>
                <td>{$ts}</td>
                <td class=\"ua\">{$page}</td>
                <td class=\"mono\">{$sid}</td>
            </tr>";
        }

        $summary = count($rows) . ' event' . (count($rows) !== 1 ? 's' : '');

    } else {
        // charts — full summary: performance, traffic, activity

        // ── Performance ──────────────────────────────────────────────────
        $perfRows = $pdo->query(
            "SELECT payload FROM events WHERE event_type = 'performance'"
        )->fetchAll(PDO::FETCH_COLUMN);

        $lcpVals = []; $inpVals = []; $clsVals = []; $ttfbVals = [];
        foreach ($perfRows as $raw) {
            $p = json_decode($raw, true);
            if (isset($p['webVitals']['lcp']))  $lcpVals[]  = $p['webVitals']['lcp'];
            if (isset($p['webVitals']['inp']))  $inpVals[]  = $p['webVitals']['inp'];
            if (isset($p['webVitals']['cls']))  $clsVals[]  = $p['webVitals']['cls'];
            if (isset($p['ttfb']))              $ttfbVals[] = $p['ttfb'];
        }

        function medianVal(array $arr) {
            if (!$arr) return null;
            sort($arr);
            $mid = (int)(count($arr) / 2);
            return count($arr) % 2 !== 0 ? $arr[$mid] : ($arr[$mid - 1] + $arr[$mid]) / 2;
        }

        $lcpTotal = count($lcpVals);
        $lcpGood  = count(array_filter($lcpVals, fn($v) => $v < 2500));
        $lcpBad   = $lcpTotal - $lcpGood;
        $lcpPct   = $lcpTotal > 0 ? round(($lcpGood / $lcpTotal) * 100, 1) : 0;

        $inpTotal = count($inpVals);
        $inpGood  = count(array_filter($inpVals, fn($v) => $v < 200));
        $inpNeeds = count(array_filter($inpVals, fn($v) => $v >= 200 && $v < 500));
        $inpPoor  = $inpTotal - $inpGood - $inpNeeds;
        $inpPct   = $inpTotal > 0 ? round(($inpGood / $inpTotal) * 100, 1) : 0;

        $medLcp  = $lcpVals  ? round(medianVal($lcpVals))  . 'ms' : '—';
        $medInp  = $inpVals  ? round(medianVal($inpVals))  . 'ms' : '—';
        $medCls  = $clsVals  ? round(medianVal($clsVals), 3)      : '—';
        $medTtfb = $ttfbVals ? round(medianVal($ttfbVals)) . 'ms' : '—';

        // ── Traffic ──────────────────────────────────────────────────────
        $browsers = $pdo->query(
            "SELECT user_agent FROM sessions WHERE user_agent IS NOT NULL"
        )->fetchAll(PDO::FETCH_COLUMN);

        $browserCounts = [];
        foreach ($browsers as $ua) {
            if (stripos($ua, 'Edg') !== false)                                                $b = 'Edge';
            elseif (stripos($ua, 'Chrome') !== false)                                         $b = 'Chrome';
            elseif (stripos($ua, 'Firefox') !== false)                                        $b = 'Firefox';
            elseif (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false)     $b = 'Safari';
            else                                                                               $b = 'Other';
            $browserCounts[$b] = ($browserCounts[$b] ?? 0) + 1;
        }
        arsort($browserCounts);

        $sessionsByDay = $pdo->query(
            "SELECT DATE(first_seen) AS day, COUNT(*) AS cnt FROM sessions GROUP BY DATE(first_seen) ORDER BY day"
        )->fetchAll(PDO::FETCH_ASSOC);

        // ── Activity ─────────────────────────────────────────────────────
        $activityRows = $pdo->query(
            "SELECT payload FROM events WHERE event_type = 'activity'"
        )->fetchAll(PDO::FETCH_COLUMN);

        $pageCounts   = [];
        $timesOnPage  = [];
        $idleDurations = [];
        foreach ($activityRows as $raw) {
            $p = json_decode($raw, true);
            if (isset($p['page'])) {
                $path = parse_url($p['page'], PHP_URL_PATH) ?: $p['page'];
                $pageCounts[$path] = ($pageCounts[$path] ?? 0) + 1;
            }
            if (isset($p['timeOnPageMs']) && $p['timeOnPageMs'] > 0) $timesOnPage[] = $p['timeOnPageMs'];
            foreach ($p['idlePeriods'] ?? [] as $ip) {
                if (isset($ip['durationMs'])) $idleDurations[] = $ip['durationMs'];
            }
        }
        arsort($pageCounts);
        $topPages = array_slice($pageCounts, 0, 10, true);

        $idleShort  = count(array_filter($idleDurations, fn($d) => $d < 5000));
        $idleMedium = count(array_filter($idleDurations, fn($d) => $d >= 5000 && $d < 30000));
        $idleLong   = count(array_filter($idleDurations, fn($d) => $d >= 30000));

        $medTimeOnPage   = $timesOnPage   ? round(medianVal($timesOnPage) / 1000, 1) . 's'   : '—';
        $medIdleDuration = $idleDurations ? round(medianVal($idleDurations) / 1000, 1) . 's' : '—';

        // ── Build table ───────────────────────────────────────────────────
        $tableHead = '<tr><th>Metric</th><th>Value</th></tr>';

        $tableBody  = '<tr><td colspan="2" class="section-sep">Performance</td></tr>';
        $tableBody .= "<tr><td>Median LCP</td><td>{$medLcp}</td></tr>";
        $tableBody .= "<tr><td>Good LCP (&lt; 2.5s)</td><td>{$lcpGood} / {$lcpTotal} ({$lcpPct}%)</td></tr>";
        $tableBody .= "<tr><td>Median INP</td><td>{$medInp}</td></tr>";
        $tableBody .= "<tr><td>Good INP (&lt; 200ms)</td><td>{$inpGood} / {$inpTotal} ({$inpPct}%)</td></tr>";
        $tableBody .= "<tr><td>Median CLS</td><td>{$medCls}</td></tr>";
        $tableBody .= "<tr><td>Median TTFB</td><td>{$medTtfb}</td></tr>";

        $tableBody .= '<tr><td colspan="2" class="section-sep">Traffic · Browser Distribution</td></tr>';
        foreach ($browserCounts as $browser => $count) {
            $b = htmlspecialchars($browser);
            $tableBody .= "<tr><td>{$b}</td><td>{$count}</td></tr>";
        }

        $tableBody .= '<tr><td colspan="2" class="section-sep">Traffic · Sessions by Day</td></tr>';
        foreach ($sessionsByDay as $row) {
            $day = htmlspecialchars($row['day']);
            $tableBody .= "<tr><td>{$day}</td><td>{$row['cnt']} session" . ($row['cnt'] != 1 ? 's' : '') . "</td></tr>";
        }

        $tableBody .= '<tr><td colspan="2" class="section-sep">Activity · Most Viewed Pages (Top 10)</td></tr>';
        foreach ($topPages as $path => $count) {
            $p = htmlspecialchars($path);
            $tableBody .= "<tr><td>{$p}</td><td>{$count} event" . ($count != 1 ? 's' : '') . "</td></tr>";
        }

        $tableBody .= '<tr><td colspan="2" class="section-sep">Activity · Engagement</td></tr>';
        $tableBody .= "<tr><td>Median Time on Page</td><td>{$medTimeOnPage}</td></tr>";
        $tableBody .= "<tr><td>Median Idle Duration</td><td>{$medIdleDuration}</td></tr>";
        $tableBody .= "<tr><td>Idle Periods — Short (&lt; 5s)</td><td>{$idleShort}</td></tr>";
        $tableBody .= "<tr><td>Idle Periods — Medium (5–30s)</td><td>{$idleMedium}</td></tr>";
        $tableBody .= "<tr><td>Idle Periods — Long (&gt; 30s)</td><td>{$idleLong}</td></tr>";

        $summary = $lcpTotal . ' performance event' . ($lcpTotal !== 1 ? 's' : '')
                 . ' · ' . count($browsers) . ' session' . (count($browsers) !== 1 ? 's' : '')
                 . ' · ' . count($activityRows) . ' activity event' . (count($activityRows) !== 1 ? 's' : '');
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// ── Build HTML ────────────────────────────────────────────────────────────────
$commentsBlock = '';
if (!empty($escapedComments)) {
    $commentsBlock = <<<HTML
    <div class="comments">
        <div class="comments-label">Analyst Comments</div>
        <div class="comments-body">{$escapedComments}</div>
    </div>
HTML;
}

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Helvetica, Arial, sans-serif;
      background: #ffffff;
      color: #1a1a2e;
      padding: 32px 40px;
      font-size: 11px;
    }
    .header {
      border-bottom: 2px solid #00cc7a;
      padding-bottom: 14px;
      margin-bottom: 20px;
    }
    .label {
      font-size: 8px;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: #00cc7a;
      margin-bottom: 4px;
    }
    .title {
      font-size: 20px;
      font-weight: bold;
      color: #1a1a2e;
      margin-bottom: 6px;
    }
    .meta { font-size: 9px; color: #888; }
    .meta span { color: #0055dd; margin-right: 16px; }
    .summary { font-size: 10px; color: #555; margin-bottom: 16px; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
    }
    th {
      background: #1a1a2e;
      color: #ffffff;
      padding: 7px 10px;
      text-align: left;
      font-size: 9px;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    td {
      padding: 6px 10px;
      border-bottom: 1px solid #eee;
      color: #1a1a2e;
      vertical-align: top;
    }
    tr:nth-child(even) td { background: #f8f8fc; }
    .mono { font-family: monospace; font-size: 10px; }
    .ua { font-size: 9px; color: #555; max-width: 200px; word-break: break-all; }
    .badge {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 4px;
      font-size: 8px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .badge-static      { background: #e8f0ff; color: #0055dd; }
    .badge-performance { background: #e8fff5; color: #00aa55; }
    .badge-activity    { background: #fff5e8; color: #dd7700; }
    .badge-noscript    { background: #f0e8ff; color: #7700dd; }
    .section-sep { font-weight: bold; background: #f0f0f8; color: #1a1a2e; }
    .comments {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 16px 20px;
      margin-top: 8px;
    }
    .comments-label {
      font-size: 9px;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: #00cc7a;
      margin-bottom: 8px;
      font-weight: bold;
    }
    .comments-body {
      font-size: 11px;
      color: #333;
      line-height: 1.6;
      white-space: pre-wrap;
    }
    .footer {
      margin-top: 24px;
      font-size: 8px;
      color: #aaa;
      text-align: center;
      border-top: 1px solid #eee;
      padding-top: 10px;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="label">CSE135 · Analytics Report</div>
    <div class="title">{$escapedTitle}</div>
    <div class="meta">
      <span>Category: {$escapedCategory}</span>
      <span>By: {$escapedUser}</span>
      <span>{$escapedDate}</span>
    </div>
  </div>
  <div class="summary">{$summary}</div>
  <table>
    <thead>{$tableHead}</thead>
    <tbody>{$tableBody}</tbody>
  </table>
  {$commentsBlock}
  <div class="footer">Generated by DataLens · CSE135 Winter 2026 · {$escapedDate}</div>
</body>
</html>
HTML;

// ── Generate PDF ──────────────────────────────────────────────────────────────
$options = new Options();
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename    = 'report_' . uniqid() . '.pdf';
$savePath    = __DIR__ . '/' . $filename;
$pdfContents = $dompdf->output();

if (file_put_contents($savePath, $pdfContents) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to save PDF']);
    exit;
}

$pdfUrl = 'https://reporting.cse135wi2026.site/' . $filename;

// ── Insert report record ──────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare(
        "INSERT INTO reports (user_id, title, category, pdf_url) VALUES (:user_id, :title, :category, :pdf_url)"
    );
    $stmt->execute([
        ':user_id'  => $_SESSION['user_id'],
        ':title'    => $title,
        ':category' => $category,
        ':pdf_url'  => $pdfUrl,
    ]);

    echo json_encode(['success' => true, 'pdf_url' => $pdfUrl]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}