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
        $url = $_GET['url'] ?? null;

        if (!$records) {
            echo json_encode(['error' => 'No records provided']);
            exit;
        }

        // use URL-based hash for consistent naming
        $hash = $url
            ? hash('sha256', $url)
            : hash('sha256', 'boundcache');

        $file = "$dir/bound-$hash.json";

        $payload = [
            'records' => $records,
            'meta' => [
            'source_url' => $url,
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




    // 🧩 LIST
    case 'list':
        $files = glob("$dir/bound-*.json");
        $list = [];

        foreach ($files as $f) {
            $json = json_decode(file_get_contents($f), true);
            $meta = $json['meta'] ?? [];

            $list[] = [
            'file' => basename($f),
            'size' => filesize($f),
            'modified' => date('Y-m-d H:i:s', filemtime($f)),
            'created_at' => $meta['created_at'] ?? null,
            'duration_seconds' => $meta['duration_seconds'] ?? null
            ];
        }

        echo json_encode($list, JSON_PRETTY_PRINT);
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
    echo json_encode(['deleted' => true, 'file' => $file]);
    break;


  // 🧩 UNKNOWN
  default:
    echo json_encode(['error' => 'Unknown action']);
    break;
}
