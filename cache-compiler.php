<?php
require 'helpers.php';
require 'CurlClient.php';
require 'OAuthClient.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $url = $_POST['url'] ?? null;

  if (!$url) {
    echo json_encode(['error' => 'No URL provided']);
    exit;
  }

  // Basic Airtable-specific pagination binding
  $allRecords = [];
  $offset = null;
  $client = new CurlClient;
  $page = 1;

  do {
    $queryUrl = $url . ($offset ? "&offset=" . urlencode($offset) : "");
    $filePath = buildFilePath('GET', $queryUrl);

    ob_start();
    $info = $client->get($queryUrl, [], null);
    $response = json_decode($info['body'] ?? ob_get_clean(), true);

    if (isset($response['records'])) {
      $allRecords = array_merge($allRecords, $response['records']);
    }

    $offset = $response['offset'] ?? null;
    $page++;

  } while ($offset);

  // Save merged dataset
  $mergedPath = __DIR__ . '/cache/merged-' . md5($url) . '.json';
  file_put_contents($mergedPath, json_encode($allRecords, JSON_PRETTY_PRINT));

  // Optionally trigger image touching
  $touched = 0;
  foreach ($allRecords as $record) {
    if (!empty($record['fields']['Attachments'])) {
      foreach ($record['fields']['Attachments'] as $attachment) {
        if (!empty($attachment['url'])) {
          $client->get($attachment['url']);
          $touched++;
          usleep(100000); // 0.1s delay to rate-limit
        }
      }
    }
  }

  echo json_encode([
    'status' => 'success',
    'records' => count($allRecords),
    'attachmentsTouched' => $touched,
    'mergedFile' => basename($mergedPath)
  ]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Airtable Cache Compiler</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
  <div class="container">
    <h1 class="mb-4">Airtable Cache Compiler</h1>
    <p class="text-muted">Bind paginated Airtable data and cache linked resources.</p>

    <form id="compilerForm" class="mb-4">
      <div class="mb-3">
        <label class="form-label">Airtable API URL</label>
        <input type="url" name="url" class="form-control" placeholder="https://api.airtable.com/v0/app123/TableName?api_key=..." required>
      </div>
      <button class="btn btn-primary" type="submit">Run Compiler</button>
    </form>

    <div id="output" class="alert d-none"></div>
  </div>

  <script>
  document.getElementById('compilerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const output = document.getElementById('output');
    output.className = 'alert alert-info';
    output.textContent = 'Running... please wait.';

    const res = await fetch(form.action || '', {
      method: 'POST',
      body: data
    });
    const json = await res.json();
    output.className = 'alert ' + (json.status === 'success' ? 'alert-success' : 'alert-danger');
    output.textContent = json.status === 'success'
      ? `Merged ${json.records} records and touched ${json.attachmentsTouched} attachments.`
      : `Error: ${json.error || 'Unknown error'}`;
  });
  </script>
</body>
</html>
