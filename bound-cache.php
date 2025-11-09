<?php
header('Content-Type: application/json; charset=utf-8');
require 'helpers.php';

$dir = __DIR__ . '/cache';
if (!file_exists($dir)) mkdir($dir, 0700, true);

$action = $_GET['action'] ?? 'list';

switch ($action) {

    // 🧩 SAVE
    case 'save':
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $records = $data['records'] ?? [];
        $duration = $data['duration'] ?? null;

        if (!$records) {
            echo json_encode(['error' => 'No records provided']);
            exit;
        }

        $hash = hash('sha256', json_encode($records[0]['table'] ?? 'boundcache'));
        $file = "$dir/merged-$hash.json";

        $payload = [
            'records' => $records,
            'meta' => [
            'created_at' => date('c'),
            'duration_seconds' => $duration ? floatval($duration) : null
            ]
        ];

        file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));

        echo json_encode([
            'status' => 'saved',
            'records' => count($records),
            'file' => basename($file),
            'duration_seconds' => $duration
        ]);
        break;


  // 🧩 GET
  case 'get':
    $file = $_GET['file'] ?? '';
    $path = "$dir/$file";
    if (!file_exists($path)) {
      http_response_code(404);
      echo json_encode(['error' => 'File not found']);
      exit;
    }
    echo file_get_contents($path);
    break;

  // 🧩 DELETE
  case 'delete':

    $file = $_GET['file'] ?? '';
    $path = "$dir/$file";
    if (file_exists($path)) unlink($path);
    echo json_encode(['status' => 'deleted', 'file' => $file]);
    break;

  default:
    echo json_encode(['error' => 'Unknown action']);
}
