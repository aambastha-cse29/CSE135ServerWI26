<?php
// export_action.php
// Receives a base64 screenshot from html2canvas, generates a PDF using dompdf,
// saves it to the server, and inserts a record into the reports table.
// Returns JSON response.

require_once 'auth_check.php';
require_once 'auth_helpers.php';
require_once 'db.php';
require_once '/var/lib/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

// Only analysts and superadmins can export
if (!canExport()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$title    = trim($_POST['title']    ?? '');
$category = trim($_POST['category'] ?? '');
$image    = $_POST['image']         ?? '';

$allowedCategories = ['sessions', 'events', 'charts'];

if (empty($title) || empty($image) || !in_array($category, $allowedCategories, true)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Strip base64 data URI prefix: "data:image/png;base64,..."
if (strpos($image, 'base64,') !== false) {
    $image = substr($image, strpos($image, 'base64,') + 7);
}

$imageData = base64_decode($image);
if ($imageData === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid image data']);
    exit;
}

// Build HTML for PDF — embeds the screenshot as an image
$escapedTitle    = htmlspecialchars($title);
$escapedCategory = htmlspecialchars($category);
$escapedUser     = htmlspecialchars($_SESSION['username']);
$escapedDate     = date('D, d M Y');
$base64Embed     = base64_encode($imageData);

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: monospace;
      background: #0a0a0f;
      color: #e8e8f0;
      padding: 32px;
    }
    .header {
      border-bottom: 1px solid #1e1e2e;
      padding-bottom: 16px;
      margin-bottom: 24px;
    }
    .label {
      font-size: 9px;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: #00ff9d;
      margin-bottom: 6px;
    }
    .title {
      font-size: 22px;
      font-weight: bold;
      color: #e8e8f0;
      margin-bottom: 8px;
    }
    .meta {
      font-size: 10px;
      color: #5a5a7a;
    }
    .meta span { color: #0066ff; margin-right: 16px; }
    .screenshot {
      width: 100%;
      margin-top: 8px;
    }
    .screenshot img {
      width: 100%;
      border-radius: 8px;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="label">CSE135 · Analytics Report</div>
    <div class="title">{$escapedTitle}</div>
    <div class="meta">
      <span>Category: {$escapedCategory}</span>
      <span>By: {$escapedUser}</span>
      <span>{$escapedDate}</span>
    </div>
  </div>
  <div class="screenshot">
    <img src="data:image/png;base64,{$base64Embed}">
  </div>
</body>
</html>
HTML;

// Generate PDF with dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'monospace');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Save PDF to public_html
$filename    = 'report_' . uniqid() . '.pdf';
$savePath    = __DIR__ . '/' . $filename;
$pdfContents = $dompdf->output();

if (file_put_contents($savePath, $pdfContents) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to save PDF']);
    exit;
}

$pdfUrl = 'https://reporting.cse135wi2026.site/' . $filename;

// Insert record into reports table
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "INSERT INTO reports (user_id, title, category, pdf_url) VALUES (:user_id, :title, :category, :pdf_url)"
    );
    $stmt->execute([
        ':user_id'  => $_SESSION['user_id'],
        ':title'    => $title,
        ':category' => $category,
        ':pdf_url'  => $pdfUrl,
    ]);

    echo json_encode(['success' => true, 'pdf_url' => $pdfUrl]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}