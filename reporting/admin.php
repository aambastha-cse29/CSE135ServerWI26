<?php
require_once 'auth_check.php';
require_once 'auth_helpers.php';
require_once 'db.php';

// Only superadmin can access this page
if (!canManageUsers()) {
    header("Location: /403");
    exit;
}

$pdo   = getDB();
$users = $pdo->query("SELECT id, username, role, sections, created_at FROM users ORDER BY created_at ASC")->fetchAll();

// Flash messages from admin_action.php
$success = $_GET['success'] ?? null;
$error   = $_GET['error']   ?? null;

$SECTIONS = ['performance', 'activity', 'traffic'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics · User Management</title>
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
    }

    .page-title span { color: var(--accent4); }

    /* ---------- FLASH MESSAGES ---------- */
    .flash {
      padding: 14px 20px;
      border-radius: 10px;
      font-size: 12px;
      margin-bottom: 28px;
      animation: fadeUp 0.4s ease both;
    }

    .flash-success {
      background: rgba(0,255,157,0.06);
      border: 1px solid rgba(0,255,157,0.2);
      color: var(--accent);
    }

    .flash-error {
      background: rgba(255,107,53,0.06);
      border: 1px solid rgba(255,107,53,0.2);
      color: var(--accent3);
    }

    /* ---------- SECTION ---------- */
    .section {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
      margin-bottom: 32px;
      animation: fadeUp 0.5s ease 0.2s both;
    }

    .section-title {
      font-family: 'Syne', sans-serif;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 24px;
    }

    /* ---------- TABLE ---------- */
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }

    thead th {
      padding: 12px 16px;
      text-align: left;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(255,255,255,0.02); }

    tbody td {
      padding: 14px 16px;
      color: var(--text);
      vertical-align: middle;
    }

    .col-id   { color: var(--muted); font-size: 11px; }
    .col-user { font-weight: 500; }
    .col-date { color: var(--muted); font-size: 11px; }

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

    .badge-superadmin { color: var(--accent);  border-color: rgba(0,255,157,0.3);   background: rgba(0,255,157,0.06); }
    .badge-analyst    { color: var(--accent2); border-color: rgba(0,102,255,0.3);   background: rgba(0,102,255,0.08); }
    .badge-viewer     { color: var(--accent4); border-color: rgba(170,136,255,0.3); background: rgba(170,136,255,0.08); }

    .sections-list {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    .section-tag {
      font-size: 10px;
      padding: 2px 8px;
      border-radius: 4px;
      background: rgba(0,102,255,0.08);
      border: 1px solid rgba(0,102,255,0.2);
      color: var(--accent2);
    }

    .null-val { color: var(--muted); font-style: italic; }

    /* ---------- ACTION BUTTONS ---------- */
    .btn-edit, .btn-delete {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 6px 14px;
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
      letter-spacing: 0.04em;
    }

    .btn-edit   { color: var(--accent2); }
    .btn-edit:hover { border-color: var(--accent2); }

    .btn-delete { color: var(--accent3); margin-left: 6px; }
    .btn-delete:hover { border-color: var(--accent3); }

    /* ---------- ADD USER FORM ---------- */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-group.full { grid-column: 1 / -1; }

    label {
      font-size: 10px;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
    }

    input[type="text"],
    input[type="password"],
    select {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 14px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s;
      width: 100%;
    }

    input[type="text"]:focus,
    input[type="password"]:focus,
    select:focus { border-color: var(--accent4); }

    select option { background: var(--surface); }

    .checkboxes {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
    }

    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: var(--text);
      cursor: pointer;
      text-transform: none;
      letter-spacing: 0;
    }

    input[type="checkbox"] { accent-color: var(--accent4); width: 14px; height: 14px; }

    .btn-submit {
      background: var(--accent4);
      border: none;
      border-radius: 8px;
      padding: 12px 28px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--bg);
      font-weight: 500;
      cursor: pointer;
      transition: opacity 0.2s;
      letter-spacing: 0.05em;
      margin-top: 8px;
    }

    .btn-submit:hover { opacity: 0.85; }

    /* ---------- EDIT MODAL ---------- */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      z-index: 100;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.open { display: flex; }

    .modal {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
      width: 100%;
      max-width: 480px;
      animation: fadeUp 0.3s ease both;
    }

    .modal-title {
      font-family: 'Syne', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 24px;
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .btn-cancel {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 20px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--muted);
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }

    .btn-cancel:hover { border-color: var(--muted); color: var(--text); }

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
    <div class="page-eyebrow">Administration</div>
    <h1 class="page-title">User <span>Management</span></h1>
  </div>

  <?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- USER LIST -->
  <div class="section">
    <div class="section-title">All Users</div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Role</th>
          <th>Sections</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="col-id"><?= htmlspecialchars($u['id']) ?></td>
          <td class="col-user"><?= htmlspecialchars($u['username']) ?></td>
          <td>
            <span class="badge badge-<?= htmlspecialchars($u['role']) ?>">
              <?= htmlspecialchars($u['role']) ?>
            </span>
          </td>
          <td>
            <?php
              $secs = json_decode($u['sections'] ?? '[]', true) ?: [];
              if (empty($secs)):
            ?>
              <span class="null-val">—</span>
            <?php else: ?>
              <div class="sections-list">
                <?php foreach ($secs as $sec): ?>
                  <span class="section-tag"><?= htmlspecialchars($sec) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </td>
          <td class="col-date"><?= htmlspecialchars($u['created_at']) ?></td>
          <td>
            <button class="btn-edit" onclick="openEdit(
              <?= (int)$u['id'] ?>,
              '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>',
              '<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>',
              <?= htmlspecialchars($u['sections'] ?? '[]', ENT_QUOTES) ?>
            )">Edit</button>
            <?php if ($u['username'] !== $_SESSION['username']): ?>
            <form method="POST" action="/admin_action" style="display:inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn-delete"
                onclick="return confirm('Delete <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?')">
                Delete
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ADD USER FORM -->
  <div class="section">
    <div class="section-title">Add New User</div>
    <form method="POST" action="/admin_action">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required autocomplete="off">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="add-role" onchange="toggleSections('add-sections', this.value)">
            <option value="analyst">Analyst</option>
            <option value="viewer">Viewer</option>
            <option value="superadmin">Super Admin</option>
          </select>
        </div>
        <div class="form-group" id="add-sections">
          <label>Sections (analyst only)</label>
          <div class="checkboxes">
            <?php foreach ($SECTIONS as $sec): ?>
              <label class="checkbox-label">
                <input type="checkbox" name="sections[]" value="<?= htmlspecialchars($sec) ?>">
                <?= htmlspecialchars($sec) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group full">
          <button type="submit" class="btn-submit">Add User →</button>
        </div>
      </div>
    </form>
  </div>

  <footer>
    <div class="footer-left">CSE135 · WI2026 · <span>reporting.cse135wi2026.site</span></div>
    <div class="footer-right"><?= date('D, d M Y') ?></div>
  </footer>

</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal">
    <div class="modal-title">Edit User</div>
    <form method="POST" action="/admin_action">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="user_id" id="edit-user-id">
      <div class="form-grid">
        <div class="form-group full">
          <label>Username</label>
          <input type="text" id="edit-username" disabled style="opacity:0.5;">
        </div>
        <div class="form-group">
          <label>New Password <span style="color:var(--muted)">(leave blank to keep)</span></label>
          <input type="password" name="password" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="edit-role" onchange="toggleSections('edit-sections', this.value)">
            <option value="analyst">Analyst</option>
            <option value="viewer">Viewer</option>
            <option value="superadmin">Super Admin</option>
          </select>
        </div>
        <div class="form-group full" id="edit-sections">
          <label>Sections (analyst only)</label>
          <div class="checkboxes">
            <?php foreach ($SECTIONS as $sec): ?>
              <label class="checkbox-label">
                <input type="checkbox" name="sections[]" value="<?= htmlspecialchars($sec) ?>" id="edit-sec-<?= htmlspecialchars($sec) ?>">
                <?= htmlspecialchars($sec) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit" class="btn-submit">Save Changes →</button>
        <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  function toggleSections(containerId, role) {
    const el = document.getElementById(containerId);
    el.style.display = role === 'analyst' ? 'flex' : 'none';
    el.style.flexDirection = 'column';
    el.style.gap = '8px';
  }

  // Init visibility on page load
  toggleSections('add-sections', document.getElementById('add-role').value);

  function openEdit(id, username, role, sections) {
    document.getElementById('edit-user-id').value  = id;
    document.getElementById('edit-username').value = username;

    const roleSelect = document.getElementById('edit-role');
    roleSelect.value = role;
    toggleSections('edit-sections', role);

    // Reset all checkboxes
    <?php foreach ($SECTIONS as $sec): ?>
      document.getElementById('edit-sec-<?= $sec ?>').checked = false;
    <?php endforeach; ?>

    // Check assigned sections
    if (Array.isArray(sections)) {
      sections.forEach(sec => {
        const cb = document.getElementById('edit-sec-' + sec);
        if (cb) cb.checked = true;
      });
    }

    document.getElementById('edit-modal').classList.add('open');
  }

  function closeEdit() {
    document.getElementById('edit-modal').classList.remove('open');
  }

  // Close modal on overlay click
  document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
  });
</script>
</body>
</html>