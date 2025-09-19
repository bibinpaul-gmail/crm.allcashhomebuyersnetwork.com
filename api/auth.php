<?php

declare(strict_types=1);

use App\Database\Mongo;
use App\Security\Password;
use App\Security\Jwt;
use MongoDB\BSON\UTCDateTime;

function api_auth_login(): void {
  $input = json_input();
  $email = strtolower(trim((string)($input['email'] ?? '')));
  $password = (string)($input['password'] ?? '');
  if ($email === '' || $password === '') {
    json_response(['error' => 'email and password required'], 400);
    return;
  }
  $user = Mongo::collection('agents')->findOne(['email' => $email]);
  if (!$user || !Password::verify($password, (string)($user['password_hash'] ?? ''))) {
    json_response(['error' => 'invalid credentials'], 401);
    return;
  }
  $token = Jwt::issue([
    'sub' => (string)$user['_id'],
    'role' => $user['role'] ?? 'agent',
    'email' => $user['email'] ?? '',
  ]);
  json_response(['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => (int)(getenv('JWT_TTL_SECONDS') ?: 7200)]);
}

function require_auth(array $roles = []): array {
  // Read Authorization header robustly across servers
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if ($hdr === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $hdr = (string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
  }
  if ($hdr === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
      if (strcasecmp($k, 'Authorization') === 0) {
        $hdr = (string)$v;
        break;
      }
    }
  }
  if (!preg_match('/Bearer\s+(.+)/i', $hdr, $m)) {
    json_response(['error' => 'missing token'], 401);
    exit;
  }
  try {
    $claims = App\Security\Jwt::verify($m[1]);
  } catch (Throwable $e) {
    json_response(['error' => 'invalid token'], 401);
    exit;
  }
  if ($roles && !in_array(($claims['role'] ?? 'agent'), $roles, true)) {
    json_response(['error' => 'forbidden'], 403);
    exit;
  }
  return $claims;
}


