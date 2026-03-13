<?php
require_once 'auth_check.php';
require_once 'auth_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Events</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0a0a0f;
      --surface:   #111118;
      --surface2:  #0d0d14;
      --border:    #1e1e2e;
      --accent:    #00ff9d;
      --accent2:   #0066ff;
      --accent3:   #ff6b35;
      --text:      #e8e8f0;
      --muted:     #5a5a7a;
      --row-hover: #16161f;
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

    .page-title span { color: var(--accent); }
    .page-meta { font-size: 12px; color: var(--muted); }
    .page-meta strong { color: var(--accent); font-weight: 500; }

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

    @keyframes spin { to { transform: rotate(360deg); } }

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

    .tab:hover { border-color: var(--muted); color: var(--text); }
    .tab[data-type="all"].active       { border-color: var(--text);            color: var(--text); }
    .tab[data-type="static"].active    { border-color: var(--c-static);        color: var(--c-static); }
    .tab[data-type="performance"].active { border-color: var(--c-performance); color: var(--c-performance); }
    .tab[data-type="activity"].active  { border-color: var(--c-activity);      color: var(--c-activity); }
    .tab[data-type="noscript"].active  { border-color: var(--c-noscript);      color: var(--c-noscript); }

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

    table { width: 100%; border-collapse: collapse; font-size: 12px; }
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

    tbody tr.data-row {
      border-bottom: 1px solid var(--border);
      cursor: pointer;
      transition: background 0.15s;
    }

    tbody tr.data-row:hover { background: var(--row-hover); }
    tbody tr.data-row.expanded { background: var(--row-hover); }

    tbody tr.payload-row { display: none; border-bottom: 1px solid var(--border); }
    tbody tr.payload-row.open { display: table-row; }
    tbody tr.payload-row td { padding: 0; }

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

    pre.payload-json::-webkit-scrollbar { width: 4px; }
    pre.payload-json::-webkit-scrollbar-track { background: transparent; }
    pre.payload-json::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

    tbody td { padding: 14px 20px; color: var(--text); vertical-align: middle; }

    .col-id   { color: var(--muted); font-size: 11px; }
    .col-sid  { font-size: 11px; color: var(--accent2); }
    .col-time { font-size: 11px; color: var(--muted); white-space: nowrap; }
    .col-page { font-size: 11px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

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

    .expand-icon { font-size: 10px; color: var(--muted); transition: transform 0.2s; display: inline-block; }
    .data-row.expanded .expand-icon { transform: rotate(90deg); }
    .null-val { color: var(--border); font-style: italic; }

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

    /* ---------- EXPORT PANEL ---------- */
    .export-panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 28px 32px;
      margin-top: 32px;
      animation: fadeUp 0.5s ease 0.3s both;
    }

    .export-title {
      font-size: 10px;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 16px;
    }

    .export-row {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .export-input {
      flex: 1;
      min-width: 200px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 14px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s;
    }

    .export-input:focus { border-color: var(--accent); }

    .btn-export {
      background: var(--accent);
      border: none;
      border-radius: 8px;
      padding: 10px 24px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--bg);
      font-weight: 500;
      cursor: pointer;
      transition: opacity 0.2s;
      letter-spacing: 0.05em;
      white-space: nowrap;
    }

    .btn-export:hover { opacity: 0.85; }
    .btn-export:disabled { opacity: 0.4; cursor: not-allowed; }

    .export-status {
      font-size: 12px;
      margin-top: 12px;
      min-height: 18px;
    }

    .export-status.success { color: var(--accent); }
    .export-status.error   { color: var(--accent3); }
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
    <h1 class="page-title"><span>Events</span> Table</h1>
    <div class="page-meta" id="meta">Loading...</div>
  </div>

  <div class="filter-tabs">
    <button class="tab active" data-type="all">All</button>
    <button class="tab" data-type="static">Static</button>
    <button class="tab" data-type="performance">Performance</button>
    <button class="tab" data-type="activity">Activity</button>
    <button class="tab" data-type="noscript">Noscript</button>
  </div>

  <div class="table-wrapper">
    <div class="table-scroll">
      <div id="table-container">
        <div class="state-box"><span class="spinner"></span> Fetching events...</div>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right" id="date-footer"></div>
  </footer>

  <?php if (canExport()): ?>
  <div class="export-panel">
    <div class="export-title">Export Report</div>
    <div class="export-row">
      <input type="text" class="export-input" id="export-title" placeholder="Report title e.g. Events Report March 2026">
      <button class="btn-export" id="export-btn" onclick="exportReport()">Export PDF →</button>
    </div>
    <div class="export-status" id="export-status"></div>
  </div>
  <?php endif; ?>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
  document.getElementById('date-footer').textContent =
    new Date().toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

  let allEvents  = [];
  let activeType = 'all';

  function nullCell(td) {
    const span = document.createElement('span');
    span.className   = 'null-val';
    span.textContent = '—';
    td.appendChild(span);
  }

  function renderTable(events) {
    const container = document.getElementById('table-container');
    container.innerHTML = '';

    if (events.length === 0) {
      const box = document.createElement('div');
      box.className   = 'state-box';
      box.textContent = 'No events found.';
      container.appendChild(box);
      return;
    }

    const table = document.createElement('table');

    // thead
    const thead = table.createTHead();
    const hrow  = thead.insertRow();
    ['', 'ID', 'Session', 'Type', 'Server Time', 'Page'].forEach(h => {
      const th = document.createElement('th');
      th.textContent = h;
      hrow.appendChild(th);
    });

    const tbody = table.createTBody();

    for (const e of events) {
      // --- data row ---
      const dataRow = tbody.insertRow();
      dataRow.className      = 'data-row';
      dataRow.dataset.id     = e.id;
      dataRow.dataset.type   = e.event_type || '';

      // expand icon cell
      const iconTd = dataRow.insertCell();
      iconTd.style.cssText = 'width:32px; padding-left:16px;';
      const icon = document.createElement('span');
      icon.className   = 'expand-icon';
      icon.textContent = '▶';
      iconTd.appendChild(icon);

      // id
      const idTd = dataRow.insertCell();
      idTd.className   = 'col-id';
      idTd.textContent = e.id;

      // session_id
      const sidTd = dataRow.insertCell();
      sidTd.className   = 'col-sid';
      sidTd.textContent = e.session_id;

      // event_type badge
      const typeTd = dataRow.insertCell();
      const badge  = document.createElement('span');
      const safeType = ['static','performance','activity','noscript'].includes(e.event_type) ? e.event_type : 'static';
      badge.className   = `badge badge-${safeType}`;
      badge.textContent = e.event_type || '';
      typeTd.appendChild(badge);

      // ts_server
      const timeTd = dataRow.insertCell();
      timeTd.className   = 'col-time';
      timeTd.textContent = e.ts_server || '';

      // page
      const pageTd = dataRow.insertCell();
      pageTd.className = 'col-page';
      if (e.page) {
        pageTd.textContent = e.page;
        pageTd.title       = e.page;
      } else {
        nullCell(pageTd);
      }

      // --- payload row ---
      const payloadRow = tbody.insertRow();
      payloadRow.className = 'payload-row';
      payloadRow.id        = `payload-${e.id}`;

      const payloadTd = payloadRow.insertCell();
      payloadTd.colSpan = 6;

      const inner = document.createElement('div');
      inner.className = 'payload-inner';

      const label = document.createElement('div');
      label.className   = 'payload-label';
      label.textContent = `Payload — event #${e.id}`;

      const pre = document.createElement('pre');
      pre.className = 'payload-json';
      // JSON.stringify is safe — textContent prevents any HTML injection
      pre.textContent = JSON.stringify(e.payload || {}, null, 2);

      inner.appendChild(label);
      inner.appendChild(pre);
      payloadTd.appendChild(inner);

      // expand click handler
      dataRow.addEventListener('click', () => {
        const isOpen = payloadRow.classList.contains('open');
        tbody.querySelectorAll('.payload-row.open').forEach(r => r.classList.remove('open'));
        tbody.querySelectorAll('.data-row.expanded').forEach(r => r.classList.remove('expanded'));
        if (!isOpen) {
          payloadRow.classList.add('open');
          dataRow.classList.add('expanded');
        }
      });
    }

    container.appendChild(table);
  }

  function applyFilter(type) {
    activeType = type;
    const filtered = type === 'all' ? allEvents : allEvents.filter(e => e.event_type === type);
    renderTable(filtered);
    updateMeta();
  }

  function updateMeta() {
    const filtered = activeType === 'all' ? allEvents : allEvents.filter(e => e.event_type === activeType);
    const total    = filtered.length;
    // meta uses static markup only, no analytics data in HTML tags
    document.getElementById('meta').innerHTML =
      `<strong>${total}</strong> event${total !== 1 ? 's' : ''} · click any row to expand payload`;
  }

  async function loadEvents() {
    const container = document.getElementById('table-container');

    try {
      const res = await fetch('/api/events');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      allEvents = await res.json();

      // Fetch full payload for each event
      const payloadFetches = allEvents.map(async (e) => {
        try {
          const r = await fetch(`/api/events/${e.id}`);
          if (r.ok) {
            const full = await r.json();
            e.payload = full.payload;
          }
        } catch (_) {}
      });

      await Promise.all(payloadFetches);

      updateMeta();
      renderTable(allEvents);

    } catch (err) {
      document.getElementById('meta').textContent = '';
      container.textContent = `Failed to load events: ${err.message}`;
      container.className   = 'state-box error';
    }
  }

  // Filter tabs
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      applyFilter(tab.dataset.type);
    });
  });

  loadEvents();

  async function exportReport() {
    const title  = document.getElementById('export-title').value.trim();
    const status = document.getElementById('export-status');
    const btn    = document.getElementById('export-btn');

    if (!title) {
      status.textContent = 'Please enter a report title.';
      status.className   = 'export-status error';
      return;
    }

    btn.disabled       = true;
    btn.textContent    = 'Capturing...';
    status.textContent = '';
    status.className   = 'export-status';

    try {
      const canvas = await html2canvas(document.querySelector('.page'), {
        backgroundColor: '#0a0a0f',
        scale: 2,
        useCORS: true,
      });

      const image = canvas.toDataURL('image/png');

      btn.textContent = 'Generating PDF...';

      const formData = new FormData();
      formData.append('title',    title);
      formData.append('category', 'events');
      formData.append('image',    image);

      const res  = await fetch('/export_action', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        status.textContent = 'Report saved successfully.';
        status.className   = 'export-status success';
        window.open(data.pdf_url, '_blank');
      } else {
        status.textContent = 'Export failed: ' + data.error;
        status.className   = 'export-status error';
      }

    } catch (err) {
      status.textContent = 'Export failed: ' + err.message;
      status.className   = 'export-status error';
    } finally {
      btn.disabled    = false;
      btn.textContent = 'Export PDF →';
    }
  }
</script>
</body>
</html>