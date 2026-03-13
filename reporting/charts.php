<?php
require_once 'auth_check.php';
require_once 'auth_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Charts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #0a0a0f;
      --surface: #111118;
      --border:  #1e1e2e;
      --accent:  #00ff9d;
      --accent2: #0066ff;
      --accent3: #ff6b35;
      --text:    #e8e8f0;
      --muted:   #5a5a7a;
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
      max-width: 1100px;
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
      margin-bottom: 48px;
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
    }

    .page-title span { color: var(--accent3); }

    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
      gap: 24px;
    }

    .chart-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
      position: relative;
      overflow: hidden;
      animation: fadeUp 0.5s ease 0.2s both;
    }

    .chart-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
    }

    .chart-card.card-browser::before { background: linear-gradient(90deg, var(--accent2), var(--accent)); }
    .chart-card.card-lcp::before     { background: linear-gradient(90deg, var(--accent), var(--accent3)); }

    .chart-title {
      font-family: 'Syne', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 4px;
    }

    .chart-subtitle { font-size: 11px; color: var(--muted); letter-spacing: 0.04em; margin-bottom: 8px; }
    .chart-meta { font-size: 11px; color: var(--muted); margin-bottom: 24px; }
    .chart-meta strong { color: var(--accent); font-weight: 500; }

    .state-box {
      padding: 40px 0;
      text-align: center;
      font-size: 12px;
      color: var(--muted);
    }

    .state-box.error { color: #ff4466; }

    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid var(--border);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      margin-right: 8px;
      vertical-align: middle;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .lcp-note {
      margin-top: 20px;
      padding: 14px 16px;
      background: rgba(0,255,157,0.05);
      border: 1px solid rgba(0,255,157,0.15);
      border-radius: 8px;
      font-size: 11px;
      color: var(--muted);
      line-height: 1.7;
    }

    .lcp-note .pct {
      font-family: 'Syne', sans-serif;
      font-size: 22px;
      font-weight: 800;
      display: block;
      margin-bottom: 4px;
    }

    .lcp-note .pct.good { color: var(--accent); }
    .lcp-note .pct.poor { color: var(--accent3); }

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
    <div class="page-eyebrow">Visualized Data</div>
    <h1 class="page-title">Analytics <span>Charts</span></h1>
  </div>

  <div class="charts-grid">

    <!-- CHART 1: Browser Share -->
    <div class="chart-card card-browser">
      <div class="chart-title">Browser Share</div>
      <div class="chart-subtitle">Unique sessions by browser</div>
      <div class="chart-meta" id="browser-meta"><span class="spinner"></span> Loading...</div>
      <div id="browser-chart-container">
        <canvas id="browserChart"></canvas>
      </div>
    </div>

    <!-- CHART 2: LCP Distribution -->
    <div class="chart-card card-lcp">
      <div class="chart-title">LCP Distribution</div>
      <div class="chart-subtitle">Largest Contentful Paint threshold breakdown</div>
      <div class="chart-meta" id="lcp-meta"><span class="spinner"></span> Loading...</div>
      <div id="lcp-chart-container">
        <canvas id="lcpChart"></canvas>
      </div>
      <div id="lcp-note"></div>
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
      <input type="text" class="export-input" id="export-title" placeholder="Report title e.g. Charts Report March 2026">
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

  Chart.defaults.color       = '#5a5a7a';
  Chart.defaults.font.family = "'DM Mono', monospace";
  Chart.defaults.font.size   = 11;

  // ---------- CHART 1: Browser Share ----------
  async function loadBrowserChart() {
    const meta      = document.getElementById('browser-meta');
    const container = document.getElementById('browser-chart-container');

    try {
      const res      = await fetch('/api/sessions');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const sessions = await res.json();

      const browsers = { Chrome: 0, Firefox: 0, Safari: 0, Edge: 0, Other: 0 };

      for (const s of sessions) {
        const ua = s.user_agent || '';
        if (ua.includes('Edg/') || ua.includes('Edge/'))       browsers.Edge++;
        else if (ua.includes('Firefox/'))                       browsers.Firefox++;
        else if (ua.includes('Chrome/') || ua.includes('CriOS/')) browsers.Chrome++;
        else if (ua.includes('Safari/') || ua.includes('FxiOS/')) browsers.Safari++;
        else                                                    browsers.Other++;
      }

      // Remove zero entries
      const labels = Object.keys(browsers).filter(k => browsers[k] > 0);
      const data   = labels.map(k => browsers[k]);
      const total  = data.reduce((a, b) => a + b, 0);

      meta.innerHTML = `<strong>${total}</strong> session${total !== 1 ? 's' : ''} analyzed`;

      if (total === 0) {
        const box = document.createElement('div');
        box.className   = 'state-box';
        box.textContent = 'No session data available yet.';
        container.innerHTML = '';
        container.appendChild(box);
        return;
      }

      new Chart(document.getElementById('browserChart'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Sessions',
            data,
            backgroundColor: [
              'rgba(0, 102, 255, 0.8)',
              'rgba(0, 255, 157, 0.75)',
              'rgba(255, 107, 53, 0.8)',
              'rgba(170, 136, 255, 0.8)',
              'rgba(90, 90, 122, 0.6)',
            ],
            borderColor: '#0a0a0f',
            borderWidth: 2,
            borderRadius: 6,
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: { grid: { color: 'rgba(30,30,46,0.8)' } },
            y: { grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const pct = ((ctx.parsed.y / total) * 100).toFixed(1);
                  return ` ${ctx.parsed.y} session${ctx.parsed.y !== 1 ? 's' : ''} (${pct}%)`;
                }
              }
            }
          }
        }
      });

    } catch (err) {
      meta.textContent = '';
      const box = document.createElement('div');
      box.className   = 'state-box error';
      box.textContent = `Failed to load: ${err.message}`;
      document.getElementById('browser-chart-container').innerHTML = '';
      document.getElementById('browser-chart-container').appendChild(box);
    }
  }

  // ---------- CHART 2: LCP Distribution ----------
  async function loadLcpChart() {
    const meta      = document.getElementById('lcp-meta');
    const container = document.getElementById('lcp-chart-container');
    const noteEl    = document.getElementById('lcp-note');

    try {
      const res    = await fetch('/api/events/performance');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const events = await res.json();

      // Fetch full payloads to get webVitals.lcp
      const payloadFetches = events.map(async (e) => {
        try {
          const r = await fetch(`/api/events/${e.id}`);
          if (r.ok) {
            const full = await r.json();
            e.payload = full.payload;
          }
        } catch (_) {}
      });

      await Promise.all(payloadFetches);

      const lcpValues = events
        .map(e => e.payload?.webVitals?.lcp)
        .filter(v => v !== null && v !== undefined);

      const total = lcpValues.length;
      const good  = lcpValues.filter(v => v < 2500).length;
      const bad   = lcpValues.filter(v => v >= 2500).length;
      const pct   = total > 0 ? ((good / total) * 100).toFixed(1) : 0;

      meta.innerHTML = `<strong>${total}</strong> page load${total !== 1 ? 's' : ''} measured`;

      if (total === 0) {
        const box = document.createElement('div');
        box.className   = 'state-box';
        box.textContent = 'No LCP data available yet.';
        container.innerHTML = '';
        container.appendChild(box);
        return;
      }

      new Chart(document.getElementById('lcpChart'), {
        type: 'bar',
        data: {
          labels: ['LCP'],
          datasets: [
            {
              label: 'Good (< 2.5s)',
              data: [good],
              backgroundColor: 'rgba(0, 255, 157, 0.75)',
              borderColor: 'rgba(0, 255, 157, 0.9)',
              borderWidth: 1,
              borderRadius: 4,
            },
            {
              label: 'Needs Improvement (≥ 2.5s)',
              data: [bad],
              backgroundColor: 'rgba(255, 107, 53, 0.75)',
              borderColor: 'rgba(255, 107, 53, 0.9)',
              borderWidth: 1,
              borderRadius: 4,
            }
          ]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          scales: {
            x: { stacked: true, grid: { color: 'rgba(30,30,46,0.8)' }, ticks: { stepSize: 1 } },
            y: { stacked: true, grid: { display: false } }
          },
          plugins: {
            legend: {
              position: 'bottom',
              labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 }
            },
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const p = ((ctx.parsed.x / total) * 100).toFixed(1);
                  return ` ${ctx.dataset.label}: ${ctx.parsed.x} load${ctx.parsed.x !== 1 ? 's' : ''} (${p}%)`;
                }
              }
            }
          }
        }
      });

      // LCP note — built via DOM, pct is a computed number not raw API data
      const isGood = parseFloat(pct) >= 75;
      const note   = document.createElement('div');
      note.className = 'lcp-note';

      const pctSpan = document.createElement('span');
      pctSpan.className   = `pct ${isGood ? 'good' : 'poor'}`;
      pctSpan.textContent = `${pct}%`;

      const desc = document.createElement('span');
      desc.textContent = ' of page loads have good LCP (< 2.5s).';

      const br = document.createElement('br');

      const threshold = document.createElement('span');
      threshold.textContent = "Google's Core Web Vitals threshold requires good LCP for at least 75% of page loads.";

      note.appendChild(pctSpan);
      note.appendChild(desc);
      note.appendChild(br);
      note.appendChild(threshold);
      noteEl.appendChild(note);

    } catch (err) {
      meta.textContent = '';
      const box = document.createElement('div');
      box.className   = 'state-box error';
      box.textContent = `Failed to load: ${err.message}`;
      container.innerHTML = '';
      container.appendChild(box);
    }
  }

  loadBrowserChart();
  loadLcpChart();

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
      formData.append('category', 'charts');
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