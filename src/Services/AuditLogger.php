<?php

namespace App\Services;

use App\Database\Mongo;
use MongoDB\BSON\UTCDateTime;

class AuditLogger
{
  public static function log(string $actorId, string $action, array $meta = []): void
  {
    try {
      Mongo::collection('audit_logs')->insertOne([
        'actor_id' => $actorId,
        'action' => $action,
        'meta' => $meta,
        'created_at' => new UTCDateTime(time() * 1000),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
      ]);
    } catch (\Throwable $e) {
      // Swallow audit errors
    }
  }
}


