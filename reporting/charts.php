<?php
require_once 'auth_check.php';
require_once 'auth_helpers.php';

// Viewers cannot access charts
if (isViewer()) {
    header('Location: /403');
    exit;
}

$isSuperAdmin = isSuperAdmin();
$userSections = $isSuperAdmin ? null : ($_SESSION['sections'] ?? []);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Charts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0a0a0f;
      --bg2:     #0d0d14;
      --surface: #111118;
      --border:  #1e1e2e;
      --accent:  #00ff9d;
      --accent2: #0066ff;
      --accent3: #ff6b35;
      --accent4: #aa88ff;
      --text:    #e8e8f0;
      --muted:   #5a5a7a;
    }
    html, body { background: var(--bg); color: var(--text); font-family: 'DM Mono', monospace; min-height: 100vh; }
    body { position: relative; overflow-x: hidden; }
    body::before {
      content: ''; position: fixed; inset: 0;
      background-image: linear-gradient(rgba(0,255,157,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(0,255,157,0.025) 1px, transparent 1px);
      background-size: 40px 40px; animation: gridMove 20s linear infinite; pointer-events: none; z-index: 0;
    }
    @keyframes gridMove { 0% { transform: translateY(0); } 100% { transform: translateY(40px); } }
    .page { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 48px 32px; }
    header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 48px; animation: fadeDown 0.5s ease both; }
    @keyframes fadeDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
    .brand-label { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 0.3em; text-transform: uppercase; color: var(--accent); margin-bottom: 6px; }
    .brand-title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--text); letter-spacing: -0.02em; }
    .brand-title span { color: var(--accent); }
    .header-actions { display: flex; align-items: center; gap: 12px; }
    .btn-back { background: transparent; border: 1px solid var(--border); border-radius: 8px; padding: 10px 20px; font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); text-decoration: none; transition: border-color 0.2s, color 0.2s; letter-spacing: 0.05em; }
    .btn-back:hover { border-color: var(--accent2); color: var(--accent2); }
    .logout-form button { background: transparent; border: 1px solid var(--border); border-radius: 8px; padding: 10px 20px; font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); cursor: pointer; transition: border-color 0.2s, color 0.2s; letter-spacing: 0.05em; }
    .logout-form button:hover { border-color: var(--accent); color: var(--accent); }
    .page-heading { margin-bottom: 32px; animation: fadeUp 0.5s ease 0.1s both; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .page-eyebrow { font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
    .page-title { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; color: var(--text); letter-spacing: -0.02em; }
    .page-title span { color: var(--accent3); }

    /* TAB NAV */
    .tab-nav { display: flex; gap: 4px; margin-bottom: 40px; border-bottom: 1px solid var(--border); animation: fadeUp 0.5s ease 0.15s both; }
    .tab-btn { background: transparent; border: none; border-bottom: 2px solid transparent; padding: 10px 20px; font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); cursor: pointer; letter-spacing: 0.08em; text-transform: uppercase; transition: color 0.2s, border-color 0.2s; margin-bottom: -1px; }
    .tab-btn:hover { color: var(--text); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fadeUp 0.3s ease both; }

    /* KPI CARDS */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; }
    .kpi-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px 24px; position: relative; overflow: hidden; }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
    .kpi-card.kpi-lcp::before  { background: linear-gradient(90deg, var(--accent), var(--accent2)); }
    .kpi-card.kpi-inp::before  { background: linear-gradient(90deg, var(--accent4), var(--accent3)); }
    .kpi-card.kpi-cls::before  { background: linear-gradient(90deg, var(--accent2), var(--accent4)); }
    .kpi-card.kpi-ttfb::before { background: linear-gradient(90deg, var(--accent3), var(--accent)); }
    .kpi-label { font-size: 9px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
    .kpi-value { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
    .kpi-value.good { color: var(--accent); }
    .kpi-value.poor { color: var(--accent3); }
    .kpi-value.warn { color: #ffcc00; }
    .kpi-sub { font-size: 10px; color: var(--muted); }

    /* CHARTS */
    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 24px; }
    .chart-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 32px; position: relative; overflow: hidden; }
    .chart-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
    .chart-card.card-browser::before  { background: linear-gradient(90deg, var(--accent2), var(--accent)); }
    .chart-card.card-sessions::before { background: linear-gradient(90deg, var(--accent), var(--accent2)); }
    .chart-card.card-lcp::before      { background: linear-gradient(90deg, var(--accent), var(--accent3)); }
    .chart-card.card-inp::before      { background: linear-gradient(90deg, var(--accent4), var(--accent3)); }
    .chart-card.card-pages::before    { background: linear-gradient(90deg, var(--accent3), var(--accent4)); }
    .chart-card.card-idle::before     { background: linear-gradient(90deg, var(--accent4), var(--accent2)); }
    .kpi-card.kpi-activity-top::before  { background: linear-gradient(90deg, var(--accent3), var(--accent4)); }
    .kpi-card.kpi-activity-time::before { background: linear-gradient(90deg, var(--accent4), var(--accent2)); }
    .kpi-card.kpi-activity-idle::before { background: linear-gradient(90deg, var(--accent2), var(--accent3)); }
    .chart-title { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .chart-subtitle { font-size: 11px; color: var(--muted); letter-spacing: 0.04em; margin-bottom: 8px; }
    .chart-meta { font-size: 11px; color: var(--muted); margin-bottom: 24px; }
    .chart-meta strong { color: var(--accent); font-weight: 500; }
    .state-box { padding: 40px 0; text-align: center; font-size: 12px; color: var(--muted); }
    .state-box.error { color: #ff4466; }
    .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.7s linear infinite; margin-right: 8px; vertical-align: middle; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .vitals-note { margin-top: 20px; padding: 14px 16px; background: rgba(0,255,157,0.05); border: 1px solid rgba(0,255,157,0.15); border-radius: 8px; font-size: 11px; color: var(--muted); line-height: 1.7; }
    .vitals-note .pct { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; display: block; margin-bottom: 4px; }
    .vitals-note .pct.good { color: var(--accent); }
    .vitals-note .pct.poor { color: var(--accent3); }

    /* FOOTER */
    footer { margin-top: 64px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; animation: fadeUp 0.5s ease 0.3s both; }
    .footer-left { font-size: 11px; color: var(--muted); letter-spacing: 0.05em; }
    .footer-left span { color: var(--accent); }
    .footer-right { font-size: 11px; color: var(--muted); }

    /* EXPORT PANEL */
    .export-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px 32px; margin-top: 32px; animation: fadeUp 0.5s ease 0.3s both; }
    .export-title { font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
    .export-row { display: flex; gap: 12px; align-items: center; }
    .export-input { flex: 1; background: var(--bg2); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; font-family: 'DM Mono', monospace; font-size: 12px; color: var(--text); outline: none; transition: border-color 0.2s; }
    .export-input:focus { border-color: var(--accent); }
    .export-input::placeholder { color: var(--muted); }
    .btn-export { background: var(--accent); border: none; border-radius: 8px; padding: 10px 24px; font-family: 'DM Mono', monospace; font-size: 12px; color: var(--bg); font-weight: 500; cursor: pointer; transition: opacity 0.2s; letter-spacing: 0.05em; white-space: nowrap; }
    .btn-export:hover { opacity: 0.85; }
    .btn-export:disabled { opacity: 0.4; cursor: not-allowed; }
    .export-comments { width: 100%; margin-top: 14px; padding: 12px 14px; background: var(--bg2); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: inherit; font-size: 12px; resize: vertical; min-height: 80px; outline: none; transition: border-color 0.2s; }
    .export-comments:focus { border-color: var(--accent); }
    .export-comments::placeholder { color: var(--muted); }
    .export-status { font-size: 12px; margin-top: 12px; min-height: 18px; }
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
        <button type="submit">Sign Out →</button>
      </form>
    </div>
  </header>

  <div class="page-heading">
    <div class="page-eyebrow">Visual Analytics</div>
    <div class="page-title">Charts &amp; <span>Insights</span></div>
  </div>

  <nav class="tab-nav" id="tab-nav">
    <?php if ($isSuperAdmin || in_array('performance', $userSections ?? [])): ?>
    <button class="tab-btn" data-tab="performance">Performance</button>
    <?php endif; ?>
    <?php if ($isSuperAdmin || in_array('traffic', $userSections ?? [])): ?>
    <button class="tab-btn" data-tab="traffic">Traffic</button>
    <?php endif; ?>
    <?php if ($isSuperAdmin || in_array('activity', $userSections ?? [])): ?>
    <button class="tab-btn" data-tab="activity">Activity</button>
    <?php endif; ?>
  </nav>

  <?php if ($isSuperAdmin || in_array('performance', $userSections ?? [])): ?>
  <div class="tab-panel" id="panel-performance">
    <div class="kpi-grid">
      <div class="kpi-card kpi-lcp">
        <div class="kpi-label">Median LCP</div>
        <div class="kpi-value" id="kpi-lcp">—</div>
        <div class="kpi-sub">Largest Contentful Paint</div>
      </div>
      <div class="kpi-card kpi-inp">
        <div class="kpi-label">Median INP</div>
        <div class="kpi-value" id="kpi-inp">—</div>
        <div class="kpi-sub">Interaction To Next Paint</div>
      </div>
      <div class="kpi-card kpi-cls">
        <div class="kpi-label">Median CLS</div>
        <div class="kpi-value" id="kpi-cls">—</div>
        <div class="kpi-sub">Cumulative Layout Shift</div>
      </div>
      <div class="kpi-card kpi-ttfb">
        <div class="kpi-label">Median TTFB</div>
        <div class="kpi-value" id="kpi-ttfb">—</div>
        <div class="kpi-sub">Time To First Byte</div>
      </div>
    </div>
    <div class="charts-grid">
      <div class="chart-card card-lcp">
        <div class="chart-title">LCP Distribution</div>
        <div class="chart-subtitle">Core Web Vital · Largest Contentful Paint</div>
        <div class="chart-meta" id="lcp-meta"><span class="spinner"></span> Loading...</div>
        <div id="lcp-chart-container"><canvas id="lcpChart"></canvas></div>
        <div id="lcp-note"></div>
      </div>
      <div class="chart-card card-inp">
        <div class="chart-title">INP Distribution</div>
        <div class="chart-subtitle">Core Web Vital · Interaction To Next Paint</div>
        <div class="chart-meta" id="inp-meta"><span class="spinner"></span> Loading...</div>
        <div id="inp-chart-container"><canvas id="inpChart"></canvas></div>
        <div id="inp-note"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($isSuperAdmin || in_array('traffic', $userSections ?? [])): ?>
  <div class="tab-panel" id="panel-traffic">
    <div class="charts-grid">
      <div class="chart-card card-browser">
        <div class="chart-title">Browser Share</div>
        <div class="chart-subtitle">Sessions By Browser Type</div>
        <div class="chart-meta" id="browser-meta"><span class="spinner"></span> Loading...</div>
        <div id="browser-chart-container"><canvas id="browserChart"></canvas></div>
      </div>
      <div class="chart-card card-sessions">
        <div class="chart-title">Sessions By Day</div>
        <div class="chart-subtitle">Daily Session Volume Over Time</div>
        <div class="chart-meta" id="sessions-day-meta"><span class="spinner"></span> Loading...</div>
        <div id="sessions-day-container"><canvas id="sessionsDayChart"></canvas></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($isSuperAdmin || in_array('activity', $userSections ?? [])): ?>
  <div class="tab-panel" id="panel-activity">
    <div class="kpi-grid">
      <div class="kpi-card kpi-activity-top">
        <div class="kpi-label">Most Visited Page</div>
        <div class="kpi-value" id="kpi-top-page" style="font-size:14px;word-break:break-all;">—</div>
        <div class="kpi-sub" id="kpi-top-page-count"></div>
      </div>
      <div class="kpi-card kpi-activity-time">
        <div class="kpi-label">Median Time On Page</div>
        <div class="kpi-value" id="kpi-avg-time">—</div>
        <div class="kpi-sub">Across All Activity Events</div>
      </div>
      <div class="kpi-card kpi-activity-idle">
        <div class="kpi-label">Median Idle Duration</div>
        <div class="kpi-value" id="kpi-avg-idle">—</div>
        <div class="kpi-sub">Per Idle Period Recorded</div>
      </div>
    </div>
    <div class="charts-grid">
      <div class="chart-card card-pages">
        <div class="chart-title">Most Viewed Pages</div>
        <div class="chart-subtitle">Activity Events By Page URL</div>
        <div class="chart-meta" id="pages-meta"><span class="spinner"></span> Loading...</div>
        <div id="pages-chart-container"><canvas id="pagesChart"></canvas></div>
      </div>
      <div class="chart-card card-idle">
        <div class="chart-title">Idle Period Distribution</div>
        <div class="chart-subtitle">Duration Of Idle Periods Across Sessions</div>
        <div class="chart-meta" id="idle-meta"><span class="spinner"></span> Loading...</div>
        <div id="idle-chart-container"><canvas id="idleChart"></canvas></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right" id="date-footer"></div>
  </footer>

  <?php if (canExport()): ?>
  <div class="export-panel">
    <div class="export-title">Export Report</div>
    <div class="export-row">
      <input type="text" class="export-input" id="export-title-input" placeholder="Report title e.g. Performance Report March 2026">
      <button class="btn-export" id="export-btn" onclick="exportReport()">Export PDF →</button>
    </div>
    <textarea class="export-comments" id="export-comments" placeholder="Analyst/Superadmin comments (optional) — included in the PDF…"></textarea>
    <div class="export-status" id="export-status"></div>
  </div>
  <?php endif; ?>

</div>
<script>
  document.getElementById('date-footer').textContent =
    new Date().toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

  // ── Tab routing ──────────────────────────────────────────────────────────
  const tabs   = document.querySelectorAll('.tab-btn');
  const panels = document.querySelectorAll('.tab-panel');

  function showTab(name) {
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    panels.forEach(p => p.classList.toggle('active', p.id === 'panel-' + name));
    window.location.hash = name;
  }

  tabs.forEach(t => t.addEventListener('click', () => { showTab(t.dataset.tab); loadTabCharts(t.dataset.tab); }));

  const hashTab  = window.location.hash.replace('#', '');
  const firstTab = tabs[0]?.dataset.tab;
  showTab(hashTab && document.getElementById('panel-' + hashTab) ? hashTab : firstTab);

  // ── Helpers ──────────────────────────────────────────────────────────────
  function median(arr) {
    if (!arr.length) return null;
    const s = [...arr].sort((a, b) => a - b);
    const m = Math.floor(s.length / 2);
    return s.length % 2 !== 0 ? s[m] : (s[m - 1] + s[m]) / 2;
  }

  function buildVitalsNote(container, pct, goodThreshold, metricName) {
    const isGood  = parseFloat(pct) >= goodThreshold;
    const note    = document.createElement('div');
    note.className = 'vitals-note';
    const pctSpan = document.createElement('span');
    pctSpan.className   = `pct ${isGood ? 'good' : 'poor'}`;
    pctSpan.textContent = `${pct}%`;
    const desc = document.createElement('span');
    desc.textContent = ` of measurements have good ${metricName}.`;
    const br        = document.createElement('br');
    const threshold = document.createElement('span');
    threshold.textContent = `Google's Core Web Vitals threshold: good ${metricName} for ≥ ${goodThreshold}% of page loads.`;
    note.appendChild(pctSpan); note.appendChild(desc);
    note.appendChild(br);      note.appendChild(threshold);
    container.appendChild(note);
  }

  // ── Shared perf event cache ───────────────────────────────────────────────
  let perfEventsCache = null;
  async function getPerfEvents() {
    if (perfEventsCache) return perfEventsCache;
    const res    = await fetch('/api/events/performance');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const events = await res.json();
    await Promise.all(events.map(async (e) => {
      try {
        const r = await fetch(`/api/events/${e.id}`);
        if (r.ok) { const full = await r.json(); e.payload = full.payload; }
      } catch (_) {}
    }));
    perfEventsCache = events;
    return events;
  }

  // ── KPI Cards ────────────────────────────────────────────────────────────
  async function loadKpis() {
    try {
      const events   = await getPerfEvents();
      const lcpVals  = events.map(e => e.payload?.webVitals?.lcp).filter(v => v != null);
      const inpVals  = events.map(e => e.payload?.webVitals?.inp).filter(v => v != null);
      const clsVals  = events.map(e => e.payload?.webVitals?.cls).filter(v => v != null);
      const ttfbVals = events.map(e => e.payload?.ttfb).filter(v => v != null);

      function setKpi(id, value, unit, good, poor) {
        const el = document.getElementById(id);
        if (value === null) { el.textContent = '—'; return; }
        el.textContent = value + unit;
        el.classList.add(value <= good ? 'good' : value >= poor ? 'poor' : 'warn');
      }

      setKpi('kpi-lcp',  Math.round(median(lcpVals)),  'ms', 2500, 4000);
      setKpi('kpi-inp',  Math.round(median(inpVals)),  'ms', 200,  500);
      setKpi('kpi-cls',  median(clsVals) !== null ? parseFloat(median(clsVals).toFixed(3)) : null, '', 0.1, 0.25);
      setKpi('kpi-ttfb', Math.round(median(ttfbVals)), 'ms', 800, 1800);
    } catch (_) {
      ['kpi-lcp','kpi-inp','kpi-cls','kpi-ttfb'].forEach(id => { document.getElementById(id).textContent = 'err'; });
    }
  }

  // ── LCP Chart ────────────────────────────────────────────────────────────
  async function loadLcpChart() {
    const meta = document.getElementById('lcp-meta');
    const cont = document.getElementById('lcp-chart-container');
    const note = document.getElementById('lcp-note');
    try {
      const events = await getPerfEvents();
      const vals   = events.map(e => e.payload?.webVitals?.lcp).filter(v => v != null);
      const total  = vals.length;
      const good   = vals.filter(v => v < 2500).length;
      const bad    = total - good;
      const pct    = total > 0 ? ((good / total) * 100).toFixed(1) : 0;
      meta.innerHTML = `<strong>${total}</strong> page load${total !== 1 ? 's' : ''} measured`;
      if (total === 0) { cont.innerHTML = '<div class="state-box">No LCP data yet.</div>'; return; }
      new Chart(document.getElementById('lcpChart'), {
        type: 'bar',
        data: { labels: ['LCP'], datasets: [
          { label: 'Good (< 2.5s)',              data: [good], backgroundColor: 'rgba(0,255,157,0.75)',  borderColor: 'rgba(0,255,157,0.9)',  borderWidth: 1, borderRadius: 4 },
          { label: 'Needs Improvement (≥ 2.5s)', data: [bad],  backgroundColor: 'rgba(255,107,53,0.75)', borderColor: 'rgba(255,107,53,0.9)', borderWidth: 1, borderRadius: 4 }
        ]},
        options: {
          indexAxis: 'y', responsive: true,
          scales: { x: { stacked: true, grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } }, y: { stacked: true, grid: { display: false } } },
          plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.x} (${((ctx.parsed.x/total)*100).toFixed(1)}%)` } } }
        }
      });
      buildVitalsNote(note, pct, 75, 'LCP');
    } catch (err) { meta.textContent = ''; cont.innerHTML = `<div class="state-box error">Failed to load: ${err.message}</div>`; }
  }

  // ── INP Chart ────────────────────────────────────────────────────────────
  async function loadInpChart() {
    const meta = document.getElementById('inp-meta');
    const cont = document.getElementById('inp-chart-container');
    const note = document.getElementById('inp-note');
    try {
      const events = await getPerfEvents();
      const vals   = events.map(e => e.payload?.webVitals?.inp).filter(v => v != null);
      const total  = vals.length;
      const good   = vals.filter(v => v < 200).length;
      const needs  = vals.filter(v => v >= 200 && v < 500).length;
      const poor   = total - good - needs;
      const pct    = total > 0 ? ((good / total) * 100).toFixed(1) : 0;
      meta.innerHTML = `<strong>${total}</strong> interaction${total !== 1 ? 's' : ''} measured`;
      if (total === 0) { cont.innerHTML = '<div class="state-box">No INP data yet.</div>'; return; }
      new Chart(document.getElementById('inpChart'), {
        type: 'bar',
        data: { labels: ['INP'], datasets: [
          { label: 'Good (< 200ms)',                data: [good],  backgroundColor: 'rgba(0,255,157,0.75)',  borderColor: 'rgba(0,255,157,0.9)',  borderWidth: 1, borderRadius: 4 },
          { label: 'Needs Improvement (200–500ms)', data: [needs], backgroundColor: 'rgba(255,204,0,0.75)',  borderColor: 'rgba(255,204,0,0.9)',  borderWidth: 1, borderRadius: 4 },
          { label: 'Poor (≥ 500ms)',                data: [poor],  backgroundColor: 'rgba(255,107,53,0.75)', borderColor: 'rgba(255,107,53,0.9)', borderWidth: 1, borderRadius: 4 }
        ]},
        options: {
          indexAxis: 'y', responsive: true,
          scales: { x: { stacked: true, grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } }, y: { stacked: true, grid: { display: false } } },
          plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.x} (${((ctx.parsed.x/total)*100).toFixed(1)}%)` } } }
        }
      });
      buildVitalsNote(note, pct, 75, 'INP');
    } catch (err) { meta.textContent = ''; cont.innerHTML = `<div class="state-box error">Failed to load: ${err.message}</div>`; }
  }

  // ── Browser Share Chart ──────────────────────────────────────────────────
  async function loadBrowserChart() {
    const meta = document.getElementById('browser-meta');
    const cont = document.getElementById('browser-chart-container');
    try {
      const res      = await fetch('/api/sessions');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const sessions = await res.json();
      const browsers = { Chrome: 0, Firefox: 0, Safari: 0, Edge: 0, Other: 0 };
      for (const s of sessions) {
        const ua = s.user_agent || '';
        if      (ua.includes('Edg/') || ua.includes('Edge/'))          browsers.Edge++;
        else if (ua.includes('Firefox/'))                               browsers.Firefox++;
        else if (ua.includes('Chrome/') || ua.includes('CriOS/'))      browsers.Chrome++;
        else if (ua.includes('Safari/') || ua.includes('FxiOS/'))      browsers.Safari++;
        else                                                            browsers.Other++;
      }
      const labels = Object.keys(browsers).filter(k => browsers[k] > 0);
      const data   = labels.map(k => browsers[k]);
      const total  = data.reduce((a, b) => a + b, 0);
      meta.innerHTML = `<strong>${total}</strong> session${total !== 1 ? 's' : ''} analyzed`;
      if (total === 0) { cont.innerHTML = '<div class="state-box">No session data yet.</div>'; return; }
      new Chart(document.getElementById('browserChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Sessions', data, backgroundColor: ['rgba(0,102,255,0.8)','rgba(0,255,157,0.75)','rgba(255,107,53,0.8)','rgba(170,136,255,0.8)','rgba(90,90,122,0.6)'], borderColor: '#0a0a0f', borderWidth: 2, borderRadius: 6 }] },
        options: { responsive: true, scales: { x: { grid: { color: 'rgba(30,30,46,0.8)' } }, y: { grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.parsed.y} session${ctx.parsed.y !== 1 ? 's' : ''} (${((ctx.parsed.y/total)*100).toFixed(1)}%)` } } } }
      });
    } catch (err) { meta.textContent = ''; cont.innerHTML = `<div class="state-box error">Failed to load: ${err.message}</div>`; }
  }

  // ── Sessions by Day Chart ────────────────────────────────────────────────
  async function loadSessionsByDayChart() {
    const meta = document.getElementById('sessions-day-meta');
    const cont = document.getElementById('sessions-day-container');
    try {
      const res      = await fetch('/api/sessions');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const sessions = await res.json();
      const counts   = {};
      for (const s of sessions) {
        const day = s.first_seen ? s.first_seen.substring(0, 10) : null;
        if (!day) continue;
        counts[day] = (counts[day] || 0) + 1;
      }
      const sortedDays = Object.keys(counts).sort();
      const data       = sortedDays.map(d => counts[d]);
      const total      = data.reduce((a, b) => a + b, 0);
      meta.innerHTML = `<strong>${total}</strong> session${total !== 1 ? 's' : ''} over <strong>${sortedDays.length}</strong> day${sortedDays.length !== 1 ? 's' : ''}`;
      if (total === 0) { cont.innerHTML = '<div class="state-box">No session data yet.</div>'; return; }
      new Chart(document.getElementById('sessionsDayChart'), {
        type: 'line',
        data: { labels: sortedDays, datasets: [{ label: 'Sessions', data, borderColor: 'rgba(0,255,157,0.9)', backgroundColor: 'rgba(0,255,157,0.08)', borderWidth: 2, pointBackgroundColor: 'rgba(0,255,157,0.9)', pointRadius: 4, tension: 0.3, fill: true }] },
        options: { responsive: true, scales: { x: { grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { maxRotation: 45, font: { size: 10 } } }, y: { grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 }, beginAtZero: true } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.parsed.y} session${ctx.parsed.y !== 1 ? 's' : ''}` } } } }
      });
    } catch (err) { meta.textContent = ''; cont.innerHTML = `<div class="state-box error">Failed to load: ${err.message}</div>`; }
  }

  // ── Activity events cache ────────────────────────────────────────────────
  let activityEventsCache = null;
  async function getActivityEvents() {
    if (activityEventsCache) return activityEventsCache;
    const res    = await fetch('/api/events/activity');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const events = await res.json();
    await Promise.all(events.map(async (e) => {
      try {
        const r = await fetch(`/api/events/${e.id}`);
        if (r.ok) { const full = await r.json(); e.payload = full.payload; }
      } catch (_) {}
    }));
    activityEventsCache = events;
    return events;
  }

  // ── Activity KPIs ────────────────────────────────────────────────────────
  async function loadActivityKpis(events) {
    // Most visited page
    const pageCounts = {};
    for (const e of events) {
      const page = e.payload?.page || e.page;
      if (!page) continue;
      pageCounts[page] = (pageCounts[page] || 0) + 1;
    }
    const sortedPages = Object.entries(pageCounts).sort((a, b) => b[1] - a[1]);
    if (sortedPages.length > 0) {
      const [topPage, topCount] = sortedPages[0];
      const el = document.getElementById('kpi-top-page');
      // Strip origin for display
      try { el.textContent = new URL(topPage).pathname || topPage; } catch { el.textContent = topPage; }
      document.getElementById('kpi-top-page-count').textContent = `${topCount} event${topCount !== 1 ? 's' : ''}`;
    }

    // Avg time on page (ms → seconds)
    const timesMs = events.map(e => e.payload?.timeOnPageMs).filter(v => v != null && v > 0);
    const avgTime = timesMs.length > 0 ? median(timesMs) : null;
    const timeEl  = document.getElementById('kpi-avg-time');
    if (avgTime !== null) {
      timeEl.textContent = avgTime >= 60000
        ? (avgTime / 60000).toFixed(1) + 'm'
        : Math.round(avgTime / 1000) + 's';
    }

    // Avg idle duration
    const idleDurations = events.flatMap(e => (e.payload?.idlePeriods || []).map(p => p.durationMs)).filter(v => v != null);
    const avgIdle = idleDurations.length > 0 ? median(idleDurations) : null;
    const idleEl  = document.getElementById('kpi-avg-idle');
    if (avgIdle !== null) {
      idleEl.textContent = avgIdle >= 60000
        ? (avgIdle / 60000).toFixed(1) + 'm'
        : Math.round(avgIdle / 1000) + 's';
    }
  }

  // ── Most Viewed Pages Chart ──────────────────────────────────────────────
  async function loadPagesChart() {
    const meta = document.getElementById('pages-meta');
    const cont = document.getElementById('pages-chart-container');
    try {
      const events = await getActivityEvents();
      await loadActivityKpis(events);

      const pageCounts = {};
      for (const e of events) {
        const page = e.payload?.page || e.page;
        if (!page) continue;
        let label;
        try { label = new URL(page).pathname || '/'; } catch { label = page; }
        pageCounts[label] = (pageCounts[label] || 0) + 1;
      }

      const sorted  = Object.entries(pageCounts).sort((a, b) => b[1] - a[1]).slice(0, 10);
      const labels  = sorted.map(([p]) => p);
      const data    = sorted.map(([, c]) => c);
      const total   = data.reduce((a, b) => a + b, 0);

      meta.innerHTML = `<strong>${total}</strong> activity event${total !== 1 ? 's' : ''} across <strong>${labels.length}</strong> page${labels.length !== 1 ? 's' : ''}`;

      if (total === 0) { cont.innerHTML = '<div class="state-box">No activity data yet.</div>'; return; }

      new Chart(document.getElementById('pagesChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Events', data, backgroundColor: 'rgba(255,107,53,0.75)', borderColor: 'rgba(255,107,53,0.9)', borderWidth: 1, borderRadius: 4 }] },
        options: {
          indexAxis: 'y', responsive: true,
          scales: {
            x: { grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } },
            y: { grid: { display: false }, ticks: { font: { size: 10 } } }
          },
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.parsed.x} event${ctx.parsed.x !== 1 ? 's' : ''} (${((ctx.parsed.x/total)*100).toFixed(1)}%)` } } }
        }
      });
    } catch (err) { meta.textContent = ''; cont.innerHTML = `<div class="state-box error">Failed to load: ${err.message}</div>`; }
  }

  // ── Idle Period Distribution Chart ───────────────────────────────────────
  async function loadIdleChart() {
    const meta = document.getElementById('idle-meta');
    const cont = document.getElementById('idle-chart-container');
    try {
      const events       = await getActivityEvents();
      const idleDurations = events.flatMap(e => (e.payload?.idlePeriods || []).map(p => p.durationMs)).filter(v => v != null);

      const short  = idleDurations.filter(d => d < 5000).length;
      const medium = idleDurations.filter(d => d >= 5000 && d < 30000).length;
      const long   = idleDurations.filter(d => d >= 30000).length;
      const total  = idleDurations.length;

      meta.innerHTML = `<strong>${total}</strong> idle period${total !== 1 ? 's' : ''} recorded`;

      if (total === 0) { cont.innerHTML = '<div class="state-box">No idle data yet.</div>'; return; }

      new Chart(document.getElementById('idleChart'), {
        type: 'bar',
        data: {
          labels: ['Idle Periods'],
          datasets: [
            { label: 'Short (< 5s)',       data: [short],  backgroundColor: 'rgba(0,255,157,0.75)',  borderColor: 'rgba(0,255,157,0.9)',  borderWidth: 1, borderRadius: 4 },
            { label: 'Medium (5s – 30s)',   data: [medium], backgroundColor: 'rgba(255,204,0,0.75)',  borderColor: 'rgba(255,204,0,0.9)',  borderWidth: 1, borderRadius: 4 },
            { label: 'Long (> 30s)',        data: [long],   backgroundColor: 'rgba(170,136,255,0.75)', borderColor: 'rgba(170,136,255,0.9)', borderWidth: 1, borderRadius: 4 }
          ]
        },
        options: {
          indexAxis: 'y', responsive: true,
          scales: {
            x: { stacked: true, grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } },
            y: { stacked: true, grid: { display: false } }
          },
          plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } },
            tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.x} (${((ctx.parsed.x/total)*100).toFixed(1)}%)` } }
          }
        }
      });
    } catch (err) { meta.textContent = ''; cont.innerHTML = `<div class="state-box error">Failed to load: ${err.message}</div>`; }
  }

  // ── Lazy load per tab ────────────────────────────────────────────────────
  const loaded = {};
  function loadTabCharts(tabName) {
    if (loaded[tabName]) return;
    loaded[tabName] = true;
    if (tabName === 'performance') { loadKpis(); loadLcpChart(); loadInpChart(); }
    if (tabName === 'traffic')     { loadBrowserChart(); loadSessionsByDayChart(); }
    if (tabName === 'activity')    { loadPagesChart(); loadIdleChart(); }
  }

  const activeTab = document.querySelector('.tab-btn.active');
  if (activeTab) loadTabCharts(activeTab.dataset.tab);

  // ── Export ───────────────────────────────────────────────────────────────
  async function exportReport() {
    const title    = document.getElementById('export-title-input').value.trim();
    const comments = document.getElementById('export-comments').value.trim();
    const status   = document.getElementById('export-status');
    const btn      = document.getElementById('export-btn');

    if (!title) {
      status.textContent = 'Please enter a report title.';
      status.className   = 'export-status error';
      return;
    }

    btn.disabled = true; btn.textContent = 'Generating PDF...';
    status.textContent = ''; status.className = 'export-status';

    try {
      const formData = new FormData();
      formData.append('title',    title);
      formData.append('category', 'charts');
      formData.append('comments', comments);

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
      btn.disabled = false; btn.textContent = 'Export PDF →';
    }
  }
</script>
</body>
</html>