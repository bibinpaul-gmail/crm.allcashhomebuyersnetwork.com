<?php

namespace App\Config;

use Dotenv\Dotenv;

class Config
{
  private static bool $loaded = false;
  private static array $phpConfig = [];

  public static function load(string $projectRoot): void
  {
    if (self::$loaded) {
      return;
    }
    // Optional: load PHP config first (overrides env)
    $phpConfigPath = $projectRoot . '/config.php';
    if (file_exists($phpConfigPath)) {
      $cfg = require $phpConfigPath;
      if (is_array($cfg)) {
        self::$phpConfig = $cfg;
      }
    }
    if (file_exists($projectRoot . '/.env')) {
      $dotenv = Dotenv::createImmutable($projectRoot);
      $dotenv->load();
    }
    self::$loaded = true;
  }

  public static function string(string $key, string $default = ''): string
  {
    if (array_key_exists($key, self::$phpConfig)) {
      $val = self::$phpConfig[$key];
      return $val === null || $val === '' ? $default : (string)$val;
    }
    $value = getenv($key);
    if ($value !== false && $value !== '') return (string)$value;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return (string)$_SERVER[$key];
    return $default;
  }

  public static function int(string $key, int $default = 0): int
  {
    if (array_key_exists($key, self::$phpConfig)) {
      $val = self::$phpConfig[$key];
      if ($val === null || $val === '') return $default;
      return (int)$val;
    }
    $value = getenv($key);
    if ($value === false || $value === '') { $value = $_ENV[$key] ?? ($_SERVER[$key] ?? ''); }
    if ($value === '') return $default;
    return (int)$value;
  }

  public static function bool(string $key, bool $default = false): bool
  {
    if (array_key_exists($key, self::$phpConfig)) {
      $val = self::$phpConfig[$key];
      if ($val === null || $val === '') return $default;
      $normalized = strtolower((string)$val);
      return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }
    $value = getenv($key);
    if ($value === false || $value === '') { $value = $_ENV[$key] ?? ($_SERVER[$key] ?? ''); }
    if ($value === '') return $default;
    $normalized = strtolower((string)$value);
    return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
  }
}


