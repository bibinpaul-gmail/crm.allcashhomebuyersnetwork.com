<?php

namespace App\Validation;

class Assert
{
  public static function string(array $src, string $key, bool $required = true, int $min = 0, int $max = 1000): string
  {
    $val = isset($src[$key]) ? trim((string)$src[$key]) : '';
    if ($required && $val === '') {
      throw new \InvalidArgumentException($key . ' is required');
    }
    if ($val !== '' && (strlen($val) < $min || strlen($val) > $max)) {
      throw new \InvalidArgumentException($key . ' length invalid');
    }
    return $val;
  }

  public static function phone(array $src, string $key, bool $required = true): string
  {
    $val = isset($src[$key]) ? preg_replace('/[^0-9\+]/', '', (string)$src[$key]) : '';
    if ($required && $val === '') {
      throw new \InvalidArgumentException($key . ' is required');
    }
    return $val;
  }
}


