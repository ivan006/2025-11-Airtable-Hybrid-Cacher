<?php

function readConfig($url) {
  $configFile = __DIR__ . '/config.json';

  if (!file_exists($configFile)) {
    return null;
  }

  $configs = json_decode(file_get_contents($configFile), true);
  $host = parse_url($url, PHP_URL_HOST);

  return isset($configs[$host]) ? $configs[$host] : null;
}

function buildFilePath($method, $url) {
  $parts = parse_url($url);

  if (!in_array($parts['scheme'], ['http', 'https'])) {
    throw new Exception('URL must be HTTP or HTTPS');
  }

  $host = $parts['host'];
  if (isset($parts['port'])) {
    $host .= '-' . $parts['port'];
  }

  $host = preg_replace('/[^\w\.]/', '-', $host);
  $dir = __DIR__ . '/cache/' . $host . '/' . $method;

  if (!file_exists($dir)) {
    mkdir($dir, 0700, true);
  }

  if ($method === 'POST') {
    $url .= file_get_contents('php://input');
  }

  return $dir . '/' . hash('sha256', $url);
}
