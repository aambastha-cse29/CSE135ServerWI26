<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <!-- Embedded CSS For Styling The Dashboard Page -->
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

    /* Animated background grid */
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

    /* Glow orb */
    body::after {
      content: '';
      position: fixed;
      width: 700px;
      height: 700px;
      background: radial-gradient(circle, rgba(0,102,255,0.06) 0%, transparent 70%);
      bottom: -300px;
      left: -200px;
      pointer-events: none;
      z-index: 0;
    }

    /* ---------- LAYOUT ---------- */
    .page {
      position: relative;
      z-index: 1;
      max-width: 1000px;
      margin: 0 auto;
      padding: 48px 32px;
    }

    /* ---------- HEADER ---------- */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 64px;
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

    /* ---------- HERO ---------- */
    .hero {
      margin-bottom: 64px;
      animation: fadeUp 0.6s ease 0.1s both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .hero-eyebrow {
      font-size: 11px;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 16px;
    }

    .hero-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(36px, 6vw, 56px);
      font-weight: 800;
      line-height: 1.05;
      letter-spacing: -0.03em;
      color: var(--text);
      margin-bottom: 16px;
    }

    .hero-title .highlight {
      background: linear-gradient(90deg, var(--accent), var(--accent2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-sub {
      font-size: 13px;
      color: var(--muted);
      max-width: 420px;
      line-height: 1.7;
    }

    /* ---------- DIVIDER ---------- */
    .divider {
      height: 1px;
      background: var(--border);
      margin-bottom: 48px;
      animation: fadeUp 0.6s ease 0.2s both;
    }

    /* ---------- CARDS ---------- */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
      animation: fadeUp 0.6s ease 0.3s both;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
      text-decoration: none;
      display: flex;
      flex-direction: column;
      gap: 20px;
      position: relative;
      overflow: hidden;
      transition: border-color 0.25s, transform 0.2s, box-shadow 0.25s;
      cursor: pointer;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      opacity: 0;
      transition: opacity 0.25s;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }

    /* Card accent colors */
    .card-sessions::before  { background: linear-gradient(90deg, var(--accent2), var(--accent)); }
    .card-events::before    { background: linear-gradient(90deg, var(--accent), var(--accent2)); }
    .card-visualized::before{ background: linear-gradient(90deg, var(--accent3), #ff0080); }

    .card-sessions:hover  { border-color: var(--accent2); }
    .card-events:hover    { border-color: var(--accent); }
    .card-visualized:hover{ border-color: var(--accent3); }

    .card:hover::before { opacity: 1; }

    .card-icon {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .card-sessions  .card-icon { background: rgba(0,102,255,0.12); }
    .card-events    .card-icon { background: rgba(0,255,157,0.08); }
    .card-visualized .card-icon{ background: rgba(255,107,53,0.1); }

    .card-body {}

    .card-title {
      font-family: 'Syne', sans-serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
      letter-spacing: -0.01em;
    }

    .card-desc {
      font-size: 12px;
      color: var(--muted);
      line-height: 1.7;
    }

    .card-arrow {
      margin-top: auto;
      font-size: 12px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: gap 0.2s;
    }

    .card-sessions  .card-arrow { color: var(--accent2); }
    .card-events    .card-arrow { color: var(--accent); }
    .card-visualized .card-arrow{ color: var(--accent3); }

    .card:hover .card-arrow { gap: 14px; }

    /* ---------- FOOTER ---------- */
    footer {
      margin-top: 80px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      animation: fadeUp 0.6s ease 0.4s both;
    }

    .footer-left {
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 0.05em;
    }

    .footer-left span { color: var(--accent); }

    .footer-right {
      font-size: 11px;
      color: var(--muted);
    }
  </style>
</head>

<!-- /* ---------- BODY ---------- */ -->
<!-- The body contains the main structure of the dashboard page and cards linking to different analytics pages, along with the logout form. -->
<body>

<div class="page">

  <header>
    <div>
      <div class="brand-label">CSE135 · Analytics</div>
    </div>
    <form class="logout-form" method="POST" action="/auth.php">
      <input type="hidden" name="action" value="logout">
      <button type="submit">Sign out →</button>
    </form>
  </header>

  <div class="hero">
    <div class="hero-eyebrow">Overview</div>
    <h1 class="hero-title">Your Analytics,<br><span class="highlight">Your Data.</span></h1>
    <p class="hero-sub">Explore Raw Collected Data Or Visualized Data From The Test Site.</p>
  </div>

  <div class="divider"></div>

  <div class="cards">

    <a href="/sessions.php" class="card card-sessions">
      <div class="card-icon">⬡</div>
      <div class="card-body">
        <div class="card-title">Sessions Table</div>
        <div class="card-desc">Browse All Tracked Sessions — IP Addresses, User Agents, First And Last Seen Timestamps.</div>
      </div>
      <div class="card-arrow">View Sessions <span>→</span></div>
    </a>

    <a href="/events.php" class="card card-events">
      <div class="card-icon">◈</div>
      <div class="card-body">
        <div class="card-title">Events Table</div>
        <div class="card-desc">Browse All Collected Events — Static, Performance, Activity, And Noscript Payloads.</div>
      </div>
      <div class="card-arrow">View Events <span>→</span></div>
    </a>

    <a href="/charts.php" class="card card-visualized">
      <div class="card-icon">◎</div>
      <div class="card-body">
        <div class="card-title">Visualized Data</div>
        <div class="card-desc">Visual Breakdowns Of Data From Collected Events.</div>
      </div>
      <div class="card-arrow">View Charts <span>→</span></div>
    </a>

  </div>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right"><?= date('D, d M Y') ?></div>
  </footer>

</div>

</body>
</html>