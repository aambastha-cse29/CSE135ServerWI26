<?php
require_once 'auth_check.php';
require_once 'auth_helpers.php';
require_once 'db.php';

$pdo = getDB();

// Fetch all reports joined with username
$reports = $pdo->query("
    SELECT r.id, r.title, r.category, r.pdf_url, r.created_at,
           u.username
    FROM reports r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Saved Reports</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #0a0a0f;
      --surface: #111118;
      --border:  #1e1e2e;
      --accent:  #00ff9d;
      --accent2: #0066ff;
      --accent3: #ff6b35;
      --accent4: #aa88ff;
      --text:    #e8e8f0;
      --muted:   #5a5a7a;
    }

    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Mono', monospace;
    }

    body {
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
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
      max-width: 1100px;
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

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
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

    .btn-back:hover { border-color: var(--accent); color: var(--accent); }

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

    .logout-form button:hover { border-color: var(--accent); color: var(--accent); }

    /* ---------- PAGE HEADING ---------- */
    .page-heading {
      margin-bottom: 40px;
      animation: fadeUp 0.5s ease 0.1s both;
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
      font-size: clamp(28px, 4vw, 40px);
      font-weight: 800;
      letter-spacing: -0.02em;
      color: var(--text);
      margin-bottom: 10px;
    }

    .page-title span { color: var(--accent4); }

    .page-meta {
      font-size: 12px;
      color: var(--muted);
    }

    /* ---------- TABLE WRAPPER ---------- */
    .table-wrapper {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      animation: fadeUp 0.5s ease 0.2s both;
    }

    .table-scroll { overflow-x: auto; }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }

    thead th {
      padding: 16px 20px;
      text-align: left;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(255,255,255,0.02); }

    tbody td {
      padding: 16px 20px;
      color: var(--text);
      vertical-align: middle;
    }

    .col-id     { color: var(--muted); font-size: 11px; }
    .col-title  { font-weight: 500; }
    .col-author { color: var(--muted); font-size: 11px; }
    .col-date   { color: var(--muted); font-size: 11px; white-space: nowrap; }

    /* ---------- BADGES ---------- */
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

    .badge-sessions { color: var(--accent2); border-color: rgba(0,102,255,0.3);   background: rgba(0,102,255,0.08); }
    .badge-events   { color: var(--accent);  border-color: rgba(0,255,157,0.3);   background: rgba(0,255,157,0.06); }
    .badge-charts   { color: var(--accent3); border-color: rgba(255,107,53,0.3);  background: rgba(255,107,53,0.08); }

    /* ---------- VIEW LINK ---------- */
    .btn-view {
      display: inline-block;
      padding: 6px 16px;
      border: 1px solid var(--accent4);
      border-radius: 6px;
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--accent4);
      text-decoration: none;
      transition: background 0.2s, color 0.2s;
      letter-spacing: 0.04em;
    }

    .btn-view:hover {
      background: var(--accent4);
      color: var(--bg);
    }

    /* ---------- EMPTY STATE ---------- */
    .state-box {
      padding: 64px 32px;
      text-align: center;
      color: var(--muted);
      font-size: 13px;
    }

    /* ---------- FOOTER ---------- */
    footer {
      margin-top: 64px;
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
<body>
<div class="page">

  <header>
    <div>
      <div class="brand-label">CSE135 · Analytics</div>
      <div class="brand-title">Data<span>Lens</span></div>
    </div>
    <div class="header-actions">
      <a href="/dashboard" class="btn-back">← Dashboard</a>
      <form class="logout-form" method="POST" action="/auth">
        <input type="hidden" name="action" value="logout">
        <button type="submit">Sign Out →</button>
      </form>
    </div>
  </header>

  <div class="page-heading">
    <div class="page-eyebrow">Reports</div>
    <h1 class="page-title">Saved <span>Reports</span></h1>
    <div class="page-meta"><?= count($reports) ?> report<?= count($reports) !== 1 ? 's' : '' ?> available</div>
  </div>

  <div class="table-wrapper">
    <div class="table-scroll">
      <?php if (empty($reports)): ?>
        <div class="state-box">No reports have been saved yet.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Category</th>
              <th>Created By</th>
              <th>Date</th>
              <th>Report</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
              <td class="col-id"><?= htmlspecialchars($r['id']) ?></td>
              <td class="col-title"><?= htmlspecialchars($r['title']) ?></td>
              <td>
                <span class="badge badge-<?= htmlspecialchars($r['category']) ?>">
                  <?= htmlspecialchars($r['category']) ?>
                </span>
              </td>
              <td class="col-author"><?= htmlspecialchars($r['username']) ?></td>
              <td class="col-date"><?= htmlspecialchars($r['created_at']) ?></td>
              <td>
                <a href="<?= htmlspecialchars($r['pdf_url']) ?>" target="_blank" class="btn-view">
                  View PDF →
                </a>
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
</body>
</html>