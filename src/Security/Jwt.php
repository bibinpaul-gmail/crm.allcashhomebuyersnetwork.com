<?php

namespace App\Security;

use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key;

class Jwt
{
  public static function issue(array $claims): string
  {
    $now = time();
    $ttl = (int)(getenv('JWT_TTL_SECONDS') ?: 7200);
    $payload = array_merge([
      'iss' => getenv('JWT_ISSUER') ?: 'app',
      'aud' => getenv('JWT_AUDIENCE') ?: 'app',
      'iat' => $now,
      'nbf' => $now,
      'exp' => $now + $ttl,
    ], $claims);
    $secret = getenv('JWT_SECRET') ?: 'dev_secret_change_me';
    return FirebaseJwt::encode($payload, $secret, 'HS256');
  }

  public static function verify(string $token): array
  {
    $secret = getenv('JWT_SECRET') ?: 'dev_secret_change_me';
    $decoded = FirebaseJwt::decode($token, new Key($secret, 'HS256'));
    return (array)$decoded;
  }
}


