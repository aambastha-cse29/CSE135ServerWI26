<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 · Access Denied</title>
  <link rel="icon" href="/favicon.ico">
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
      --text:    #e8e8f0;
      --muted:   #5a5a7a;
    }

    html, body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Mono', monospace;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,107,53,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,107,53,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      animation: gridMove 20s linear infinite;
      pointer-events: none;
      z-index: 0;
    }

    @keyframes gridMove {
      0%   { transform: translateY(0); }
      100% { transform: translateY(40px); }
    }

    .container {
      position: relative;
      z-index: 1;
      text-align: center;
      padding: 48px 32px;
      max-width: 480px;
    }

    .code {
      font-family: 'Syne', sans-serif;
      font-size: 120px;
      font-weight: 800;
      line-height: 1;
      color: transparent;
      -webkit-text-stroke: 1px rgba(255,107,53,0.3);
      letter-spacing: -0.04em;
      margin-bottom: 8px;
      animation: fadeDown 0.6s ease both;
      user-select: none;
    }

    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .label {
      font-size: 9px;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: var(--accent3);
      margin-bottom: 20px;
      animation: fadeUp 0.5s ease 0.1s both;
    }

    .title {
      font-family: 'Syne', sans-serif;
      font-size: 24px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 12px;
      animation: fadeUp 0.5s ease 0.15s both;
    }

    .desc {
      font-size: 12px;
      color: var(--muted);
      line-height: 1.7;
      margin-bottom: 36px;
      animation: fadeUp 0.5s ease 0.2s both;
    }

    .btn {
      display: inline-block;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px 28px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--muted);
      text-decoration: none;
      letter-spacing: 0.05em;
      transition: border-color 0.2s, color 0.2s;
      animation: fadeUp 0.5s ease 0.25s both;
    }

    .btn:hover { border-color: var(--accent); color: var(--accent); }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="code">404</div>
    <div class="label">This Page Does Not Exist</div>
    <div class="title">The Page You Are Looking For Could Not Be Found</div>
    <div class="desc">It Seems The Page You Are Looking For Does Not Exist Or Has Been Moved</div>
    <a href="/dashboard" class="btn">← Back To Dashboard</a>
  </div>
</body>
</html>