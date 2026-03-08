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

// --------------------
// CHART 1: Browser Share (from sessions)
// --------------------
$uaStmt = $pdo->query("SELECT user_agent FROM sessions WHERE user_agent IS NOT NULL");
$uaRows  = $uaStmt->fetchAll();

$browsers = ['Chrome' => 0, 'Firefox' => 0, 'Safari' => 0, 'Edge' => 0, 'Other' => 0];

foreach ($uaRows as $row) {
    $ua = $row['user_agent'];
    if (str_contains($ua, 'Edg/') || str_contains($ua, 'Edge/')) {
        $browsers['Edge']++;
    } elseif (str_contains($ua, 'Firefox/')) {
        $browsers['Firefox']++;
    } elseif (str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/')) {
        $browsers['Chrome']++;
    } elseif (str_contains($ua, 'Safari/') || str_contains($ua, 'FxiOS/')) {
        $browsers['Safari']++;
    } else {
        $browsers['Other']++;
    }
}

// Remove zero-count browsers for cleaner chart
$browsers = array_filter($browsers, fn($v) => $v > 0);

$browserLabels = json_encode(array_keys($browsers));
$browserCounts = json_encode(array_values($browsers));
$totalSessions = array_sum($browsers);

// --------------------
// CHART 2: LCP Distribution
// --------------------
$lcpStmt = $pdo->query("
    SELECT
        SUM(CASE WHEN JSON_EXTRACT(payload, '$.webVitals.lcp') < 2500 THEN 1 ELSE 0 END) as good,
        SUM(CASE WHEN JSON_EXTRACT(payload, '$.webVitals.lcp') >= 2500 THEN 1 ELSE 0 END) as needs_improvement,
        COUNT(*) as total
    FROM events
    WHERE event_type = 'performance'
    AND JSON_EXTRACT(payload, '$.webVitals.lcp') IS NOT NULL
");
$lcpData = $lcpStmt->fetch();

$lcpGood  = (int)($lcpData['good'] ?? 0);
$lcpBad   = (int)($lcpData['needs_improvement'] ?? 0);
$lcpTotal = (int)($lcpData['total'] ?? 0);
$lcpGoodPct = $lcpTotal > 0 ? round(($lcpGood / $lcpTotal) * 100, 1) : 0;
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

    /* ---------- PAGE HEADING ---------- */
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

    /* ---------- CHART GRID ---------- */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
      gap: 24px;
    }

    /* ---------- CHART CARD ---------- */
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

    .chart-card.card-browser::before {
      background: linear-gradient(90deg, var(--accent2), var(--accent));
    }

    .chart-card.card-lcp::before {
      background: linear-gradient(90deg, var(--accent), var(--accent3));
    }

    .chart-header {
      margin-bottom: 8px;
    }

    .chart-title {
      font-family: 'Syne', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 4px;
    }

    .chart-subtitle {
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 0.04em;
    }

    .chart-meta {
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 24px;
    }

    .chart-meta strong { color: var(--accent); font-weight: 500; }

    .chart-container {
      position: relative;
    }

    /* ---------- LCP NOTE ---------- */
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

    .lcp-note .pct.good  { color: var(--accent); }
    .lcp-note .pct.poor  { color: var(--accent3); }

    .lcp-note .threshold {
      color: var(--text);
    }

    /* ---------- EMPTY STATE ---------- */
    .empty-state {
      padding: 40px 0;
      text-align: center;
      color: var(--muted);
      font-size: 12px;
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
      <div class="chart-header">
        <div class="chart-title">Browser Share</div>
        <div class="chart-subtitle">Unique Sessions By Browser</div>
      </div>
      <div class="chart-meta">
        <strong><?= $totalSessions ?></strong> Session<?= $totalSessions !== 1 ? 's' : '' ?> Analyzed
      </div>
      <?php if ($totalSessions === 0): ?>
        <div class="empty-state">No Session Data Available Yet.</div>
      <?php else: ?>
        <div class="chart-container">
          <canvas id="browserChart"></canvas>
        </div>
      <?php endif; ?>
    </div>

    <!-- CHART 2: LCP Distribution -->
    <div class="chart-card card-lcp">
      <div class="chart-header">
        <div class="chart-title">LCP Distribution</div>
        <div class="chart-subtitle">Largest Contentful Paint Threshold Breakdown</div>
      </div>
      <div class="chart-meta">
        <strong><?= $lcpTotal ?></strong> Page Load<?= $lcpTotal !== 1 ? 's' : '' ?> Measured
      </div>
      <?php if ($lcpTotal === 0): ?>
        <div class="empty-state">No LCP Data Available Yet.</div>
      <?php else: ?>
        <div class="chart-container">
          <canvas id="lcpChart"></canvas>
        </div>
        <div class="lcp-note">
          <span class="pct <?= $lcpGoodPct >= 75 ? 'good' : 'poor' ?>"><?= $lcpGoodPct ?>%</span>
          <span class="threshold">Of Page Loads Have <strong>Good LCP (&lt; 2.5s)</strong>.</span>
          <br>Google's Core Web Vitals Threshold requires Good LCP For At Least 75% Of Page Loads.
        </div>
      <?php endif; ?>
    </div>

  </div>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right"><?= date('D, d M Y') ?></div>
  </footer>

</div>

<script>
// ---------- SHARED CHART DEFAULTS ----------
Chart.defaults.color         = '#5a5a7a';
Chart.defaults.font.family   = "'DM Mono', monospace";
Chart.defaults.font.size     = 11;

// ---------- CHART 1: Browser Share ----------
<?php if ($totalSessions > 0): ?>
new Chart(document.getElementById('browserChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $browserLabels ?>,
    datasets: [{
      data: <?= $browserCounts ?>,
      backgroundColor: [
        'rgba(0, 102, 255, 0.8)',
        'rgba(0, 255, 157, 0.75)',
        'rgba(255, 107, 53, 0.8)',
        'rgba(170, 136, 255, 0.8)',
        'rgba(90, 90, 122, 0.6)',
      ],
      borderColor: '#0a0a0f',
      borderWidth: 3,
      hoverOffset: 8,
    }]
  },
  options: {
    responsive: true,
    cutout: '65%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 16,
          usePointStyle: true,
          pointStyleWidth: 8,
        }
      },
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const pct   = ((ctx.parsed / total) * 100).toFixed(1);
            return ` ${ctx.label}: ${ctx.parsed} session${ctx.parsed !== 1 ? 's' : ''} (${pct}%)`;
          }
        }
      }
    }
  }
});
<?php endif; ?>

// ---------- CHART 2: LCP Distribution ----------
<?php if ($lcpTotal > 0): ?>
new Chart(document.getElementById('lcpChart'), {
  type: 'bar',
  data: {
    labels: ['LCP'],
    datasets: [
      {
        label: 'Good (< 2.5s)',
        data: [<?= $lcpGood ?>],
        backgroundColor: 'rgba(0, 255, 157, 0.75)',
        borderColor: 'rgba(0, 255, 157, 0.9)',
        borderWidth: 1,
        borderRadius: 4,
      },
      {
        label: 'Needs Improvement (≥ 2.5s)',
        data: [<?= $lcpBad ?>],
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
      x: {
        stacked: true,
        grid: { color: 'rgba(30, 30, 46, 0.8)' },
        ticks: { stepSize: 1 }
      },
      y: {
        stacked: true,
        grid: { display: false },
      }
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 16,
          usePointStyle: true,
          pointStyleWidth: 8,
        }
      },
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const pct = ((ctx.parsed.x / <?= $lcpTotal ?>) * 100).toFixed(1);
            return ` ${ctx.dataset.label}: ${ctx.parsed.x} load${ctx.parsed.x !== 1 ? 's' : ''} (${pct}%)`;
          }
        }
      }
    }
  }
});
<?php endif; ?>
</script>

</body>
</html>