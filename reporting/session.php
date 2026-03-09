<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Sessions</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0a0a0f;
      --surface:   #111118;
      --border:    #1e1e2e;
      --accent:    #00ff9d;
      --accent2:   #0066ff;
      --text:      #e8e8f0;
      --muted:     #5a5a7a;
      --row-hover: #16161f;
    }

    html, body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Mono', monospace;
      min-height: 100vh;
    }

    body { position: relative; overflow-x: hidden; }

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

    .header-actions { display: flex; align-items: center; gap: 12px; }

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

    .btn-back:hover { border-color: var(--accent2); color: var(--accent2); }

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

    .page-title span { color: var(--accent2); }

    .page-meta { font-size: 12px; color: var(--muted); }
    .page-meta strong { color: var(--accent); font-weight: 500; }

    /* Loading / error states */
    .state-box {
      padding: 60px 20px;
      text-align: center;
      font-size: 12px;
      color: var(--muted);
    }

    .state-box.error { color: #ff4466; }

    .spinner {
      display: inline-block;
      width: 18px;
      height: 18px;
      border: 2px solid var(--border);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      margin-right: 10px;
      vertical-align: middle;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

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
      background: linear-gradient(90deg, var(--accent2), var(--accent));
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

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--row-hover); }

    tbody td {
      padding: 14px 20px;
      color: var(--text);
      vertical-align: middle;
    }

    .col-id    { color: var(--muted); font-size: 11px; }
    .col-sid   { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--accent2); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .col-time  { font-size: 11px; color: var(--muted); white-space: nowrap; }
    .col-ip    { font-size: 11px; color: var(--accent); white-space: nowrap; }
    .col-ua    { font-size: 11px; color: var(--muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .null-val  { color: var(--border); font-style: italic; }

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
<body>
<div class="page">

  <header>
    <div>
      <div class="brand-label">CSE135 · Analytics</div>
      <div class="brand-title">Data<span>Lens</span></div>
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
    <h1 class="page-title"><span>Sessions</span> Table</h1>
    <div class="page-meta" id="meta">Loading...</div>
  </div>

  <div class="table-wrapper">
    <div class="table-scroll">
      <div id="table-container">
        <div class="state-box"><span class="spinner"></span> Fetching sessions...</div>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right" id="date-footer"></div>
  </footer>

</div>
<script>
  // Set date
  document.getElementById('date-footer').textContent =
    new Date().toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

  function escHtml(str) {
    if (str === null || str === undefined) return '<span class="null-val">—</span>';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function loadSessions() {
    const container = document.getElementById('table-container');
    const meta      = document.getElementById('meta');

    try {
      const res  = await fetch('/api/sessions');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const sessions = await res.json();
      const total    = sessions.length;

      meta.innerHTML = `<strong>${total}</strong> session${total !== 1 ? 's' : ''} collected`;

      if (total === 0) {
        container.innerHTML = '<div class="state-box">No sessions collected yet.</div>';
        return;
      }

      let rows = '';
      for (const s of sessions) {
        rows += `
          <tr>
            <td class="col-id">${escHtml(s.id)}</td>
            <td class="col-sid" title="${escHtml(s.sid)}">${escHtml(s.sid)}</td>
            <td class="col-time">${escHtml(s.first_seen)}</td>
            <td class="col-time">${escHtml(s.last_seen)}</td>
            <td class="col-ip">${s.ip ? escHtml(s.ip) : '<span class="null-val">—</span>'}</td>
            <td class="col-ua" title="${escHtml(s.user_agent)}">${s.user_agent ? escHtml(s.user_agent) : '<span class="null-val">—</span>'}</td>
          </tr>`;
      }

      container.innerHTML = `
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>SID</th>
              <th>First Seen</th>
              <th>Last Seen</th>
              <th>IP Address</th>
              <th>User Agent</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>`;

    } catch (err) {
      meta.textContent = '';
      container.innerHTML = `<div class="state-box error">Failed to load sessions: ${err.message}</div>`;
    }
  }

  loadSessions();
</script>
</body>
</html>