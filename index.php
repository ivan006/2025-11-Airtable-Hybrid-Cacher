<?php

ini_set('display_errors', false);

require 'CurlClient.php';
require 'OAuthClient.php';
require 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

// Allow only certain methods
switch ($method) {
  case 'OPTIONS':
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    exit();

  case 'GET':
  case 'HEAD':
  case 'POST':
  case 'PUT':
  case 'DELETE':
    break;

  default:
    http_response_code(406);
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
  $url = $_GET['delete'];
  $file = buildFilePath('GET', $url);

  @unlink($file);
  @unlink($file . '.json');

  http_response_code(200);
  echo json_encode(['deleted' => basename($file)]);
  exit();
}

// Require a URL
if (!isset($_GET['url'])) {
  throw new Exception('No URL');
}

$url = $_GET['url'];
$file = buildFilePath($method, $url);
$headers = getallheaders();

$nocache = isset($headers['Vege-Cache-Control']) && $headers['Vege-Cache-Control'] === 'no-cache';

// Fetch and cache if missing or invalid
if ($nocache || !file_exists($file) || !file_exists($file . '.json') || filesize($file) < 50) {
  $requestHeaders = array_map(function ($value, $key) {
    $key = strtolower($key);

    if ($key === 'vege-user-agent') return 'User-Agent: ' . $value;
    if (!in_array($key, ['origin', 'referer', 'connection', 'host', 'accept-encoding', 'dnt', 'vege-cache-control', 'vege-follow'])) {
      return $key . ': ' . $value;
    }
  }, $headers, array_keys($headers));

  $config = readConfig($url);
  $client = isset($config['oauth']) ? new OAuthClient : new CurlClient;
  if (isset($config['oauth'])) $requestHeaders[] = $client->authorizationHeader($config['oauth']);
  if (isset($config['params'])) $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($config['params']);
  if (isset($config['headers'])) foreach ($config['headers'] as $k => $v) $requestHeaders[] = $k . ': ' . $v;

  switch ($method) {
    case 'HEAD':
      curl_setopt($client->curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
      curl_setopt($client->curl, CURLOPT_FOLLOWLOCATION, false);
      break;
    case 'POST':
      curl_setopt($client->curl, CURLOPT_POST, true);
      curl_setopt($client->curl, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
      break;
    case 'DELETE':
      curl_setopt($client->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
      break;
  }

  $output = gzopen($file, 'w');
  $info = $client->get($url, $requestHeaders, $output);
  gzclose($output);

  file_put_contents($file . '.json', json_encode($info, JSON_PRETTY_PRINT));
}

// Send response
$info = json_decode(file_get_contents($file . '.json'), true);
$exposedHeaders = ['link', 'content-location', 'x-status', 'x-location', 'x-ratelimit-limit', 'x-ratelimit-remaining', 'x-ratelimit-reset'];

if ($info['redirect_url']) {
  header('x-status: ' . $info['http_code']);
  header('x-location: ' . $info['redirect_url']);
  http_response_code(204);
} else {
  http_response_code($info['http_code']);
  header('Content-Type: ' . $info['content_type']);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: ' . implode(', ', $exposedHeaders));
header('Content-Location: ' . $info['url']);

foreach ($info['headers'] as $k => $v) {
  if (in_array($k, $exposedHeaders)) header($k . ': ' . $v);
}

if ($info['http_code'] >= 400) {
  unlink($file);
  exit();
}

//if ($method === 'GET' || $method === 'POST') {
  readfile('compress.zlib://' . $file);
//}