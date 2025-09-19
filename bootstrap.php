<?php

declare(strict_types=1);

use App\Config\Config;

require __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

// Debug settings
if ((getenv('APP_DEBUG') ?: '0') !== '0') {
  ini_set('display_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '0');
}

// Basic CORS for API routes
function send_cors_headers(): void {
  $allowed = getenv('CORS_ALLOWED_ORIGINS') ?: '';
  $origins = array_filter(array_map('trim', explode(',', $allowed)));
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin && (empty($origins) || in_array($origin, $origins, true))) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
  }
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function json_input(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($data);
}

// Global API exception handler - returns JSON for API routes
set_exception_handler(function (Throwable $e): void {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $isApi = strpos($uri, '/api/index.php') === 0;
  if ($isApi) {
    $debug = (getenv('APP_DEBUG') ?: '0') !== '0';
    $payload = ['error' => 'server_error'];
    if ($debug) {
      $payload['message'] = $e->getMessage();
    }
    json_response($payload, 500);
    return;
  }
  http_response_code(500);
  echo 'Internal Server Error';
});


