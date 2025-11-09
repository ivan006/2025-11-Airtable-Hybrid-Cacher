<?php
header('Content-Type: application/json; charset=utf-8');
require 'helpers.php';

$dir = __DIR__ . '/cache';
if (!file_exists($dir)) mkdir($dir, 0700, true);

$action = $_GET['action'] ?? 'list';

switch ($action) {

    // 🧩 SAVE
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['records'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        $hash = md5(json_encode($data['records']));
        $path = "$dir/bound-$hash.json";
        file_put_contents($path, json_encode($data['records'], JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'saved', 'file' => basename($path), 'records' => count($data['records'])]);
        break;

        // 🧩 LIST
        case 'list':
        $files = glob("$dir/bound-*.json");
        $out = [];
        foreach ($files as $f) {
            $out[] = [
            'file' => basename($f),
            'size' => filesize($f),
            'modified' => date('Y-m-d H:i:s', filemtime($f))
            ];
        }
        echo json_encode($out, JSON_PRETTY_PRINT);
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
    error_log("🧹 DELETE ATTEMPT: " . $path);
    if (file_exists($path)) unlink($path);
    echo json_encode(['status' => 'deleted', 'file' => $file]);
    break;

  default:
    echo json_encode(['error' => 'Unknown action']);
}
