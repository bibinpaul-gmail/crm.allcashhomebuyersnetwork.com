<?php

namespace App\Security;

class Password
{
  public static function hash(string $password): string
  {
    if (defined('PASSWORD_ARGON2ID')) {
      return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 1<<17,
        'time_cost' => 4,
        'threads' => 2,
      ]);
    }
    return password_hash($password, PASSWORD_BCRYPT, [
      'cost' => 12,
    ]);
  }

  public static function verify(string $password, string $hash): bool
  {
    return password_verify($password, $hash);
  }
}


