<?php
session_start();

// Already logged in — redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: /dashboard");
    exit;
}

$status = $_GET['status'] ?? null;
$error  = $_GET['error']  ?? null;
?>

<!-- Login page for CSE135 Analytics Dashboard -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <title>Analytics · Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <!-- Embedded CSS for styling the login page -->
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #0a0a0f;
      --surface: #111118;
      --border:  #1e1e2e;
      --accent:  #00ff9d;
      --accent2: #0066ff;
      --text:    #e8e8f0;
      --muted:   #5a5a7a;
      --error:   #ff4466;
      --warning: #ffaa00;
      --success: #00ff9d;
    }

    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Mono', monospace;
    }

    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow: hidden;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(rgba(0,255,157,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,255,157,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      animation: gridMove 20s linear infinite;
      pointer-events: none;
    }

    @keyframes gridMove {
      0%   { transform: translateY(0); }
      100% { transform: translateY(40px); }
    }

    body::after {
      content: '';
      position: fixed;
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(0,102,255,0.08) 0%, transparent 70%);
      top: -200px;
      right: -200px;
      pointer-events: none;
      animation: orbFloat 8s ease-in-out infinite alternate;
    }

    @keyframes orbFloat {
      0%   { transform: translate(0, 0); }
      100% { transform: translate(-30px, 30px); }
    }

    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 0 24px;
      animation: fadeUp 0.6s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .brand {
      margin-bottom: 40px;
      text-align: center;
    }

    .brand-label {
      font-family: 'Syne', sans-serif;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .brand-title {
      font-family: 'Syne', sans-serif;
      font-size: 28px;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.02em;
    }

    .brand-title span { color: var(--accent); }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 36px;
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg, var(--accent2), var(--accent));
    }

    .card-header {
      margin-bottom: 28px;
    }

    .card-title {
      font-family: 'Syne', sans-serif;
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 6px;
    }

    .card-sub {
      font-size: 12px;
      color: var(--muted);
      letter-spacing: 0.02em;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 12px;
      margin-bottom: 20px;
      letter-spacing: 0.02em;
    }

    .alert-error {
      background: rgba(255, 68, 102, 0.1);
      border: 1px solid rgba(255, 68, 102, 0.3);
      color: var(--error);
    }

    .alert-warning {
      background: rgba(255, 170, 0, 0.08);
      border: 1px solid rgba(255, 170, 0, 0.25);
      color: var(--warning);
    }

    .alert-success {
      background: rgba(0, 255, 157, 0.08);
      border: 1px solid rgba(0, 255, 157, 0.2);
      color: var(--success);
    }

    .field {
      margin-bottom: 20px;
    }

    .field label {
      display: block;
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 8px;
    }

    .field input {
      width: 100%;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px 16px;
      font-family: 'DM Mono', monospace;
      font-size: 14px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .field input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(0, 255, 157, 0.08);
    }

    .field input::placeholder { color: var(--muted); }

    .btn {
      width: 100%;
      padding: 14px;
      background: var(--accent);
      color: #000;
      border: none;
      border-radius: 8px;
      font-family: 'Syne', sans-serif;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.05em;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.1s;
      margin-top: 8px;
    }

    .btn:hover  { opacity: 0.9; }
    .btn:active { transform: scale(0.99); }

    .footer-note {
      text-align: center;
      margin-top: 24px;
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 0.05em;
    }

    .footer-note span { color: var(--accent); }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="brand">
    <div class="brand-label">CSE135 · Analytics</div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Sign in</div>
      <div class="card-sub">Access restricted to authorized personnel</div>
    </div>

    <?php if ($error === 'invalid'): ?>
      <div class="alert alert-error">⚠ Invalid username or password.</div>
    <?php endif; ?>

    <?php if ($status === 'expired'): ?>
      <div class="alert alert-warning">⏱ Your session expired. Please sign in again.</div>
    <?php endif; ?>

    <?php if ($status === 'logged_out'): ?>
      <div class="alert alert-success">✓ You have been signed out.</div>
    <?php endif; ?>

    <!-- Login form submits to auth.php which handles authentication logic -->
    <!-- The hidden input 'action' with value 'login' indicates the type of request to auth.php -->
    <form method="POST" action="/auth.php"> 
      <input type="hidden" name="action" value="login">

      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="enter username" autocomplete="username" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••••••" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn">Sign In →</button>
    </form>
  </div>

  <div class="footer-note">
    CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span>
  </div>
</div>

</body>
</html>