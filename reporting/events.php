<?php require_once 'auth_check.php'; ?>
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
    die("Database connection failed.");
}

$stmt = $pdo->query("
    SELECT id, session_id, event_type, ts_client, ts_server, page, payload
    FROM events
    ORDER BY id DESC
");
$events = $stmt->fetchAll();
$total  = count($events);

// Decode payloads for JSON display
foreach ($events as &$e) {
    $e['payload'] = json_decode($e['payload'], true);
}
unset($e);
?>

<!-- The events.php page displays a table of all collected events from the database. 
 It includes a header with navigation, filter tabs to filter events by type, and a 
 table that lists event details. Each row can be expanded to show the full JSON payload of the event. 
 The design is consistent with the rest of the dashboard, using a dark theme with green accents. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Events</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <!-- Embedded CSS For Styling The Events Page -->
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #0a0a0f;
      --surface: #111118;
      --surface2:#0d0d14;
      --border:  #1e1e2e;
      --accent:  #00ff9d;
      --accent2: #0066ff;
      --accent3: #ff6b35;
      --text:    #e8e8f0;
      --muted:   #5a5a7a;
      --row-hover: #16161f;

      /* event type colors */
      --c-static:      #0066ff;
      --c-performance: #00ff9d;
      --c-activity:    #ff6b35;
      --c-noscript:    #aa88ff;
    }

    html, body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Mono', monospace;
      min-height: 100vh;
    }

    body {
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(rgba(0,255,157,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,255,157,0.025) 1px, transparent 1px);
      background-size: 40px 40px;
      animation: gridMove 20s linear infinite;
      pointer-events: none;
      z-index: 0;
    }

    @keyframes gridMove {
      0%   { transform: translateY(0); }
      100% { transform: translateY(40px); }
    }

    .page {
      position: relative;
      z-index: 1;
      max-width: 1200px;
      margin: 0 auto;
      padding: 48px 32px;
    }

    /* ---------- HEADER ---------- */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 48px;
      animation: fadeDown 0.5s ease both;
    }

    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .brand-label {
      font-family: 'Syne', sans-serif;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 6px;
    }

    .brand-title {
      font-family: 'Syne', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.02em;
    }

    .brand-title span { color: var(--accent); }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .btn-back {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 20px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--muted);
      cursor: pointer;
      text-decoration: none;
      transition: border-color 0.2s, color 0.2s;
      letter-spacing: 0.05em;
    }

    .btn-back:hover {
      border-color: var(--accent2);
      color: var(--accent2);
    }

    .logout-form button {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 20px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--muted);
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
      letter-spacing: 0.05em;
    }

    .logout-form button:hover {
      border-color: var(--accent);
      color: var(--accent);
    }

    /* ---------- PAGE HEADING ---------- */
    .page-heading {
      margin-bottom: 32px;
      animation: fadeUp 0.5s ease 0.1s both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .page-eyebrow {
      font-size: 11px;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 10px;
    }

    .page-title {
      font-family: 'Syne', sans-serif;
      font-size: 32px;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.02em;
      margin-bottom: 8px;
    }

    .page-title span { color: var(--accent); }

    .page-meta {
      font-size: 12px;
      color: var(--muted);
    }

    .page-meta strong { color: var(--accent); font-weight: 500; }

    /* ---------- FILTER TABS ---------- */
    .filter-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 24px;
      flex-wrap: wrap;
      animation: fadeUp 0.5s ease 0.15s both;
    }

    .tab {
      padding: 7px 16px;
      border-radius: 20px;
      font-size: 11px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      cursor: pointer;
      border: 1px solid var(--border);
      background: transparent;
      color: var(--muted);
      font-family: 'DM Mono', monospace;
      transition: all 0.2s;
    }

    .tab:hover     { border-color: var(--muted); color: var(--text); }
    .tab.active    { background: var(--surface); color: var(--text); border-color: var(--muted); }

    .tab[data-type="all"].active      { border-color: var(--text);         color: var(--text); }
    .tab[data-type="static"].active   { border-color: var(--c-static);     color: var(--c-static); }
    .tab[data-type="performance"].active { border-color: var(--c-performance); color: var(--c-performance); }
    .tab[data-type="activity"].active { border-color: var(--c-activity);   color: var(--c-activity); }
    .tab[data-type="noscript"].active { border-color: var(--c-noscript);   color: var(--c-noscript); }

    /* ---------- TABLE WRAPPER ---------- */
    .table-wrapper {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      animation: fadeUp 0.5s ease 0.2s both;
      position: relative;
    }

    .table-wrapper::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg, var(--accent), var(--accent2));
    }

    .table-scroll { overflow-x: auto; }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }

    thead tr { border-bottom: 1px solid var(--border); }

    thead th {
      padding: 16px 20px;
      text-align: left;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      white-space: nowrap;
    }

    /* Data rows */
    tbody tr.data-row {
      border-bottom: 1px solid var(--border);
      cursor: pointer;
      transition: background 0.15s;
    }

    tbody tr.data-row:hover { background: var(--row-hover); }

    tbody tr.data-row.expanded { background: var(--row-hover); }

    /* Payload expand row */
    tbody tr.payload-row {
      display: none;
      border-bottom: 1px solid var(--border);
    }

    tbody tr.payload-row.open { display: table-row; }

    tbody tr.payload-row td {
      padding: 0;
    }

    .payload-inner {
      padding: 20px 24px;
      background: var(--surface2);
      border-top: 1px solid var(--border);
    }

    .payload-label {
      font-size: 10px;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 12px;
    }

    pre.payload-json {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--accent);
      white-space: pre-wrap;
      word-break: break-all;
      line-height: 1.7;
      max-height: 320px;
      overflow-y: auto;
    }

    /* Scrollbar for payload */
    pre.payload-json::-webkit-scrollbar { width: 4px; }
    pre.payload-json::-webkit-scrollbar-track { background: transparent; }
    pre.payload-json::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

    tbody td {
      padding: 14px 20px;
      color: var(--text);
      vertical-align: middle;
    }

    .col-id    { color: var(--muted); font-size: 11px; }
    .col-sid   { font-size: 11px; color: var(--accent2); }
    .col-time  { font-size: 11px; color: var(--muted); white-space: nowrap; }
    .col-page  { font-size: 11px; color: var(--text); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 10px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 500;
      border: 1px solid;
    }

    .badge-static      { color: var(--c-static);      border-color: rgba(0,102,255,0.35);   background: rgba(0,102,255,0.08); }
    .badge-performance { color: var(--c-performance); border-color: rgba(0,255,157,0.3);    background: rgba(0,255,157,0.06); }
    .badge-activity    { color: var(--c-activity);    border-color: rgba(255,107,53,0.35);  background: rgba(255,107,53,0.08); }
    .badge-noscript    { color: var(--c-noscript);    border-color: rgba(170,136,255,0.35); background: rgba(170,136,255,0.08); }

    .expand-icon {
      font-size: 10px;
      color: var(--muted);
      transition: transform 0.2s;
      display: inline-block;
    }

    .data-row.expanded .expand-icon { transform: rotate(90deg); }

    .null-val { color: var(--border); font-style: italic; }

    /* ---------- EMPTY ---------- */
    .empty {
      padding: 60px 20px;
      text-align: center;
      color: var(--muted);
      font-size: 13px;
    }

    /* ---------- FOOTER ---------- */
    footer {
      margin-top: 48px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      animation: fadeUp 0.5s ease 0.3s both;
    }

    .footer-left { font-size: 11px; color: var(--muted); letter-spacing: 0.05em; }
    .footer-left span { color: var(--accent); }
    .footer-right { font-size: 11px; color: var(--muted); }
  </style>
</head>
<!-- The body of the events page includes a header with a brand label and navigation, a page heading with a title and meta information, filter tabs to filter events by type, a table that lists event details from the database. 
 Each row can be clicked to expand and show the full JSON payload of the event. The page also includes a footer with site information and the current date. -->
<body>

<div class="page">

  <header>
    <div>
      <div class="brand-label">CSE135 · Analytics</div>
    </div>
    <div class="header-actions">
      <a href="/dashboard" class="btn-back">← Dashboard</a>
      <form class="logout-form" method="POST" action="/auth.php">
        <input type="hidden" name="action" value="logout">
        <button type="submit">Sign out →</button>
      </form>
    </div>
  </header>

  <div class="page-heading">
    <div class="page-eyebrow">Raw Data</div>
    <h1 class="page-title"><span>Events</span> Table</h1>
    <div class="page-meta">
      <strong><?= $total ?></strong> event<?= $total !== 1 ? 's' : '' ?> collected &nbsp;·&nbsp; click any row to expand payload
    </div>
  </div>

  <!-- Filter tabs -->
  <div class="filter-tabs">
    <button class="tab active" data-type="all">All</button>
    <button class="tab" data-type="static">Static</button>
    <button class="tab" data-type="performance">Performance</button>
    <button class="tab" data-type="activity">Activity</button>
    <button class="tab" data-type="noscript">Noscript</button>
  </div>

  <div class="table-wrapper">
    <div class="table-scroll">
      <?php if ($total === 0): ?>
        <div class="empty">No events collected yet.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>Session</th>
              <th>Type</th>
              <th>Server Time</th>
              <th>Page</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <!-- Data row -->
              <tr class="data-row" data-type="<?= htmlspecialchars($e['event_type']) ?>" data-id="<?= $e['id'] ?>">
                <td style="width:32px; padding-left:16px;">
                  <span class="expand-icon">▶</span>
                </td>
                <td class="col-id"><?= htmlspecialchars($e['id']) ?></td>
                <td class="col-sid"><?= htmlspecialchars($e['session_id']) ?></td>
                <td>
                  <span class="badge badge-<?= htmlspecialchars($e['event_type']) ?>">
                    <?= htmlspecialchars($e['event_type']) ?>
                  </span>
                </td>
                <td class="col-time"><?= htmlspecialchars($e['ts_server']) ?></td>
                <td class="col-page" title="<?= htmlspecialchars($e['page'] ?? '') ?>">
                  <?= $e['page'] ? htmlspecialchars($e['page']) : '<span class="null-val">—</span>' ?>
                </td>
              </tr>
              <!-- Payload expand row -->
              <tr class="payload-row" id="payload-<?= $e['id'] ?>">
                <td colspan="6">
                  <div class="payload-inner">
                    <div class="payload-label">Payload — event #<?= $e['id'] ?></div>
                    <pre class="payload-json"><?= htmlspecialchars(json_encode($e['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right"><?= date('D, d M Y') ?></div>
  </footer>

</div>

<script>
  // ---------- ROW EXPAND ----------
  document.querySelectorAll('.data-row').forEach(row => {
    row.addEventListener('click', () => {
      const id         = row.dataset.id;
      const payloadRow = document.getElementById('payload-' + id);
      const isOpen     = payloadRow.classList.contains('open');

      // Close all open rows first
      document.querySelectorAll('.payload-row.open').forEach(r => r.classList.remove('open'));
      document.querySelectorAll('.data-row.expanded').forEach(r => r.classList.remove('expanded'));

      // Toggle clicked row
      if (!isOpen) {
        payloadRow.classList.add('open');
        row.classList.add('expanded');
      }
    });
  });

  // ---------- FILTER TABS ----------
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      const type = tab.dataset.type;

      document.querySelectorAll('.data-row').forEach(row => {
        const show = type === 'all' || row.dataset.type === type;
        row.style.display = show ? '' : 'none';

        // Also hide open payload rows for hidden data rows
        const payloadRow = document.getElementById('payload-' + row.dataset.id);
        if (!show && payloadRow) {
          payloadRow.classList.remove('open');
          row.classList.remove('expanded');
          payloadRow.style.display = 'none';
        } else if (show && payloadRow) {
          payloadRow.style.display = '';
        }
      });
    });
  });
</script>

</body>
</html>